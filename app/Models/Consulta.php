<?php

namespace App\Models;

use App\Traits\BelongsToClinica;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Consulta extends Model
{
  use BelongsToClinica, SoftDeletes;

  protected $fillable = [
    'clinica_id',
    'agendamento_id',
    'paciente_id',
    'veterinario_id',
    'tipo_consulta',
    'anamnese',
    'exame_fisico',
    'diagnostico',
    'prognostico',
    'observacoes',
    'status',
    'iniciada_em',
    'finalizada_em',
  ];

  protected $casts = [
    'iniciada_em' => 'datetime',
    'finalizada_em' => 'datetime',
  ];

  public function clinica(): BelongsTo
  {
    return $this->belongsTo(Clinica::class);
  }

  public function agendamento(): BelongsTo
  {
    return $this->belongsTo(Agendamento::class);
  }

  public function paciente(): BelongsTo
  {
    return $this->belongsTo(Paciente::class);
  }

  public function veterinario(): BelongsTo
  {
    return $this->belongsTo(User::class, 'veterinario_id');
  }

  public function receitas(): HasMany
  {
    return $this->hasMany(ReceitaMedica::class);
  }

  public function exames(): HasMany
  {
    return $this->hasMany(ExameSolicitado::class);
  }

  public function procedimentos(): HasMany
  {
    return $this->hasMany(Procedimento::class);
  }

  public function vacinacoes(): HasMany
  {
    return $this->hasMany(Vacinacao::class);
  }

  public function produtosUtilizados(): HasMany
  {
    return $this->hasMany(ConsultaProduto::class);
  }

  public function documentos(): MorphMany
  {
    return $this->morphMany(Documento::class, 'documentavel');
  }

  public function lancamentosFinanceiros(): HasMany
  {
    return $this->hasMany(LancamentoFinanceiro::class);
  }
}
