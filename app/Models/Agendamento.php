<?php

namespace App\Models;

use App\Traits\BelongsToClinica;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Agendamento extends Model
{
    use BelongsToClinica, SoftDeletes;

    protected $fillable = [
        'clinica_id', 'cliente_id', 'paciente_id', 'veterinario_id', 'tipo_servico_id',
        'status', 'data', 'hora_inicio', 'hora_fim', 'duracao_estimada_minutos',
        'primeira_consulta', 'urgencia',
        'observacoes_publicas', 'observacoes_internas', 'valor_estimado',
        'confirmado_em', 'checkin_em', 'atendimento_iniciado_em',
        'atendimento_finalizado_em', 'cancelado_em', 'cancelado_por_id',
    ];

    protected $casts = [
        'data' => 'date',
        'primeira_consulta' => 'boolean',
        'urgencia' => 'boolean',
        'valor_estimado' => 'decimal:2',
        'confirmado_em' => 'datetime',
        'checkin_em' => 'datetime',
        'atendimento_iniciado_em' => 'datetime',
        'atendimento_finalizado_em' => 'datetime',
        'cancelado_em' => 'datetime',
    ];

    public function clinica(): BelongsTo
    {
        return $this->belongsTo(Clinica::class);
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function paciente(): BelongsTo
    {
        return $this->belongsTo(Paciente::class);
    }

    public function veterinario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'veterinario_id');
    }

    public function tipoServico(): BelongsTo
    {
        return $this->belongsTo(TipoServico::class);
    }

    public function canceladoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelado_por_id');
    }

    public function consulta(): HasOne
    {
        return $this->hasOne(Consulta::class);
    }

    public function scopePorData($query, string $data)
    {
        return $query->whereDate('data', $data);
    }

    public function scopeAtivos($query)
    {
        return $query->whereNotIn('status', ['cancelado', 'finalizado']);
    }

    public function scopeEmEspera($query)
    {
        return $query->where('status', 'aguardando');
    }
}
