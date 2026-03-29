<?php

namespace App\Models;

use App\Traits\BelongsToClinica;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TipoMovimentacao extends Model
{
    use BelongsToClinica;

    protected $table = 'tipos_movimentacao';

    protected $fillable = ['clinica_id', 'nome', 'tipo_base', 'descricao', 'ativo'];

    protected $casts = ['ativo' => 'boolean'];

    public function clinica(): BelongsTo
    {
        return $this->belongsTo(Clinica::class);
    }
}
