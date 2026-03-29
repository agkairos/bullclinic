<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResultadoExame extends Model
{
    protected $table = 'resultados_exames';

    protected $fillable = [
        'exame_solicitado_id', 'arquivo_url', 'arquivo_tamanho_bytes',
        'interpretacao', 'recebido_em',
    ];

    protected $casts = ['recebido_em' => 'datetime'];

    public function exame(): BelongsTo
    {
        return $this->belongsTo(ExameSolicitado::class, 'exame_solicitado_id');
    }
}
