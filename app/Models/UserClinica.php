<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserClinica extends Model
{
    protected $table = 'user_clinicas';

    protected $fillable = ['user_id', 'clinica_id', 'role_id', 'ativo'];

    protected $casts = ['ativo' => 'boolean'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function clinica(): BelongsTo
    {
        return $this->belongsTo(Clinica::class);
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }
}
