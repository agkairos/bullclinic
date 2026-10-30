<?php

namespace App\Models;

use Aws\S3\S3Client;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;

class Mensagem extends Model
{
  protected $table = 'mensagens';

  protected $fillable = [
    'conversa_id',
    'user_id',
    'resposta_a_id',
    'tipo',
    'conteudo',
    'arquivo_url',
    'arquivo_tamanho_bytes',
    'duracao_audio_segundos',
    'fixada',
    'deletada_em',
  ];

  protected $casts = [
    'fixada' => 'boolean',
    'deletada_em' => 'datetime',
  ];

  protected static function booted(): void
  {
    static::updated(function (Mensagem $mensagem) {
      if ($mensagem->isDirty('arquivo_url')) {
        Cache::forget("mensagem_arquivo_signed_{$mensagem->id}");
      }
    });

    static::deleted(function (Mensagem $mensagem) {
      Cache::forget("mensagem_arquivo_signed_{$mensagem->id}");
    });
  }

  protected function arquivoUrl(): Attribute
  {
    return Attribute::make(
      get: function ($value) {
        if (!$value) {
          return null;
        }

        // Cache da URL assinada por 50 minutos
        $cacheKey = "mensagem_arquivo_signed_{$this->id}";

        return Cache::remember($cacheKey, now()->addMinutes(50), function () use ($value) {
          // Se é path relativo do S3
          if (!str_starts_with($value, 'http')) {
            $config = config('filesystems.disks.s3_images');
            return $this->generateSignedUrl($config, $config['bucket'], $value);
          }

          // Se já é URL S3
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

  public function conversa(): BelongsTo
  {
    return $this->belongsTo(Conversa::class);
  }

  public function user(): BelongsTo
  {
    return $this->belongsTo(User::class);
  }

  public function respostaA(): BelongsTo
  {
    return $this->belongsTo(Mensagem::class, 'resposta_a_id');
  }

  public function respostas(): HasMany
  {
    return $this->hasMany(Mensagem::class, 'resposta_a_id');
  }

  public function leituras(): HasMany
  {
    return $this->hasMany(MensagemLeitura::class);
  }

  public function foiLidaPor(int $userId): bool
  {
    return $this->leituras()->where('user_id', $userId)->exists();
  }
}
