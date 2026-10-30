<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Services\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ClienteController extends Controller
{
    /** GET /clientes/buscar?q=João — autocomplete JSON */
    public function buscar(Request $request): JsonResponse
    {
        $q = trim($request->string('q'));

        if (strlen($q) < 2) {
            return response()->json([]);
        }

        $results = Cliente::query()
            ->where(function ($query) use ($q) {
                $query->where('nome', 'like', "%{$q}%")
                      ->orWhere('cpf', 'like', "%{$q}%")
                      ->orWhere('celular', 'like', "%{$q}%")
                      ->orWhere('email', 'like', "%{$q}%");
            })
            ->select('id', 'nome', 'celular', 'email')
            ->limit(10)
            ->get();

        return response()->json($results);
    }

    public function index(Request $request): Response
    {
        $clientes = Cliente::query()
            ->when($request->search, fn($q) => $q->where(function ($q) use ($request) {
                $q->where('nome', 'like', "%{$request->search}%")
                  ->orWhere('cpf', 'like', "%{$request->search}%")
                  ->orWhere('email', 'like', "%{$request->search}%")
                  ->orWhere('celular', 'like', "%{$request->search}%");
            }))
            ->when($request->status !== null, fn($q) => $q->where('ativo', $request->status))
            ->withCount('pacientes')
            ->orderBy('nome')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Clientes/Index', ['clientes' => $clientes, 'filters' => $request->only('search', 'status')]);
    }

    public function show(Request $request, Cliente $cliente): Response|JsonResponse
    {
        $cliente->load(['pacientes' => fn($q) => $q->withCount('consultas')]);
        if ($request->wantsJson()) {
            return response()->json($cliente);
        }
        return Inertia::render('Clientes/Show', ['cliente' => $cliente]);
    }

    public function create(): Response
    {
        return Inertia::render('Clientes/Form');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'nome' => ['required', 'string', 'max:255'],
            'cpf' => ['nullable', 'string', 'max:14'],
            'rg' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'telefone' => ['nullable', 'string', 'max:20'],
            'celular' => ['nullable', 'string', 'max:20'],
            'cep' => ['nullable', 'string', 'max:9'],
            'logradouro' => ['nullable', 'string', 'max:255'],
            'numero' => ['nullable', 'string', 'max:20'],
            'complemento' => ['nullable', 'string', 'max:100'],
            'bairro' => ['nullable', 'string', 'max:100'],
            'cidade' => ['nullable', 'string', 'max:100'],
            'estado' => ['nullable', 'string', 'max:2'],
            'observacoes' => ['nullable', 'string'],
        ]);

        $cliente = Cliente::create($data);
        AuditService::criado($cliente);

        return redirect()->route('clientes.show', $cliente)->with('success', 'Cliente cadastrado.');
    }

    public function edit(Cliente $cliente): Response
    {
        return Inertia::render('Clientes/Form', ['cliente' => $cliente]);
    }

    public function update(Request $request, Cliente $cliente): RedirectResponse
    {
        $data = $request->validate([
            'nome' => ['required', 'string', 'max:255'],
            'cpf' => ['nullable', 'string', 'max:14'],
            'rg' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'telefone' => ['nullable', 'string', 'max:20'],
            'celular' => ['nullable', 'string', 'max:20'],
            'cep' => ['nullable', 'string', 'max:9'],
            'logradouro' => ['nullable', 'string', 'max:255'],
            'numero' => ['nullable', 'string', 'max:20'],
            'complemento' => ['nullable', 'string', 'max:100'],
            'bairro' => ['nullable', 'string', 'max:100'],
            'cidade' => ['nullable', 'string', 'max:100'],
            'estado' => ['nullable', 'string', 'max:2'],
            'observacoes' => ['nullable', 'string'],
            'ativo' => ['boolean'],
        ]);

        $antes = $cliente->toArray();
        $cliente->update($data);
        AuditService::editado($cliente, $antes);

        return redirect()->route('clientes.show', $cliente)->with('success', 'Cliente atualizado.');
    }

    public function destroy(Cliente $cliente): RedirectResponse
    {
        AuditService::excluido($cliente);
        $cliente->delete();

        return redirect()->route('clientes.index')->with('success', 'Cliente removido.');
    }
}
