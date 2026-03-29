<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConversaMembro extends Model
{
    protected $table = 'conversa_membros';

    protected $fillable = ['conversa_id', 'user_id', 'silenciada', 'entrou_em', 'saiu_em'];

    protected $casts = [
        'silenciada' => 'boolean',
        'entrou_em' => 'datetime',
        'saiu_em' => 'datetime',
    ];

    public function conversa(): BelongsTo
    {
        return $this->belongsTo(Conversa::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
