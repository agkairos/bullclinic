<?php

namespace App\Models;

use App\Traits\BelongsToClinica;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Cliente extends Model
{
    use BelongsToClinica, SoftDeletes;

    protected $fillable = [
        'clinica_id', 'nome', 'cpf', 'rg', 'email', 'telefone', 'celular',
        'cep', 'logradouro', 'numero', 'complemento', 'bairro', 'cidade', 'estado',
        'observacoes', 'ativo',
    ];

    protected $casts = ['ativo' => 'boolean'];

    public function clinica(): BelongsTo
    {
        return $this->belongsTo(Clinica::class);
    }

    public function pacientes(): HasMany
    {
        return $this->hasMany(Paciente::class);
    }

    public function agendamentos(): HasMany
    {
        return $this->hasMany(Agendamento::class);
    }
}
