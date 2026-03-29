<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Conta extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'plano_id', 'nome', 'email', 'cnpj',
        'status_pagamento', 'stripe_customer_id', 'stripe_subscription_id',
        'woovi_customer_id', 'data_vencimento', 'data_trial_fim', 'ativa',
    ];

    protected $casts = [
        'ativa' => 'boolean',
        'data_vencimento' => 'datetime',
        'data_trial_fim' => 'datetime',
    ];

    public function plano(): BelongsTo
    {
        return $this->belongsTo(Plano::class);
    }

    public function clinicas(): HasMany
    {
        return $this->hasMany(Clinica::class);
    }

    public function unidadesAtivas(): HasMany
    {
        return $this->hasMany(Clinica::class)->where('ativa', true);
    }

    public function estaAtiva(): bool
    {
        if (! $this->ativa) return false;
        if (! in_array($this->status_pagamento, ['trial', 'pago'])) return false;
        if ($this->status_pagamento === 'trial' && $this->data_trial_fim?->isPast()) return false;
        return true;
    }

    public function podeAdicionarUnidade(): bool
    {
        if ($this->plano->clinicas_ilimitadas) return true;
        return $this->clinicas()->count() < $this->plano->limite_clinicas;
    }
}
