<?php

namespace App\Models;

use App\Traits\BelongsToClinica;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProcedimentoCatalogo extends Model
{
    use BelongsToClinica;

    protected $table = 'procedimentos_catalogo';

    protected $fillable = ['clinica_id', 'nome', 'descricao', 'valor_padrao', 'ativo'];

    protected $casts = [
        'valor_padrao' => 'decimal:2',
        'ativo'        => 'boolean',
    ];

    public function clinica(): BelongsTo
    {
        return $this->belongsTo(Clinica::class);
    }
}
