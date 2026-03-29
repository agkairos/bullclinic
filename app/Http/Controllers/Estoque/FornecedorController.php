<?php

namespace App\Http\Controllers\Estoque;

use App\Http\Controllers\Controller;
use App\Models\Fornecedor;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FornecedorController extends Controller
{
  public function index(Request $request): Response
  {
    $fornecedores = Fornecedor::query()
      ->when($request->search, fn($q) => $q->where('razao_social', 'like', "%{$request->search}%")
        ->orWhere('nome_fantasia', 'like', "%{$request->search}%")
        ->orWhere('cnpj', 'like', "%{$request->search}%"))
      ->when($request->has('ativo'), fn($q) => $q->where('ativo', $request->ativo))
      ->withCount('produtos')
      ->orderBy('razao_social')
      ->paginate(20)
      ->withQueryString();

    return Inertia::render('Estoque/Fornecedores/Index', [
      'fornecedores' => $fornecedores,
      'filters' => $request->only('search', 'ativo'),
    ]);
  }

  public function store(Request $request): RedirectResponse
  {
    $data = $request->validate([
      'razao_social' => ['required', 'string', 'max:255'],
      'nome_fantasia' => ['nullable', 'string', 'max:255'],
      'cnpj' => ['nullable', 'string', 'max:18'],
      'telefone' => ['nullable', 'string', 'max:20'],
      'email' => ['nullable', 'email', 'max:255'],
      'site' => ['nullable', 'url', 'max:255'],
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

    $fornecedor = Fornecedor::create($data);
    AuditService::criado($fornecedor);

    return back()->with('success', 'Fornecedor cadastrado com sucesso.');
  }

  public function update(Request $request, Fornecedor $fornecedor): RedirectResponse
  {
    $data = $request->validate([
      'razao_social' => ['required', 'string', 'max:255'],
      'nome_fantasia' => ['nullable', 'string', 'max:255'],
      'cnpj' => ['nullable', 'string', 'max:18'],
      'telefone' => ['nullable', 'string', 'max:20'],
      'email' => ['nullable', 'email', 'max:255'],
      'site' => ['nullable', 'url', 'max:255'],
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

    $antes = $fornecedor->toArray();
    $fornecedor->update($data);
    AuditService::editado($fornecedor, $antes);

    return back()->with('success', 'Fornecedor atualizado com sucesso.');
  }

  public function destroy(Fornecedor $fornecedor): RedirectResponse
  {
    // Verificar se tem produtos associados
    if ($fornecedor->produtos()->count() > 0) {
      return back()->withErrors([
        'fornecedor' => 'Não é possível excluir um fornecedor com produtos associados.'
      ]);
    }

    AuditService::excluido($fornecedor);
    $fornecedor->delete();

    return back()->with('success', 'Fornecedor removido com sucesso.');
  }
}
