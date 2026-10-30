<?php

namespace App\Http\Controllers;

use App\Models\Clinica;
use App\Models\Documento;
use App\Models\Paciente;
use App\Services\AuditService;
use App\Services\ClinicaContext;
use Aws\S3\S3Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class DocumentoController extends Controller
{
  public function index(Request $request): Response
  {
    $clinicaId = ClinicaContext::id();
    $clinica = Clinica::with('plano')->findOrFail($clinicaId);

    $query = Documento::with(['uploadedBy:id,name', 'documentavel'])
      ->when($request->categoria, fn($q) => $q->where('categoria', $request->categoria))
      ->when($request->search, fn($q) => $q->where('nome', 'like', "%{$request->search}%"))
      ->when($request->data_inicio, fn($q) => $q->whereDate('created_at', '>=', $request->data_inicio))
      ->when($request->data_fim, fn($q) => $q->whereDate('created_at', '<=', $request->data_fim))
      ->orderByDesc('created_at');

    $documentos = $query->paginate(20)->through(fn($d) => [
      'id' => $d->id,
      'nome' => $d->nome,
      'arquivo_url' => $d->arquivo_url,
      'mime_type' => $d->mime_type,
      'tamanho_bytes' => $d->tamanho_bytes,
      'categoria' => $d->categoria,
      'created_at' => $d->created_at->format('d/m/Y H:i'),
      'uploaded_by' => $d->uploadedBy?->name,
      'documentavel_type' => $d->documentavel_type,
      'documentavel_id' => $d->documentavel_id,
      'documentavel_nome' => $this->resolveDocumentavelNome($d),
    ]);

    $storageUsadoGb = (float) $clinica->limite_storage_usado_gb;
    $storageLimiteGb = $clinica->plano?->limite_storage_gb ?? 5;

    return Inertia::render('Documentos/Index', [
      'documentos' => $documentos,
      'storage' => [
        'usado_gb' => round($storageUsadoGb, 3),
        'limite_gb' => $storageLimiteGb,
        'percentual' => $storageLimiteGb > 0 ? min(100, round(($storageUsadoGb / $storageLimiteGb) * 100, 1)) : 0,
      ],
      'filters' => $request->only(['search', 'categoria', 'data_inicio', 'data_fim']),
    ]);
  }

  /**
   * Upload de arquivo através do servidor Laravel (igual aos outros módulos).
   */
  public function store(Request $request): JsonResponse
  {
    $data = $request->validate([
      'arquivo' => ['required', 'file', 'max:524288'], // 500 MB
      'nome' => ['nullable', 'string', 'max:255'],
      'categoria' => ['nullable', 'in:exame,receita,atestado,contrato,imagem,outro'],
      'documentavel_type' => ['nullable', 'in:paciente,consulta,cliente'],
      'documentavel_id' => ['nullable', 'integer'],
    ]);

    $clinicaId = ClinicaContext::id();
    $clinica = Clinica::with('plano')->findOrFail($clinicaId);

    $arquivo = $request->file('arquivo');
    $tamanhoBytes = $arquivo->getSize();

    // Verificar limite de storage
    $tamanhoGb = $tamanhoBytes / (1024 ** 3);
    $limiteGb = $clinica->plano?->limite_storage_gb ?? 5;
    if (($clinica->limite_storage_usado_gb + $tamanhoGb) > $limiteGb) {
      return response()->json(['error' => 'Limite de armazenamento atingido.'], 422);
    }

    // Upload para S3 através do servidor
    $path = $arquivo->store("clinica_{$clinicaId}/arquivos", 's3_images');

    $morphMap = [
      'paciente' => \App\Models\Paciente::class,
      'consulta' => \App\Models\Consulta::class,
      'cliente' => \App\Models\Cliente::class,
    ];

    $documento = Documento::create([
      'clinica_id' => $clinicaId,
      'uploaded_by_id' => $request->user()->id,
      'nome' => $data['nome'] ?? $arquivo->getClientOriginalName(),
      'arquivo_url' => $path,
      'mime_type' => $arquivo->getMimeType() ?? 'application/octet-stream',
      'tamanho_bytes' => $tamanhoBytes,
      'categoria' => $data['categoria'] ?? 'outro',
      'documentavel_type' => isset($data['documentavel_type']) ? $morphMap[$data['documentavel_type']] : null,
      'documentavel_id' => $data['documentavel_id'] ?? null,
    ]);

    // Registrar auditoria
    AuditService::criado($documento);

    // Atualizar storage usado
    $clinica->increment('limite_storage_usado_gb', round($tamanhoGb, 4));

    return response()->json([
      'id' => $documento->id,
      'nome' => $documento->nome,
      'arquivo_url' => $documento->arquivo_url,
      'mime_type' => $documento->mime_type,
      'tamanho_bytes' => $documento->tamanho_bytes,
      'categoria' => $documento->categoria,
      'created_at' => $documento->created_at->format('d/m/Y H:i'),
    ]);
  }

  /**
   * Gera URL assinada temporária para download seguro do arquivo (válida por 1 hora).
   */
  public function signedUrl(Documento $documento): JsonResponse
  {
    // Extrair o path/key do S3 da URL completa
    $path = $documento->getRawOriginal('arquivo_url');

    $diskConfig = config('filesystems.disks.s3_images');

    $client = new S3Client([
      'version' => 'latest',
      'region' => $diskConfig['region'],
      'credentials' => [
        'key' => $diskConfig['key'],
        'secret' => $diskConfig['secret'],
      ],
    ]);

    $cmd = $client->getCommand('GetObject', [
      'Bucket' => $diskConfig['bucket'],
      'Key' => $path,
    ]);

    $request = $client->createPresignedRequest($cmd, '+1 hour');
    $signedUrl = (string) $request->getUri();

    return response()->json(['url' => $signedUrl]);
  }

  public function destroy(Documento $documento, Request $request): RedirectResponse
  {
    Gate::authorize('delete', $documento);

    // Remover do S3
    $key = $this->keyFromUrl($documento->arquivo_url);
    if ($key) {
      Storage::disk('s3_images')->delete($key);
    }

    // Subtrair do storage usado
    $removeGb = $documento->tamanho_bytes / (1024 ** 3);
    Clinica::where('id', $documento->clinica_id)->decrement('limite_storage_usado_gb', $removeGb);

    // Registrar auditoria
    AuditService::excluido($documento);

    $documento->delete();

    return back()->with('success', 'Documento removido.');
  }

  private function keyFromUrl(string $url): ?string
  {
    $bucket = config('filesystems.disks.s3_images.bucket');
    if (preg_match('#/' . preg_quote($bucket, '#') . '/(.+)$#', $url, $m)) {
      return $m[1];
    }
    // URL estilo path: https://bucket.s3.region.amazonaws.com/key
    $parsed = parse_url($url);
    $path = ltrim($parsed['path'] ?? '', '/');
    return $path ?: null;
  }

  private function resolveDocumentavelNome(Documento $d): ?string
  {
    if (!$d->documentavel)
      return null;
    return match (true) {
      $d->documentavel instanceof Paciente => $d->documentavel->nome,
      $d->documentavel instanceof \App\Models\Consulta => "Consulta #{$d->documentavel->id}",
      $d->documentavel instanceof \App\Models\Cliente => $d->documentavel->nome,
      default => null,
    };
  }
}
