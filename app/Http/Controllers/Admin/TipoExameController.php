<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TipoExame;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TipoExameController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'nome'      => ['required', 'string', 'max:100'],
            'categoria' => ['nullable', 'string', 'max:100'],
            'descricao' => ['nullable', 'string'],
        ]);

        $tipo = TipoExame::create($data);
        AuditService::criado($tipo);

        return back()->with('success', 'Tipo de exame criado.');
    }

    public function update(Request $request, TipoExame $tipoExame): RedirectResponse
    {
        $data = $request->validate([
            'nome'      => ['required', 'string', 'max:100'],
            'categoria' => ['nullable', 'string', 'max:100'],
            'descricao' => ['nullable', 'string'],
            'ativo'     => ['boolean'],
        ]);

        $antes = $tipoExame->toArray();
        $tipoExame->update($data);
        AuditService::editado($tipoExame, $antes);

        return back()->with('success', 'Tipo de exame atualizado.');
    }

    public function destroy(TipoExame $tipoExame): RedirectResponse
    {
        AuditService::excluido($tipoExame);
        $tipoExame->delete();

        return back()->with('success', 'Tipo de exame removido.');
    }
}
