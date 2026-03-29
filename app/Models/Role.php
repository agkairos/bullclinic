<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    protected $fillable = ['clinica_id', 'nome', 'display_name', 'descricao', 'is_system'];

    protected $casts = ['is_system' => 'boolean'];

    public function clinica(): BelongsTo
    {
        return $this->belongsTo(Clinica::class);
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'role_permissions');
    }

    public function userClinicas(): HasMany
    {
        return $this->hasMany(UserClinica::class);
    }

    public function hasPermission(string $nome): bool
    {
        return $this->permissions->contains('nome', $nome);
    }
}
