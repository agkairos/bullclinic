<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReceitaItem extends Model
{
    protected $table = 'receita_itens';

    protected $fillable = [
        'receita_id', 'medicamento_nome', 'dosagem', 'forma_farmaceutica',
        'quantidade', 'frequencia', 'duracao_tratamento', 'via_administracao',
        'instrucoes_especificas', 'ordem',
    ];

    public function receita(): BelongsTo
    {
        return $this->belongsTo(ReceitaMedica::class, 'receita_id');
    }
}
