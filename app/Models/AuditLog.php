<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AuditLog extends Model
{
    protected $table = 'audit_logs';

    public $timestamps = false;

    protected $fillable = [
        'clinica_id', 'user_id', 'acao',
        'auditable_type', 'auditable_id',
        'dados_antes', 'dados_depois',
        'ip', 'user_agent', 'criado_em',
    ];

    protected $casts = [
        'dados_antes' => 'array',
        'dados_depois' => 'array',
        'criado_em' => 'datetime',
    ];

    public function clinica(): BelongsTo
    {
        return $this->belongsTo(Clinica::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }
}
