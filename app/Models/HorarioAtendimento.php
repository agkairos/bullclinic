<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HorarioAtendimento extends Model
{
    protected $table = 'horarios_atendimento';

    protected $fillable = [
        'user_id', 'clinica_id', 'dia_semana', 'ordem',
        'hora_inicio', 'hora_fim',
        'duracao_consulta_minutos', 'intervalo_entre_consultas_minutos', 'ativo',
    ];

    protected $casts = ['ativo' => 'boolean'];

    public function veterinario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function clinica(): BelongsTo
    {
        return $this->belongsTo(Clinica::class);
    }

    public function intervalos(): HasMany
    {
        return $this->hasMany(IntervaloDia::class, 'user_id', 'user_id')
            ->where('clinica_id', $this->clinica_id)
            ->where('dia_semana', $this->dia_semana);
    }
}
