<?php

namespace App\Models;

use App\Traits\BelongsToClinica;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Fornecedor extends Model
{
    use BelongsToClinica, SoftDeletes;

    protected $table = 'fornecedores';

    protected $fillable = [
        'clinica_id', 'razao_social', 'nome_fantasia', 'cnpj', 'telefone', 'email', 'site',
        'cep', 'logradouro', 'numero', 'complemento', 'bairro', 'cidade', 'estado',
        'observacoes', 'ativo',
    ];

    protected $casts = ['ativo' => 'boolean'];

    public function clinica(): BelongsTo
    {
        return $this->belongsTo(Clinica::class);
    }

    public function produtos(): HasMany
    {
        return $this->hasMany(Produto::class);
    }
}
