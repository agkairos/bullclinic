<?php

namespace App\Models;

use App\Traits\BelongsToClinica;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ExameSolicitado extends Model
{
    use BelongsToClinica;

    protected $table = 'exames_solicitados';

    protected $fillable = [
        'clinica_id', 'consulta_id', 'paciente_id', 'veterinario_id',
        'tipo_exame_id', 'laboratorio_id', 'descricao', 'instrucoes',
        'status', 'solicitado_em', 'previsao_em', 'concluido_em',
    ];

    protected $casts = [
        'solicitado_em' => 'datetime',
        'previsao_em' => 'datetime',
        'concluido_em' => 'datetime',
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

    public function tipoExame(): BelongsTo
    {
        return $this->belongsTo(TipoExame::class);
    }

    public function laboratorio(): BelongsTo
    {
        return $this->belongsTo(Laboratorio::class);
    }

    public function resultado(): HasOne
    {
        return $this->hasOne(ResultadoExame::class);
    }
}
