<?php

namespace App\Models;

use Aws\S3\S3Client;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;

class PacienteFoto extends Model
{
  protected $table = 'paciente_fotos';

  protected $fillable = ['paciente_id', 'url', 'tamanho_bytes', 'principal'];

  protected $casts = ['principal' => 'boolean'];

  protected static function booted(): void
  {
    // Limpar cache da URL quando for atualizada ou deletada
    static::updated(function (PacienteFoto $foto) {
      if ($foto->isDirty('url')) {
        Cache::forget("paciente_foto_signed_{$foto->id}");
      }
    });

    static::deleted(function (PacienteFoto $foto) {
      Cache::forget("paciente_foto_signed_{$foto->id}");
    });
  }

  protected function url(): Attribute
  {
    return Attribute::make(
      get: function ($value) {
        if (!$value) {
          return null;
        }

        // Cache da URL assinada por 50 minutos
        $cacheKey = "paciente_foto_signed_{$this->id}";

        return Cache::remember($cacheKey, now()->addMinutes(50), function () use ($value) {
          // Se já é uma URL completa e é S3
          if (str_starts_with($value, 'http') && (str_contains($value, '.s3.') || str_contains($value, 's3.amazonaws.com'))) {
            $config = config('filesystems.disks.s3_images');
            $bucket = $config['bucket'];

            // Extrair path da URL
            if (preg_match('#/' . preg_quote($bucket, '#') . '/(.+)$#', $value, $m)) {
              $path = $m[1];
            } else {
              $parsed = parse_url($value);
              $path = ltrim($parsed['path'] ?? '', '/');
            }

            return $this->generateSignedUrl($config, $bucket, $path);
          }

          // Se não é S3, retorna como está
          return $value;
        });
      },
    );
  }

  private function generateSignedUrl(array $config, string $bucket, string $path): string
  {
    $client = new S3Client([
      'version' => 'latest',
      'region' => $config['region'],
      'credentials' => [
        'key' => $config['key'],
        'secret' => $config['secret'],
      ],
    ]);

    $cmd = $client->getCommand('GetObject', [
      'Bucket' => $bucket,
      'Key' => $path,
    ]);

    $request = $client->createPresignedRequest($cmd, '+1 hour');
    return (string) $request->getUri();
  }

  public function paciente(): BelongsTo
  {
    return $this->belongsTo(Paciente::class);
  }
}
