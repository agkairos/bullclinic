<?php

namespace App\Http\Controllers;

use App\Models\Paciente;
use App\Models\PacienteFoto;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PacienteFotoController extends Controller
{
    /**
     * Upload de foto do paciente.
     * Caminho no S3: {clinica_id}/pacientes/fotos/{uuid}.{ext}
     */
    public function store(Request $request, Paciente $paciente): JsonResponse
    {
        $request->validate([
            'foto' => ['required', 'image', 'max:5120'], // 5 MB
        ]);

        $clinicaId = $paciente->clinica_id;
        $ext       = $request->file('foto')->getClientOriginalExtension();
        $path      = "{$clinicaId}/pacientes/fotos/" . Str::uuid() . ".{$ext}";

        Storage::disk('s3_images')->put($path, $request->file('foto')->get(), 'public');

        $url = Storage::disk('s3_images')->url($path);

        // Primeira foto vira principal automaticamente
        $isPrincipal = $paciente->fotos()->count() === 0;

        $foto = $paciente->fotos()->create([
            'url'            => $url,
            'tamanho_bytes'  => $request->file('foto')->getSize(),
            'principal'      => $isPrincipal,
        ]);

        return response()->json(['foto' => $foto]);
    }

    /**
     * Define uma foto como principal (remove principal das demais).
     */
    public function setPrincipal(Paciente $paciente, PacienteFoto $foto): JsonResponse
    {
        $paciente->fotos()->update(['principal' => false]);
        $foto->update(['principal' => true]);

        return response()->json(['ok' => true]);
    }

    /**
     * Remove foto do S3 e do banco.
     */
    public function destroy(Paciente $paciente, PacienteFoto $foto): JsonResponse
    {
        $path = $this->pathFromUrl($foto->url);
        if ($path) {
            Storage::disk('s3_images')->delete($path);
        }

        $wasMain = $foto->principal;
        $foto->delete();

        // Se era principal, promove a próxima foto disponível
        if ($wasMain) {
            $proxima = $paciente->fotos()->first();
            $proxima?->update(['principal' => true]);
        }

        return response()->json(['ok' => true]);
    }

    /** Extrai o path relativo a partir da URL pública do S3. */
    private function pathFromUrl(string $url): ?string
    {
        $bucket = config('filesystems.disks.s3_images.bucket');
        $region = config('filesystems.disks.s3_images.region');
        $base   = "https://{$bucket}.s3.{$region}.amazonaws.com/";
        if (str_starts_with($url, $base)) {
            return substr($url, strlen($base));
        }
        return null;
    }
}
