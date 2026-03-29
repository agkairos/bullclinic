<?php

namespace App\Models;

use App\Traits\BelongsToClinica;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReceitaMedica extends Model
{
    use BelongsToClinica;

    protected $table = 'receitas_medicas';

    protected $fillable = [
        'clinica_id', 'consulta_id', 'paciente_id', 'veterinario_id',
        'instrucoes_gerais', 'observacoes', 'emitida_em',
    ];

    protected $casts = ['emitida_em' => 'datetime'];

    public function consulta(): BelongsTo
    {
        return $this->belongsTo(Consulta::class);
    }

    public function paciente(): BelongsTo
    {
        return $this->belongsTo(Paciente::class);
    }

    public function veterinario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'veterinario_id');
    }

    public function itens(): HasMany
    {
        return $this->hasMany(ReceitaItem::class, 'receita_id')->orderBy('ordem');
    }
}
