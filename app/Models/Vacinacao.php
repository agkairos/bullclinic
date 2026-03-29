<?php

namespace App\Models;

use App\Traits\BelongsToClinica;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Vacinacao extends Model
{
    use BelongsToClinica;

    protected $table = 'vacinacoes';

    protected $fillable = [
        'clinica_id', 'consulta_id', 'paciente_id', 'veterinario_id',
        'vacina_nome', 'fabricante', 'lote',
        'data_aplicacao', 'proxima_dose_em', 'observacoes', 'reacoes_adversas',
    ];

    protected $casts = [
        'data_aplicacao' => 'date',
        'proxima_dose_em' => 'date',
    ];

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
}
