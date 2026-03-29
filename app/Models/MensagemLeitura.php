<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MensagemLeitura extends Model
{
    protected $table = 'mensagem_leituras';

    protected $fillable = ['mensagem_id', 'user_id', 'lida_em'];

    protected $casts = ['lida_em' => 'datetime'];

    public function mensagem(): BelongsTo
    {
        return $this->belongsTo(Mensagem::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
