<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IntervaloDia extends Model
{
    protected $table = 'intervalos_dia';

    protected $fillable = [
        'user_id', 'clinica_id', 'dia_semana',
        'hora_inicio_intervalo', 'hora_fim_intervalo',
    ];

    public function veterinario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function clinica(): BelongsTo
    {
        return $this->belongsTo(Clinica::class);
    }
}
