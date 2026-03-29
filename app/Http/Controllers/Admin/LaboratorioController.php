<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Laboratorio;
use App\Models\TipoExame;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LaboratorioController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Laboratorios/Index', [
            'laboratorios' => Laboratorio::with('tiposExame')->orderBy('nome')->get(),
            'tipos_exame'  => TipoExame::orderBy('nome')->get(['id', 'nome', 'categoria']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'nome'                        => ['required', 'string', 'max:255'],
            'cnpj'                        => ['nullable', 'string', 'max:18'],
            'telefone'                    => ['nullable', 'string', 'max:20'],
            'email'                       => ['nullable', 'email'],
            'tempo_medio_resultado_dias'  => ['nullable', 'integer', 'min:0'],
            'observacoes'                 => ['nullable', 'string'],
            'tipos_exame'                 => ['nullable', 'array'],
            'tipos_exame.*'               => ['exists:tipos_exame,id'],
        ]);

        $tiposExame = $data['tipos_exame'] ?? [];
        unset($data['tipos_exame']);

        $lab = Laboratorio::create($data);
        if ($tiposExame) $lab->tiposExame()->sync($tiposExame);
        AuditService::criado($lab);

        return back()->with('success', 'Laboratório cadastrado.');
    }

    public function update(Request $request, Laboratorio $laboratorio): RedirectResponse
    {
        $data = $request->validate([
            'nome'                        => ['required', 'string', 'max:255'],
            'cnpj'                        => ['nullable', 'string', 'max:18'],
            'telefone'                    => ['nullable', 'string', 'max:20'],
            'email'                       => ['nullable', 'email'],
            'tempo_medio_resultado_dias'  => ['nullable', 'integer', 'min:0'],
            'observacoes'                 => ['nullable', 'string'],
            'ativo'                       => ['boolean'],
            'tipos_exame'                 => ['nullable', 'array'],
            'tipos_exame.*'               => ['exists:tipos_exame,id'],
        ]);

        $tiposExame = $data['tipos_exame'] ?? [];
        unset($data['tipos_exame']);

        $antes = $laboratorio->toArray();
        $laboratorio->update($data);
        $laboratorio->tiposExame()->sync($tiposExame);
        AuditService::editado($laboratorio, $antes);

        return back()->with('success', 'Laboratório atualizado.');
    }

    public function destroy(Laboratorio $laboratorio): RedirectResponse
    {
        AuditService::excluido($laboratorio);
        $laboratorio->delete();

        return back()->with('success', 'Laboratório removido.');
    }
}
