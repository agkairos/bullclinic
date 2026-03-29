<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Especialidade;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EspecialidadeController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Especialidades/Index', [
            'especialidades' => Especialidade::orderBy('nome')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'nome'      => ['required', 'string', 'max:100'],
            'descricao' => ['nullable', 'string', 'max:255'],
        ]);

        $esp = Especialidade::create($data);
        AuditService::criado($esp);

        return back()->with('success', 'Especialidade criada.');
    }

    public function update(Request $request, Especialidade $especialidade): RedirectResponse
    {
        $data = $request->validate([
            'nome'      => ['required', 'string', 'max:100'],
            'descricao' => ['nullable', 'string', 'max:255'],
            'ativo'     => ['boolean'],
        ]);

        $antes = $especialidade->toArray();
        $especialidade->update($data);
        AuditService::editado($especialidade, $antes);

        return back()->with('success', 'Especialidade atualizada.');
    }

    public function destroy(Especialidade $especialidade): RedirectResponse
    {
        AuditService::excluido($especialidade);
        $especialidade->delete();

        return back()->with('success', 'Especialidade removida.');
    }
}
