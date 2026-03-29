<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Clinica extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'conta_id', 'plano_id', 'nome', 'slug', 'cnpj', 'telefone', 'email', 'logo',
        'cep', 'logradouro', 'numero', 'complemento', 'bairro', 'cidade', 'estado',
        'timezone', 'ativa', 'limite_storage_usado_gb', 'configs',
    ];

    protected $casts = [
        'ativa' => 'boolean',
        'limite_storage_usado_gb' => 'decimal:4',
        'configs' => 'array',
    ];

    public function conta(): BelongsTo
    {
        return $this->belongsTo(Conta::class);
    }

    public function plano(): BelongsTo
    {
        return $this->belongsTo(Plano::class);
    }

    public function usuarios(): HasMany
    {
        return $this->hasMany(UserClinica::class);
    }

    public function clientes(): HasMany
    {
        return $this->hasMany(Cliente::class);
    }

    public function pacientes(): HasMany
    {
        return $this->hasMany(Paciente::class);
    }

    public function agendamentos(): HasMany
    {
        return $this->hasMany(Agendamento::class);
    }

    public function consultas(): HasMany
    {
        return $this->hasMany(Consulta::class);
    }

    public function estaAtiva(): bool
    {
        return $this->ativa && ($this->conta?->estaAtiva() ?? false);
    }

    public function storageDisponivel(): bool
    {
        $plano = $this->conta?->plano ?? $this->plano;
        return $this->limite_storage_usado_gb < $plano->limite_storage_gb;
    }
}
