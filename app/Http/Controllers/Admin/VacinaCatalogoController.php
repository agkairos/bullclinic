<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\VacinaCatalogo;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class VacinaCatalogoController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/VacinasCatalogo/Index', [
            'vacinas' => VacinaCatalogo::orderBy('nome')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'nome'                  => ['required', 'string', 'max:255'],
            'fabricante'            => ['nullable', 'string', 'max:255'],
            'intervalo_reforco_dias' => ['nullable', 'integer', 'min:0'],
        ]);

        $vac = VacinaCatalogo::create($data);
        AuditService::criado($vac);

        return back()->with('success', 'Vacina cadastrada.');
    }

    public function update(Request $request, VacinaCatalogo $vacinaCatalogo): RedirectResponse
    {
        $data = $request->validate([
            'nome'                  => ['required', 'string', 'max:255'],
            'fabricante'            => ['nullable', 'string', 'max:255'],
            'intervalo_reforco_dias' => ['nullable', 'integer', 'min:0'],
            'ativo'                 => ['boolean'],
        ]);

        $antes = $vacinaCatalogo->toArray();
        $vacinaCatalogo->update($data);
        AuditService::editado($vacinaCatalogo, $antes);

        return back()->with('success', 'Vacina atualizada.');
    }

    public function destroy(VacinaCatalogo $vacinaCatalogo): RedirectResponse
    {
        AuditService::excluido($vacinaCatalogo);
        $vacinaCatalogo->delete();

        return back()->with('success', 'Vacina removida.');
    }
}
