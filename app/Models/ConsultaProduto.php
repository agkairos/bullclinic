<?php

namespace App\Models;

use App\Traits\BelongsToClinica;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ConsultaProduto extends Model
{
  use BelongsToClinica, SoftDeletes;

  protected $table = 'consulta_produtos';

  protected $fillable = [
    'clinica_id',
    'consulta_id',
    'produto_id',
    'quantidade',
    'valor_unitario',
    'aplicado_na_clinica',
    'observacoes',
    'usuario_id',
  ];

  protected $casts = [
    'quantidade' => 'decimal:3',
    'valor_unitario' => 'decimal:2',
    'subtotal' => 'decimal:2',
    'aplicado_na_clinica' => 'boolean',
  ];

  protected $appends = ['subtotal'];

  // ─── Relacionamentos ─────────────────────────────────────────────────

  public function consulta(): BelongsTo
  {
    return $this->belongsTo(Consulta::class);
  }

  public function produto(): BelongsTo
  {
    return $this->belongsTo(Produto::class);
  }

  public function usuario(): BelongsTo
  {
    return $this->belongsTo(User::class, 'usuario_id');
  }

  // ─── Accessors ───────────────────────────────────────────────────────

  public function getSubtotalAttribute(): float
  {
    return round($this->quantidade * $this->valor_unitario, 2);
  }
}
