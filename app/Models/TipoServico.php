<?php

namespace App\Models;

use App\Traits\BelongsToClinica;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TipoServico extends Model
{
    use BelongsToClinica;

    protected $table = 'tipos_servico';

    protected $fillable = ['clinica_id', 'nome', 'descricao', 'duracao_minutos', 'preco', 'cor', 'ativo'];

    protected $casts = [
        'preco' => 'decimal:2',
        'ativo' => 'boolean',
    ];

    public function clinica(): BelongsTo
    {
        return $this->belongsTo(Clinica::class);
    }

    public function agendamentos(): HasMany
    {
        return $this->hasMany(Agendamento::class);
    }
}
