<?php

namespace App\Models;

use App\Traits\BelongsToClinica;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Documento extends Model
{
    use BelongsToClinica, SoftDeletes;

    protected $fillable = [
        'clinica_id', 'documentavel_type', 'documentavel_id', 'uploaded_by_id',
        'nome', 'arquivo_url', 'mime_type', 'tamanho_bytes', 'categoria',
    ];

    public function documentavel(): MorphTo
    {
        return $this->morphTo();
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by_id');
    }

    public function clinica(): BelongsTo
    {
        return $this->belongsTo(Clinica::class);
    }
}
