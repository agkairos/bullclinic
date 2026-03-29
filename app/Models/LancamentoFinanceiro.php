<?php

namespace App\Models;

use App\Traits\BelongsToClinica;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class LancamentoFinanceiro extends Model
{
    use BelongsToClinica, SoftDeletes;

    protected $table = 'lancamentos_financeiros';

    protected $fillable = [
        'clinica_id', 'tipo', 'descricao', 'valor',
        'data_vencimento', 'data_pagamento', 'forma_pagamento',
        'categoria_id', 'cliente_id', 'fornecedor_id', 'consulta_id', 'usuario_id',
        'status', 'recorrente', 'periodicidade', 'observacoes',
    ];

    protected $casts = [
        'valor' => 'decimal:2',
        'data_vencimento' => 'date',
        'data_pagamento' => 'date',
        'recorrente' => 'boolean',
    ];

    public function clinica(): BelongsTo
    {
        return $this->belongsTo(Clinica::class);
    }

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(CategoriaFinanceira::class, 'categoria_id');
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function fornecedor(): BelongsTo
    {
        return $this->belongsTo(Fornecedor::class);
    }

    public function consulta(): BelongsTo
    {
        return $this->belongsTo(Consulta::class);
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function scopeReceitas($query)
    {
        return $query->where('tipo', 'receita');
    }

    public function scopeDespesas($query)
    {
        return $query->where('tipo', 'despesa');
    }
}
