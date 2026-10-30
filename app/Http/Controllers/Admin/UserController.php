<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\ConviteUsuario;
use App\Models\Clinica;
use App\Models\Especialidade;
use App\Models\Role;
use App\Models\User;
use App\Models\UserClinica;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function index(Request $request): Response
    {
        $clinicaId = $request->user()->clinica_ativa_id;

        $usuarios = User::whereHas('userClinicas', fn($q) => $q->where('clinica_id', $clinicaId))
            ->with([
                'userClinicas' => fn($q) => $q->where('clinica_id', $clinicaId)->with('role'),
                'especialidades' => fn($q) => $q->wherePivot('clinica_id', $clinicaId),
            ])
            ->when($request->search, fn($q) => $q->where('name', 'like', "%{$request->search}%")
                ->orWhere('email', 'like', "%{$request->search}%"))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        $roles = Role::where(fn($q) => $q->whereNull('clinica_id')->orWhere('clinica_id', $clinicaId))
            ->orderBy('display_name')
            ->get(['id', 'nome', 'display_name']);

        $especialidades = Especialidade::where('clinica_id', $clinicaId)
            ->where('ativo', true)
            ->orderBy('nome')
            ->get(['id', 'nome']);

        return Inertia::render('Admin/Usuarios/Index', [
            'usuarios'       => $usuarios,
            'roles'          => $roles,
            'especialidades' => $especialidades,
            'filters'        => $request->only('search'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name'                => ['required', 'string', 'max:255'],
            'email'               => ['required', 'email', 'unique:users'],
            'role_id'             => ['required', 'exists:roles,id'],
            'crmv'                => ['nullable', 'string', 'max:20'],
            'cargo'               => ['nullable', 'string', 'max:100'],
            'especialidade_ids'   => ['nullable', 'array'],
            'especialidade_ids.*' => ['exists:especialidades,id'],
        ]);

        $clinicaId = $request->user()->clinica_ativa_id;
        $clinica   = Clinica::find($clinicaId);

        $token = Str::random(64);

        $user = User::create([
            'name'             => $request->name,
            'email'            => $request->email,
            'password'         => Hash::make(Str::random(32)), // senha temporária aleatória
            'crmv'             => $request->crmv,
            'cargo'            => $request->cargo,
            'clinica_ativa_id' => $clinicaId,
            'ativo'            => false, // inativo até aceitar o convite
            'invite_token'     => $token,
            'invite_sent_at'   => now(),
        ]);

        UserClinica::create([
            'user_id'    => $user->id,
            'clinica_id' => $clinicaId,
            'role_id'    => $request->role_id,
            'ativo'      => false,
        ]);

        $this->syncEspecialidades($user, $clinicaId, $request->especialidade_ids ?? []);

        Mail::to($user->email)->send(new ConviteUsuario($user, $clinica?->nome ?? 'BullClinic'));

        AuditService::criado($user);

        return back()->with('success', 'Convite enviado para ' . $user->email);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $request->validate([
            'name'                => ['required', 'string', 'max:255'],
            'role_id'             => ['required', 'exists:roles,id'],
            'crmv'                => ['nullable', 'string', 'max:20'],
            'cargo'               => ['nullable', 'string', 'max:100'],
            'ativo'               => ['boolean'],
            'especialidade_ids'   => ['nullable', 'array'],
            'especialidade_ids.*' => ['exists:especialidades,id'],
        ]);

        $clinicaId = $request->user()->clinica_ativa_id;

        $antes = $user->toArray();
        $user->update([
            'name'  => $request->name,
            'crmv'  => $request->crmv,
            'cargo' => $request->cargo,
        ]);

        UserClinica::where('user_id', $user->id)
            ->where('clinica_id', $clinicaId)
            ->update([
                'role_id' => $request->role_id,
                'ativo'   => $request->boolean('ativo'),
            ]);

        $this->syncEspecialidades($user, $clinicaId, $request->especialidade_ids ?? []);

        AuditService::editado($user, $antes);

        return back()->with('success', 'Usuário atualizado.');
    }

    /** Reenviar convite */
    public function reenviarConvite(Request $request, User $user): RedirectResponse
    {
        if ($user->invite_accepted_at) {
            return back()->with('error', 'Este usuário já aceitou o convite.');
        }

        $clinicaId = $request->user()->clinica_ativa_id;
        $clinica   = Clinica::find($clinicaId);

        $token = Str::random(64);
        $user->update(['invite_token' => $token, 'invite_sent_at' => now()]);

        Mail::to($user->email)->send(new ConviteUsuario($user, $clinica?->nome ?? 'BullClinic'));

        return back()->with('success', 'Convite reenviado para ' . $user->email);
    }

    private function syncEspecialidades(User $user, int $clinicaId, array $ids): void
    {
        DB::table('user_especialidades')
            ->where('user_id', $user->id)
            ->where('clinica_id', $clinicaId)
            ->delete();

        foreach ($ids as $espId) {
            DB::table('user_especialidades')->insert([
                'user_id'          => $user->id,
                'especialidade_id' => $espId,
                'clinica_id'       => $clinicaId,
                'created_at'       => now(),
                'updated_at'       => now(),
            ]);
        }
    }
}
