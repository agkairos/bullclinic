<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Frequencia;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FrequenciaController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Frequencias/Index', [
            'frequencias' => Frequencia::orderBy('descricao')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'descricao' => ['required', 'string', 'max:255'],
        ]);

        $freq = Frequencia::create($data);
        AuditService::criado($freq);

        return back()->with('success', 'Frequência cadastrada.');
    }

    public function update(Request $request, Frequencia $frequencia): RedirectResponse
    {
        $data = $request->validate([
            'descricao' => ['required', 'string', 'max:255'],
            'ativo'     => ['boolean'],
        ]);

        $antes = $frequencia->toArray();
        $frequencia->update($data);
        AuditService::editado($frequencia, $antes);

        return back()->with('success', 'Frequência atualizada.');
    }

    public function destroy(Frequencia $frequencia): RedirectResponse
    {
        AuditService::excluido($frequencia);
        $frequencia->delete();

        return back()->with('success', 'Frequência removida.');
    }
}
