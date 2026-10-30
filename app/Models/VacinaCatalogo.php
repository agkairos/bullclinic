<?php

namespace App\Models;

use App\Traits\BelongsToClinica;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VacinaCatalogo extends Model
{
    use BelongsToClinica;

    protected $table = 'vacinas_catalogo';

    protected $fillable = ['clinica_id', 'nome', 'fabricante', 'intervalo_reforco_dias', 'ativo'];

    protected $casts = ['ativo' => 'boolean'];

    public function clinica(): BelongsTo
    {
        return $this->belongsTo(Clinica::class);
    }
}
