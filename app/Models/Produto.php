<?php

namespace App\Models;

use App\Traits\BelongsToClinica;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Produto extends Model
{
    use BelongsToClinica, SoftDeletes;

    protected $fillable = [
        'clinica_id', 'categoria_id', 'fornecedor_id', 'nome', 'tipo', 'codigo_barras',
        'unidade_medida', 'quantidade_estoque', 'estoque_minimo',
        'preco_custo', 'preco_venda', 'ativo',
        'forma_farmaceutica', 'dosagem_padrao', 'via_administracao_padrao',
        'frequencia_padrao', 'duracao_padrao', 'controlado',
    ];

    protected $casts = [
        'quantidade_estoque' => 'decimal:3',
        'estoque_minimo' => 'decimal:3',
        'preco_custo' => 'decimal:2',
        'preco_venda' => 'decimal:2',
        'ativo' => 'boolean',
        'controlado' => 'boolean',
    ];

    public function clinica(): BelongsTo
    {
        return $this->belongsTo(Clinica::class);
    }

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(CategoriaProduto::class, 'categoria_id');
    }

    public function fornecedor(): BelongsTo
    {
        return $this->belongsTo(Fornecedor::class);
    }

    public function movimentacoes(): HasMany
    {
        return $this->hasMany(MovimentacaoEstoque::class);
    }

    public function estoqueCritico(): bool
    {
        return $this->quantidade_estoque <= $this->estoque_minimo;
    }
}
