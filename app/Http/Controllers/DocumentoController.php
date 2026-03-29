<?php

namespace App\Http\Controllers;

use App\Models\Clinica;
use App\Models\Documento;
use App\Models\Paciente;
use App\Services\ClinicaContext;
use Aws\S3\S3Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
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
            'filters' => $request->only(['search', 'categoria']),
        ]);
    }

    /**
     * Gera URL pré-assinada para upload direto ao S3.
     * O servidor não processa o arquivo — apenas assina a URL.
     */
    public function presignedUrl(Request $request): JsonResponse
    {
        $request->validate([
            'nome' => ['required', 'string', 'max:255'],
            'mime_type' => ['required', 'string'],
            'tamanho_bytes' => ['required', 'integer', 'min:1', 'max:524288000'], // 500 MB
        ]);

        $clinicaId = ClinicaContext::id();
        $clinica = Clinica::with('plano')->findOrFail($clinicaId);

        // Verificar limite de storage
        $tamanhoGb = $request->tamanho_bytes / (1024 ** 3);
        $limiteGb = $clinica->plano?->limite_storage_gb ?? 5;
        if (($clinica->limite_storage_usado_gb + $tamanhoGb) > $limiteGb) {
            return response()->json(['error' => 'Limite de armazenamento atingido.'], 422);
        }

        $ext = $this->extensaoDoMime($request->mime_type) ?? pathinfo($request->nome, PATHINFO_EXTENSION);
        $key = "{$clinicaId}/arquivos/" . Str::uuid() . ($ext ? ".{$ext}" : '');

        $diskConfig = config('filesystems.disks.s3_images');

        /** @var S3Client $client */
        $client = new S3Client([
            'version' => 'latest',
            'region' => $diskConfig['region'],
            'credentials' => [
                'key' => $diskConfig['key'],
                'secret' => $diskConfig['secret'],
            ],
            'endpoint' => $diskConfig['endpoint'] ?? null,
            'use_path_style_endpoint' => $diskConfig['use_path_style_endpoint'] ?? false,
        ]);

        $cmd = $client->getCommand('PutObject', [
            'Bucket' => $diskConfig['bucket'],
            'Key' => $key,
            'ContentType' => $request->mime_type,
            'ACL' => 'public-read',
        ]);

        $presignedRequest = $client->createPresignedRequest($cmd, '+10 minutes');
        $uploadUrl = (string) $presignedRequest->getUri();

        $baseUrl = rtrim($diskConfig['url'] ?? "https://{$diskConfig['bucket']}.s3.{$diskConfig['region']}.amazonaws.com", '/');
        $arquivoUrl = "{$baseUrl}/{$key}";

        return response()->json([
            'upload_url' => $uploadUrl,
            'key' => $key,
            'arquivo_url' => $arquivoUrl,
        ]);
    }

    /**
     * Salva os metadados após o upload já ter sido feito diretamente no S3.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'nome' => ['required', 'string', 'max:255'],
            'arquivo_url' => ['required', 'url'],
            'mime_type' => ['required', 'string'],
            'tamanho_bytes' => ['required', 'integer', 'min:1'],
            'categoria' => ['nullable', 'in:exame,receita,atestado,contrato,imagem,outro'],
            'documentavel_type' => ['nullable', 'in:paciente,consulta,cliente'],
            'documentavel_id' => ['nullable', 'integer'],
        ]);

        $morphMap = [
            'paciente' => \App\Models\Paciente::class,
            'consulta' => \App\Models\Consulta::class,
            'cliente' => \App\Models\Cliente::class,
        ];

        $clinicaId = ClinicaContext::id();

        $documento = Documento::create([
            'clinica_id' => $clinicaId,
            'uploaded_by_id' => $request->user()->id,
            'nome' => $data['nome'],
            'arquivo_url' => $data['arquivo_url'],
            'mime_type' => $data['mime_type'],
            'tamanho_bytes' => $data['tamanho_bytes'],
            'categoria' => $data['categoria'] ?? 'outro',
            'documentavel_type' => isset($data['documentavel_type']) ? $morphMap[$data['documentavel_type']] : null,
            'documentavel_id' => $data['documentavel_id'] ?? null,
        ]);

        // Atualizar storage usado
        $addGb = $data['tamanho_bytes'] / (1024 ** 3);
        Clinica::where('id', $clinicaId)->increment('limite_storage_usado_gb', $addGb);

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
        if (!$d->documentavel) return null;
        return match (true) {
            $d->documentavel instanceof Paciente => $d->documentavel->nome,
            $d->documentavel instanceof \App\Models\Consulta => "Consulta #{$d->documentavel->id}",
            $d->documentavel instanceof \App\Models\Cliente => $d->documentavel->nome,
            default => null,
        };
    }

    private function extensaoDoMime(string $mime): ?string
    {
        return match ($mime) {
            'application/pdf' => 'pdf',
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            'application/msword' => 'doc',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
            'application/vnd.ms-excel' => 'xls',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
            'video/mp4' => 'mp4',
            default => null,
        };
    }
}
