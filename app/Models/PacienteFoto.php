<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PacienteFoto extends Model
{
    protected $table = 'paciente_fotos';

    protected $fillable = ['paciente_id', 'url', 'tamanho_bytes', 'principal'];

    protected $casts = ['principal' => 'boolean'];

    public function paciente(): BelongsTo
    {
        return $this->belongsTo(Paciente::class);
    }
}
