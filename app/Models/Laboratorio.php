<?php

namespace App\Models;

use App\Traits\BelongsToClinica;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Laboratorio extends Model
{
    use BelongsToClinica;

    protected $table = 'laboratorios';

    protected $fillable = [
        'clinica_id', 'nome', 'cnpj', 'telefone', 'email',
        'tempo_medio_resultado_dias', 'observacoes', 'ativo',
    ];

    protected $casts = ['ativo' => 'boolean'];

    public function clinica(): BelongsTo
    {
        return $this->belongsTo(Clinica::class);
    }

    public function tiposExame(): BelongsToMany
    {
        return $this->belongsToMany(TipoExame::class, 'laboratorio_tipos_exame');
    }
}
