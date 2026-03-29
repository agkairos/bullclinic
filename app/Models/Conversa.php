<?php

namespace App\Models;

use App\Traits\BelongsToClinica;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Conversa extends Model
{
    use BelongsToClinica;

    protected $table = 'conversas';

    protected $fillable = ['clinica_id', 'criado_por_id', 'nome', 'tipo', 'arquivada'];

    protected $casts = ['arquivada' => 'boolean'];

    public function clinica(): BelongsTo
    {
        return $this->belongsTo(Clinica::class);
    }

    public function criadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'criado_por_id');
    }

    public function membros(): HasMany
    {
        return $this->hasMany(ConversaMembro::class);
    }

    public function mensagens(): HasMany
    {
        return $this->hasMany(Mensagem::class);
    }

    public function ultimaMensagem(): ?Mensagem
    {
        return $this->mensagens()->whereNull('deletada_em')->latest()->first();
    }

    public function naoLidasPara(int $userId): int
    {
        return $this->mensagens()
            ->whereNull('deletada_em')
            ->where('user_id', '!=', $userId)
            ->whereDoesntHave('leituras', fn($q) => $q->where('user_id', $userId))
            ->count();
    }
}
