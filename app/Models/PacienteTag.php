<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PacienteTag extends Model
{
    protected $table = 'paciente_tags';

    protected $fillable = ['paciente_id', 'tag'];

    public function paciente(): BelongsTo
    {
        return $this->belongsTo(Paciente::class);
    }
}
