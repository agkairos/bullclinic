<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plano extends Model
{
    protected $fillable = [
        'nome', 'slug', 'descricao', 'preco_mensal', 'preco_anual',
        'limite_usuarios', 'limite_clinicas', 'limite_consultas_mes', 'limite_storage_gb',
        'consultas_ilimitadas', 'clinicas_ilimitadas',
        'chat_audio', 'chat_audio_duracao_segundos', 'chat_grupos', 'chat_grupos_max_membros',
        'stripe_product_id', 'stripe_price_mensal_id', 'stripe_price_anual_id',
        'ativo', 'ordem',
    ];

    protected $casts = [
        'preco_mensal' => 'decimal:2',
        'preco_anual' => 'decimal:2',
        'consultas_ilimitadas' => 'boolean',
        'clinicas_ilimitadas' => 'boolean',
        'chat_audio' => 'boolean',
        'chat_grupos' => 'boolean',
        'ativo' => 'boolean',
    ];

    public function clinicas(): HasMany
    {
        return $this->hasMany(Clinica::class);
    }
}
