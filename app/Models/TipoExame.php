<?php

namespace App\Models;

use App\Traits\BelongsToClinica;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class TipoExame extends Model
{
    use BelongsToClinica;

    protected $table = 'tipos_exame';

    protected $fillable = ['clinica_id', 'nome', 'categoria', 'descricao', 'ativo'];

    protected $casts = ['ativo' => 'boolean'];

    public function clinica(): BelongsTo
    {
        return $this->belongsTo(Clinica::class);
    }

    public function laboratorios(): BelongsToMany
    {
        return $this->belongsToMany(Laboratorio::class, 'laboratorio_tipos_exame');
    }
}
