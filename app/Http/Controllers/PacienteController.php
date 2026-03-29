<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Paciente;
use App\Services\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PacienteController extends Controller
{
    public function index(Request $request): Response
    {
        $pacientes = Paciente::query()
            ->when($request->search, fn($q) => $q->where(function ($q) use ($request) {
                $q->where('nome', 'like', "%{$request->search}%")
                  ->orWhere('microchip', 'like', "%{$request->search}%");
            }))
            ->when($request->especie, fn($q) => $q->where('especie', $request->especie))
            ->with(['cliente', 'fotos' => fn($q) => $q->where('principal', true)])
            ->withCount('consultas')
            ->orderBy('nome')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Pacientes/Index', ['pacientes' => $pacientes, 'filters' => $request->only('search', 'especie')]);
    }

    public function show(Paciente $paciente): Response
    {
        $paciente->load([
            'cliente', 'fotos', 'tags',
            'consultas' => fn($q) => $q->latest()->limit(10)->with('veterinario'),
            'vacinacoes' => fn($q) => $q->latest('data_aplicacao')->limit(10),
            'agendamentos' => fn($q) => $q->latest()->limit(5)->with('tipoServico', 'veterinario'),
        ]);

        return Inertia::render('Pacientes/Show', ['paciente' => $paciente]);
    }

    /**
     * Busca rápida (autocomplete) — retorna JSON.
     * GET /pacientes/buscar?q=Rex
     */
    public function buscar(Request $request): JsonResponse
    {
        $q = trim($request->string('q'));

        if (strlen($q) < 2) {
            return response()->json([]);
        }

        $results = Paciente::query()
            ->where(function ($query) use ($q) {
                $query->where('nome', 'like', "%{$q}%")
                      ->orWhere('microchip', 'like', "%{$q}%");
            })
            ->with(['cliente:id,nome', 'fotos' => fn($fq) => $fq->where('principal', true)->select('id', 'paciente_id', 'url')])
            ->select('id', 'nome', 'especie', 'raca', 'cliente_id')
            ->limit(10)
            ->get()
            ->map(fn($p) => [
                'id'         => $p->id,
                'nome'       => $p->nome,
                'especie'    => $p->especie,
                'raca'       => $p->raca,
                'foto_url'   => $p->fotos->first()?->url,
                'cliente'    => ['id' => $p->cliente->id, 'nome' => $p->cliente->nome],
            ]);

        return response()->json($results);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'cliente_id' => ['required', 'exists:clientes,id'],
            'nome' => ['required', 'string', 'max:255'],
            'especie' => ['required', 'string', 'max:100'],
            'raca' => ['nullable', 'string', 'max:100'],
            'sexo' => ['in:macho,femea,nao_informado'],
            'data_nascimento' => ['nullable', 'date'],
            'cor' => ['nullable', 'string', 'max:100'],
            'peso' => ['nullable', 'numeric', 'min:0'],
            'altura' => ['nullable', 'numeric', 'min:0'],
            'microchip' => ['nullable', 'string', 'max:50'],
            'tatuagem' => ['nullable', 'string', 'max:50'],
            'alergias' => ['nullable', 'string'],
            'condicoes_especiais' => ['nullable', 'string'],
            'restricoes_alimentares' => ['nullable', 'string'],
            'medicamentos_cronicos' => ['nullable', 'string'],
        ]);

        $paciente = Paciente::create($data);
        AuditService::criado($paciente);

        return redirect()->route('pacientes.show', $paciente)->with('success', 'Paciente cadastrado.');
    }

    public function update(Request $request, Paciente $paciente): RedirectResponse
    {
        $data = $request->validate([
            'nome' => ['required', 'string', 'max:255'],
            'especie' => ['required', 'string', 'max:100'],
            'raca' => ['nullable', 'string', 'max:100'],
            'sexo' => ['in:macho,femea,nao_informado'],
            'data_nascimento' => ['nullable', 'date'],
            'cor' => ['nullable', 'string', 'max:100'],
            'peso' => ['nullable', 'numeric', 'min:0'],
            'altura' => ['nullable', 'numeric', 'min:0'],
            'microchip' => ['nullable', 'string', 'max:50'],
            'tatuagem' => ['nullable', 'string', 'max:50'],
            'alergias' => ['nullable', 'string'],
            'condicoes_especiais' => ['nullable', 'string'],
            'restricoes_alimentares' => ['nullable', 'string'],
            'medicamentos_cronicos' => ['nullable', 'string'],
            'historico_resumido' => ['nullable', 'string'],
            'ativo' => ['boolean'],
        ]);

        $antes = $paciente->toArray();
        $paciente->update($data);
        AuditService::editado($paciente, $antes);

        return redirect()->route('pacientes.show', $paciente)->with('success', 'Paciente atualizado.');
    }

    public function destroy(Paciente $paciente): RedirectResponse
    {
        AuditService::excluido($paciente);
        $paciente->delete();

        return redirect()->route('pacientes.index')->with('success', 'Paciente removido.');
    }
}
