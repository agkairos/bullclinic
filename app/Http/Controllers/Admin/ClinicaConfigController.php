<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Clinica;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class ClinicaConfigController extends Controller
{
    public function show(Request $request): Response
    {
        $clinica = $request->user()->clinicaAtiva->load('plano');

        return Inertia::render('Admin/ClinicaConfig', ['clinica' => $clinica]);
    }

    public function update(Request $request): RedirectResponse
    {
        $clinica = $request->user()->clinicaAtiva;

        $data = $request->validate([
            'nome' => ['required', 'string', 'max:255'],
            'cnpj' => ['nullable', 'string', 'max:18'],
            'telefone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email'],
            'cep' => ['nullable', 'string', 'max:9'],
            'logradouro' => ['nullable', 'string', 'max:255'],
            'numero' => ['nullable', 'string', 'max:20'],
            'complemento' => ['nullable', 'string', 'max:100'],
            'bairro' => ['nullable', 'string', 'max:100'],
            'cidade' => ['nullable', 'string', 'max:100'],
            'estado' => ['nullable', 'string', 'max:2'],
            'timezone' => ['nullable', 'string', 'max:50'],
        ]);

        $antes = $clinica->toArray();
        $clinica->update($data);
        AuditService::editado($clinica, $antes);

        return back()->with('success', 'Configurações salvas.');
    }

    public function uploadLogo(Request $request): RedirectResponse
    {
        $request->validate(['logo' => ['required', 'image', 'max:2048']]);

        $clinica = $request->user()->clinicaAtiva;
        $path = $request->file('logo')->store("clinica/{$clinica->id}/logo", 's3_images');

        $clinica->update(['logo' => Storage::disk('s3_images')->url($path)]);

        return back()->with('success', 'Logo atualizada.');
    }
}
