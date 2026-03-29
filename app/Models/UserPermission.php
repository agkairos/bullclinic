<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserPermission extends Model
{
    protected $table = 'user_permissions';

    protected $fillable = ['user_id', 'clinica_id', 'permission_id', 'concedida'];

    protected $casts = ['concedida' => 'boolean'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function clinica(): BelongsTo
    {
        return $this->belongsTo(Clinica::class);
    }

    public function permission(): BelongsTo
    {
        return $this->belongsTo(Permission::class);
    }
}
