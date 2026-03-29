<?php

namespace App\Models;

use App\Traits\BelongsToClinica;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MovimentacaoEstoque extends Model
{
    use BelongsToClinica;

    protected $table = 'movimentacoes_estoque';

    protected $fillable = [
        'clinica_id', 'produto_id', 'tipo_movimentacao_id', 'usuario_id',
        'paciente_id', 'consulta_id', 'quantidade', 'valor_unitario',
        'observacoes', 'movimentado_em',
    ];

    protected $casts = [
        'quantidade' => 'decimal:3',
        'valor_unitario' => 'decimal:2',
        'movimentado_em' => 'datetime',
    ];

    public function produto(): BelongsTo
    {
        return $this->belongsTo(Produto::class);
    }

    public function tipoMovimentacao(): BelongsTo
    {
        return $this->belongsTo(TipoMovimentacao::class);
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function paciente(): BelongsTo
    {
        return $this->belongsTo(Paciente::class);
    }

    public function consulta(): BelongsTo
    {
        return $this->belongsTo(Consulta::class);
    }
}
