<?php

namespace App\Models;

use App\Traits\BelongsToClinica;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Paciente extends Model
{
    use BelongsToClinica, SoftDeletes;

    protected $fillable = [
        'clinica_id', 'cliente_id', 'nome', 'especie', 'raca', 'sexo',
        'data_nascimento', 'cor', 'peso', 'altura', 'microchip', 'tatuagem',
        'alergias', 'condicoes_especiais', 'restricoes_alimentares',
        'medicamentos_cronicos', 'historico_resumido', 'ativo',
    ];

    protected $casts = [
        'data_nascimento' => 'date',
        'peso' => 'decimal:3',
        'altura' => 'decimal:2',
        'ativo' => 'boolean',
    ];

    public function clinica(): BelongsTo
    {
        return $this->belongsTo(Clinica::class);
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function fotos(): HasMany
    {
        return $this->hasMany(PacienteFoto::class);
    }

    public function tags(): HasMany
    {
        return $this->hasMany(PacienteTag::class);
    }

    public function agendamentos(): HasMany
    {
        return $this->hasMany(Agendamento::class);
    }

    public function consultas(): HasMany
    {
        return $this->hasMany(Consulta::class);
    }

    public function vacinacoes(): HasMany
    {
        return $this->hasMany(Vacinacao::class);
    }

    public function documentos(): MorphMany
    {
        return $this->morphMany(Documento::class, 'documentavel');
    }

    public function fotoPrincipal(): ?PacienteFoto
    {
        return $this->fotos()->where('principal', true)->first()
            ?? $this->fotos()->first();
    }

    public function idadeEmAnos(): ?int
    {
        return $this->data_nascimento?->age;
    }
}
