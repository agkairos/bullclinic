<?php

namespace App\Models;

use App\Notifications\VerifyEmailNotification;
use Database\Factories\UserFactory;
use App\Models\Especialidade;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'name', 'email', 'password',
        'conta_ativa_id', 'clinica_ativa_id', 'avatar', 'crmv', 'cargo', 'ativo', 'ultimo_acesso_em', 'session_token',
        'invite_token', 'invite_sent_at', 'invite_accepted_at', 'email_verified_at',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'ultimo_acesso_em' => 'datetime',
            'invite_sent_at' => 'datetime',
            'invite_accepted_at' => 'datetime',
            'password' => 'hashed',
            'ativo' => 'boolean',
        ];
    }

    public function contaAtiva(): BelongsTo
    {
        return $this->belongsTo(Conta::class, 'conta_ativa_id');
    }

    public function clinicaAtiva(): BelongsTo
    {
        return $this->belongsTo(Clinica::class, 'clinica_ativa_id');
    }

    public function clinicas(): BelongsToMany
    {
        return $this->belongsToMany(Clinica::class, 'user_clinicas')
            ->withPivot(['role_id', 'ativo'])
            ->withTimestamps();
    }

    public function userClinicas(): HasMany
    {
        return $this->hasMany(UserClinica::class);
    }

    public function permissoesCustomizadas(): HasMany
    {
        return $this->hasMany(UserPermission::class);
    }

    public function agendamentos(): HasMany
    {
        return $this->hasMany(Agendamento::class, 'veterinario_id');
    }

    public function consultas(): HasMany
    {
        return $this->hasMany(Consulta::class, 'veterinario_id');
    }

    public function horarios(): HasMany
    {
        return $this->hasMany(HorarioAtendimento::class);
    }

    public function mensagens(): HasMany
    {
        return $this->hasMany(Mensagem::class);
    }

    public function especialidades(): BelongsToMany
    {
        return $this->belongsToMany(Especialidade::class, 'user_especialidades')
            ->withPivot('clinica_id')
            ->withTimestamps();
    }

    public function especialidadesNaClinica(int $clinicaId): BelongsToMany
    {
        return $this->belongsToMany(Especialidade::class, 'user_especialidades')
            ->withPivot('clinica_id')
            ->wherePivot('clinica_id', $clinicaId);
    }

    public function roleNaClinica(int $clinicaId): ?Role
    {
        $uc = $this->userClinicas()
            ->where('clinica_id', $clinicaId)
            ->with('role.permissions')
            ->first();

        return $uc?->role;
    }

    public function podeNaClinica(string $permissao, int $clinicaId): bool
    {
        $custom = $this->permissoesCustomizadas()
            ->where('clinica_id', $clinicaId)
            ->whereHas('permission', fn($q) => $q->where('nome', $permissao))
            ->first();

        if ($custom) {
            return $custom->concedida;
        }

        $role = $this->roleNaClinica($clinicaId);
        return $role?->hasPermission($permissao) ?? false;
    }

    public function pode(string $permissao): bool
    {
        return $this->podeNaClinica($permissao, $this->clinica_ativa_id);
    }

    public function isSuperAdmin(): bool
    {
        $role = $this->roleNaClinica($this->clinica_ativa_id);
        return $role?->nome === 'super_admin';
    }

    /** Sobrescreve para usar nossa notification personalizada */
    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new VerifyEmailNotification());
    }
}
