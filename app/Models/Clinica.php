<?php

namespace App\Models;

use Aws\S3\S3Client;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;

class Clinica extends Model
{
  use SoftDeletes;

  protected $fillable = [
    'conta_id',
    'plano_id',
    'nome',
    'slug',
    'cnpj',
    'telefone',
    'email',
    'logo',
    'cep',
    'logradouro',
    'numero',
    'complemento',
    'bairro',
    'cidade',
    'estado',
    'timezone',
    'ativa',
    'limite_storage_usado_gb',
    'configs',
  ];

  protected $casts = [
    'ativa' => 'boolean',
    'limite_storage_usado_gb' => 'decimal:4',
    'configs' => 'array',
  ];

  protected static function booted(): void
  {
    // Limpar cache da logo quando for atualizada
    static::updated(function (Clinica $clinica) {
      if ($clinica->isDirty('logo')) {
        Cache::forget("clinica_logo_signed_{$clinica->id}");
      }
    });
  }

  protected function logo(): Attribute
  {
    return Attribute::make(
      get: function ($value) {
        if (!$value) {
          return null;
        }

        // Cache da URL assinada por 50 minutos (expira em 1 hora, renovamos antes)
        $cacheKey = "clinica_logo_signed_{$this->id}";

        return Cache::remember($cacheKey, now()->addMinutes(50), function () use ($value) {
          // Se já é uma URL completa, gera URL assinada se for S3
          if (str_starts_with($value, 'http')) {
            // Extrair o path do S3 da URL
            if (str_contains($value, '.s3.') || str_contains($value, 's3.amazonaws.com')) {
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

            return $value;
          }

          // Se é um path relativo, construir URL assinada
          $config = config('filesystems.disks.s3_images');
          return $this->generateSignedUrl($config, $config['bucket'], $value);
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

  public function conta(): BelongsTo
  {
    return $this->belongsTo(Conta::class);
  }

  public function plano(): BelongsTo
  {
    return $this->belongsTo(Plano::class);
  }

  public function usuarios(): HasMany
  {
    return $this->hasMany(UserClinica::class);
  }

  public function clientes(): HasMany
  {
    return $this->hasMany(Cliente::class);
  }

  public function pacientes(): HasMany
  {
    return $this->hasMany(Paciente::class);
  }

  public function agendamentos(): HasMany
  {
    return $this->hasMany(Agendamento::class);
  }

  public function consultas(): HasMany
  {
    return $this->hasMany(Consulta::class);
  }

  public function estaAtiva(): bool
  {
    return $this->ativa && ($this->conta?->estaAtiva() ?? false);
  }

  public function storageDisponivel(): bool
  {
    $plano = $this->conta?->plano ?? $this->plano;
    return $this->limite_storage_usado_gb < $plano->limite_storage_gb;
  }
}
