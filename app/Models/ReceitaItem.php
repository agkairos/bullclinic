<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReceitaItem extends Model
{
  protected $table = 'receita_itens';

  protected $fillable = [
    'receita_id',
    'produto_id',
    'medicamento_nome',
    'dosagem',
    'forma_farmaceutica',
    'quantidade',
    'frequencia',
    'duracao_tratamento',
    'via_administracao',
    'instrucoes_especificas',
    'ordem',
    'vendido_pela_clinica',
  ];

  protected $casts = [
    'vendido_pela_clinica' => 'boolean',
  ];

  public function receita(): BelongsTo
  {
    return $this->belongsTo(ReceitaMedica::class, 'receita_id');
  }

  public function produto(): BelongsTo
  {
    return $this->belongsTo(Produto::class);
  }
}
