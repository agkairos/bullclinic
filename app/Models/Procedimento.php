<?php

namespace App\Models;

use App\Traits\BelongsToClinica;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Procedimento extends Model
{
    use BelongsToClinica;

    protected $fillable = [
        'clinica_id', 'consulta_id', 'paciente_id', 'veterinario_id',
        'tipo_procedimento', 'descricao', 'status', 'realizado_em', 'valor_cobrado',
    ];

    protected $casts = [
        'realizado_em' => 'datetime',
        'valor_cobrado' => 'decimal:2',
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
