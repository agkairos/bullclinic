<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Mensagem extends Model
{
    protected $table = 'mensagens';

    protected $fillable = [
        'conversa_id', 'user_id', 'resposta_a_id', 'tipo',
        'conteudo', 'arquivo_url', 'arquivo_tamanho_bytes',
        'duracao_audio_segundos', 'fixada', 'deletada_em',
    ];

    protected $casts = [
        'fixada' => 'boolean',
        'deletada_em' => 'datetime',
    ];

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
