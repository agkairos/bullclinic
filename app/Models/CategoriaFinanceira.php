<?php

namespace App\Models;

use App\Traits\BelongsToClinica;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CategoriaFinanceira extends Model
{
    use BelongsToClinica;

    protected $table = 'categorias_financeiras';

    protected $fillable = ['clinica_id', 'nome', 'tipo', 'descricao', 'ativo'];

    protected $casts = ['ativo' => 'boolean'];

    public function clinica(): BelongsTo
    {
        return $this->belongsTo(Clinica::class);
    }

    public function lancamentos(): HasMany
    {
        return $this->hasMany(LancamentoFinanceiro::class, 'categoria_id');
    }
}
