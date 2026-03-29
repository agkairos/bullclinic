<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use App\Models\UserClinica;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function index(Request $request): Response
    {
        $clinicaId = $request->user()->clinica_ativa_id;

        $usuarios = User::whereHas('userClinicas', fn($q) => $q->where('clinica_id', $clinicaId))
            ->with(['userClinicas' => fn($q) => $q->where('clinica_id', $clinicaId)->with('role')])
            ->when($request->search, fn($q) => $q->where('name', 'like', "%{$request->search}%")
                ->orWhere('email', 'like', "%{$request->search}%"))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        $roles = Role::where(fn($q) => $q->whereNull('clinica_id')->orWhere('clinica_id', $clinicaId))
            ->orderBy('display_name')
            ->get(['id', 'nome', 'display_name']);

        return Inertia::render('Admin/Usuarios/Index', [
            'usuarios' => $usuarios,
            'roles' => $roles,
            'filters' => $request->only('search'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users'],
            'password' => ['required', 'min:8'],
            'role_id' => ['required', 'exists:roles,id'],
            'crmv' => ['nullable', 'string', 'max:20'],
            'cargo' => ['nullable', 'string', 'max:100'],
        ]);

        $clinicaId = $request->user()->clinica_ativa_id;

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'crmv' => $request->crmv,
            'cargo' => $request->cargo,
            'clinica_ativa_id' => $clinicaId,
        ]);

        UserClinica::create([
            'user_id' => $user->id,
            'clinica_id' => $clinicaId,
            'role_id' => $request->role_id,
            'ativo' => true,
        ]);

        AuditService::criado($user);

        return back()->with('success', 'Usuário criado.');
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'role_id' => ['required', 'exists:roles,id'],
            'crmv' => ['nullable', 'string', 'max:20'],
            'cargo' => ['nullable', 'string', 'max:100'],
            'ativo' => ['boolean'],
        ]);

        $clinicaId = $request->user()->clinica_ativa_id;

        $antes = $user->toArray();
        $user->update([
            'name' => $request->name,
            'crmv' => $request->crmv,
            'cargo' => $request->cargo,
        ]);

        UserClinica::where('user_id', $user->id)
            ->where('clinica_id', $clinicaId)
            ->update([
                'role_id' => $request->role_id,
                'ativo' => $request->boolean('ativo'),
            ]);

        AuditService::editado($user, $antes);

        return back()->with('success', 'Usuário atualizado.');
    }
}
