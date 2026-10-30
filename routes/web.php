<?php

use App\Http\Controllers\Admin\ClinicaConfigController;
use App\Http\Controllers\Admin\EspecialidadeController;
use App\Http\Controllers\Admin\FrequenciaController;
use App\Http\Controllers\Admin\HorarioAtendimentoController;
use App\Http\Controllers\Admin\LaboratorioController;
use App\Http\Controllers\Admin\ProcedimentoCatalogoController;
use App\Http\Controllers\Admin\TipoExameController;
use App\Http\Controllers\Admin\TipoServicoController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\VacinaCatalogoController;
use App\Http\Controllers\Agenda\AgendamentoController;
use App\Http\Controllers\Agenda\FilaEsperaController;
use App\Http\Controllers\Auth\ConviteController;
use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\Chat\ConversaController;
use App\Http\Controllers\Chat\MensagemController;
use App\Http\Controllers\ContaController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\DocumentoController;
use App\Http\Controllers\ClinicaSelectorController;
use App\Http\Controllers\AssinaturaController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\StripeWebhookController;
use App\Http\Controllers\WooviWebhookController;
use App\Http\Controllers\Estoque\FornecedorController;
use App\Http\Controllers\Estoque\MovimentacaoController;
use App\Http\Controllers\Estoque\ProdutoController;
use App\Http\Controllers\Financeiro\CategoriaFinanceiraController;
use App\Http\Controllers\Financeiro\LancamentoController;
use App\Http\Controllers\PacienteController;
use App\Http\Controllers\PacienteFotoController;
use App\Http\Controllers\PacienteTagController;
use App\Http\Controllers\Prontuario\ConsultaController;
use App\Http\Controllers\Prontuario\ConsultaProdutoController;
use App\Http\Controllers\Prontuario\ExameController;
use App\Http\Controllers\Prontuario\ProcedimentoController;
use App\Http\Controllers\Prontuario\ProntuarioPdfController;
use App\Http\Controllers\Prontuario\ReceitaMedicaController;
use App\Http\Controllers\Prontuario\VacinacaoController;
use Illuminate\Support\Facades\Route;

// ─── Webhooks (sem CSRF, sem auth) ────────────────────────────────────────────
Route::post('/webhooks/stripe', [StripeWebhookController::class, 'handle'])->name('webhooks.stripe');
Route::post('/webhooks/woovi', [WooviWebhookController::class, 'handle'])->name('webhooks.woovi');

// ─── Guest ────────────────────────────────────────────────────────────────────
Route::middleware('guest')->group(function () {
  Route::get('/login', [LoginController::class, 'show'])->name('login');
  Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:auth');

  Route::get('/register', [RegisterController::class, 'show'])->name('register');
  Route::post('/register', [RegisterController::class, 'store'])->middleware('throttle:auth');

  Route::get('/convite/{token}', [ConviteController::class, 'show'])->name('convite.show');
  Route::post('/convite/{token}', [ConviteController::class, 'accept'])->name('convite.accept')->middleware('throttle:6,1');

  Route::get('/forgot-password', [ForgotPasswordController::class, 'show'])->name('password.request');
  Route::post('/forgot-password', [ForgotPasswordController::class, 'store'])->name('password.email')->middleware('throttle:password-reset');

  Route::get('/reset-password/{token}', [ResetPasswordController::class, 'show'])->name('password.reset');
  Route::post('/reset-password', [ResetPasswordController::class, 'store'])->name('password.update')->middleware('throttle:password-reset');
});

// ─── Email Verification ───────────────────────────────────────────────────────
Route::middleware('auth')->group(function () {
  Route::get('/email/verify', [EmailVerificationController::class, 'notice'])->name('verification.notice');
  Route::post('/email/verification-notification', [EmailVerificationController::class, 'resend'])->middleware('throttle:6,1')->name('verification.send');
});

Route::get('/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])->middleware(['signed'])->name('verification.verify');

// ─── Auth (sem verificar clínica ativa) ───────────────────────────────────────
Route::middleware('auth')->group(function () {
  Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');
  Route::post('/clinica/switch', [ClinicaSelectorController::class, 'switch'])->name('clinica.switch');
});

// ─── Auth + Clínica Ativa ─────────────────────────────────────────────────────
Route::middleware(['auth', 'verified', 'unique.session', 'clinica.active'])->group(function () {

  // Dashboard
  Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

  // ── API autocomplete catálogos ─────────────────────────────────────────────
  Route::get('/api/medicamentos', function (\Illuminate\Http\Request $req) {
    $q = $req->query('q');
    return \App\Models\Produto::where('ativo', true)->where('tipo', 'medicamento')
      ->when($q, fn($query) => $query->where('nome', 'like', "%{$q}%"))
      ->orderBy('nome')
      ->get(['id', 'nome', 'forma_farmaceutica', 'dosagem_padrao', 'via_administracao_padrao', 'frequencia_padrao', 'duracao_padrao', 'quantidade_estoque', 'unidade_medida']);
  });
  Route::get('/api/frequencias', function (\Illuminate\Http\Request $req) {
    $q = $req->query('q');
    return \App\Models\Frequencia::where('ativo', true)
      ->when($q, fn($query) => $query->where('descricao', 'like', "%{$q}%"))
      ->orderBy('descricao')
      ->get(['id', 'descricao']);
  });
  Route::get('/api/procedimentos-catalogo', function (\Illuminate\Http\Request $req) {
    $q = $req->query('q');
    return \App\Models\ProcedimentoCatalogo::where('ativo', true)
      ->when($q, fn($query) => $query->where('nome', 'like', "%{$q}%"))
      ->orderBy('nome')
      ->get(['id', 'nome', 'descricao', 'valor_padrao']);
  });
  Route::get('/api/vacinas-catalogo', function (\Illuminate\Http\Request $req) {
    $q = $req->query('q');
    return \App\Models\VacinaCatalogo::where('ativo', true)
      ->when($q, fn($query) => $query->where('nome', 'like', "%{$q}%"))
      ->orderBy('nome')
      ->get(['id', 'nome', 'fabricante', 'intervalo_reforco_dias']);
  });

  // ── Documentos ────────────────────────────────────────────────────────────
  Route::get('/documentos', [DocumentoController::class, 'index'])->name('documentos.index');
  Route::post('/documentos', [DocumentoController::class, 'store'])->name('documentos.store');
  Route::get('/documentos/{documento}/signed-url', [DocumentoController::class, 'signedUrl'])->name('documentos.signedUrl');
  Route::delete('/documentos/{documento}', [DocumentoController::class, 'destroy'])->name('documentos.destroy');

  // ── Clientes ──────────────────────────────────────────────────────────────
  Route::prefix('clientes')->name('clientes.')->group(function () {
    Route::get('/', [ClienteController::class, 'index'])->name('index')->middleware('permission:ver_clientes');
    Route::get('/buscar', [ClienteController::class, 'buscar'])->name('buscar')->middleware(['permission:ver_clientes', 'throttle:60,1']);
    Route::get('/novo', [ClienteController::class, 'create'])->name('create')->middleware('permission:criar_clientes');
    Route::post('/', [ClienteController::class, 'store'])->name('store')->middleware('permission:criar_clientes');
    Route::get('/{cliente}', [ClienteController::class, 'show'])->name('show')->middleware('permission:ver_clientes');
    Route::get('/{cliente}/editar', [ClienteController::class, 'edit'])->name('edit')->middleware('permission:editar_clientes');
    Route::put('/{cliente}', [ClienteController::class, 'update'])->name('update')->middleware('permission:editar_clientes');
    Route::delete('/{cliente}', [ClienteController::class, 'destroy'])->name('destroy')->middleware('permission:excluir_clientes');
  });

  // ── Pacientes ─────────────────────────────────────────────────────────────
  Route::prefix('pacientes')->name('pacientes.')->group(function () {
    Route::get('/', [PacienteController::class, 'index'])->name('index')->middleware('permission:ver_pacientes');
    Route::get('/buscar', [PacienteController::class, 'buscar'])->name('buscar')->middleware(['permission:ver_pacientes', 'throttle:60,1']);
    Route::post('/', [PacienteController::class, 'store'])->name('store')->middleware('permission:criar_pacientes');
    Route::get('/{paciente}', [PacienteController::class, 'show'])->name('show')->middleware('permission:ver_pacientes');
    Route::put('/{paciente}', [PacienteController::class, 'update'])->name('update')->middleware('permission:editar_pacientes');
    Route::delete('/{paciente}', [PacienteController::class, 'destroy'])->name('destroy')->middleware('permission:excluir_pacientes');
    // Fotos
    Route::post('/{paciente}/fotos', [PacienteFotoController::class, 'store'])->name('fotos.store')->middleware('permission:editar_pacientes');
    Route::patch('/{paciente}/fotos/{foto}/principal', [PacienteFotoController::class, 'setPrincipal'])->name('fotos.principal')->middleware('permission:editar_pacientes');
    Route::delete('/{paciente}/fotos/{foto}', [PacienteFotoController::class, 'destroy'])->name('fotos.destroy')->middleware('permission:editar_pacientes');
    // Tags
    Route::post('/{paciente}/tags', [PacienteTagController::class, 'sync'])->name('tags.sync')->middleware('permission:editar_pacientes');
  });

  // ── Agenda ────────────────────────────────────────────────────────────────
  Route::middleware('permission:ver_agenda')->group(function () {
    Route::get('/agenda', [AgendamentoController::class, 'index'])->name('agenda.index');
    Route::get('/em-espera', [FilaEsperaController::class, 'index'])->name('agenda.fila');
  });

  Route::prefix('agendamentos')->name('agendamentos.')->group(function () {
    Route::get('/disponibilidade', [AgendamentoController::class, 'disponibilidade'])->name('disponibilidade')->middleware('permission:ver_agenda');
    Route::post('/', [AgendamentoController::class, 'store'])->name('store')->middleware('permission:criar_agendamentos');
    Route::put('/{agendamento}', [AgendamentoController::class, 'update'])->name('update')->middleware('permission:editar_agendamentos');
    Route::patch('/{agendamento}/status', [AgendamentoController::class, 'atualizarStatus'])->name('status')->middleware('permission:fazer_checkin');
    Route::delete('/{agendamento}', [AgendamentoController::class, 'destroy'])->name('destroy')->middleware('permission:excluir_agendamentos');
  });

  // ── Prontuário ────────────────────────────────────────────────────────────
  Route::middleware('permission:ver_prontuario')->group(function () {
    Route::get('/prontuarios', [ConsultaController::class, 'index'])->name('prontuario.index');
    Route::get('/prontuarios/paciente/{paciente}', [ConsultaController::class, 'show'])->name('prontuario.paciente');
    Route::get('/prontuarios/consulta/{consulta}', [ConsultaController::class, 'consulta'])->name('prontuario.consulta');
    Route::get('/prontuarios/paciente/{paciente}/pdf', [ProntuarioPdfController::class, 'prontuario'])->name('prontuario.pdf');
    Route::get('/receitas/{receita}/pdf', [ProntuarioPdfController::class, 'receita'])->name('receitas.pdf');
  });

  Route::middleware('permission:criar_consultas')->group(function () {
    Route::post('/prontuarios/paciente/{paciente}/abrir', [ConsultaController::class, 'abrir'])->name('prontuario.abrir');
    Route::patch('/prontuarios/consulta/{consulta}/salvar', [ConsultaController::class, 'salvar'])->name('prontuario.salvar');
    Route::patch('/prontuarios/consulta/{consulta}/finalizar', [ConsultaController::class, 'finalizar'])->name('prontuario.finalizar');
  });

  Route::middleware('permission:prescrever_receitas')->group(function () {
    Route::post('/prontuarios/consulta/{consulta}/receitas', [ReceitaMedicaController::class, 'store'])->name('receitas.store');
    Route::delete('/receitas/{receita}', [ReceitaMedicaController::class, 'destroy'])->name('receitas.destroy');
  });

  Route::middleware('permission:solicitar_exames')->group(function () {
    Route::post('/prontuarios/consulta/{consulta}/exames', [ExameController::class, 'store'])->name('exames.store');
    Route::delete('/exames/{exame}', [ExameController::class, 'destroy'])->name('exames.destroy');
  });
  Route::post('/exames/{exame}/resultado', [ExameController::class, 'registrarResultado'])->name('exames.resultado')->middleware('permission:registrar_resultados_exames');

  Route::middleware('permission:registrar_procedimentos')->group(function () {
    Route::post('/prontuarios/consulta/{consulta}/procedimentos', [ProcedimentoController::class, 'store'])->name('procedimentos.store');
    Route::put('/procedimentos/{procedimento}', [ProcedimentoController::class, 'update'])->name('procedimentos.update');
    Route::delete('/procedimentos/{procedimento}', [ProcedimentoController::class, 'destroy'])->name('procedimentos.destroy');

    // Produtos utilizados na consulta
    Route::post('/prontuarios/consulta/{consulta}/produtos', [ConsultaProdutoController::class, 'store'])->name('consulta.produtos.store');
    Route::put('/prontuarios/consulta/{consulta}/produtos/{consultaProduto}', [ConsultaProdutoController::class, 'update'])->name('consulta.produtos.update');
    Route::delete('/prontuarios/consulta/{consulta}/produtos/{consultaProduto}', [ConsultaProdutoController::class, 'destroy'])->name('consulta.produtos.destroy');
  });

  Route::middleware('permission:registrar_vacinas')->group(function () {
    Route::post('/prontuarios/consulta/{consulta}/vacinacoes', [VacinacaoController::class, 'store'])->name('vacinacoes.store');
    Route::delete('/vacinacoes/{vacinacao}', [VacinacaoController::class, 'destroy'])->name('vacinacoes.destroy');
  });

  // ── Estoque ───────────────────────────────────────────────────────────────
  Route::middleware('permission:ver_estoque')->group(function () {
    Route::get('/estoque', [ProdutoController::class, 'index'])->name('estoque.index');
    Route::get('/estoque/movimentacoes', [MovimentacaoController::class, 'index'])->name('estoque.movimentacoes');
    Route::get('/estoque/fornecedores', [FornecedorController::class, 'index'])->name('estoque.fornecedores');
  });
  Route::post('/estoque', [ProdutoController::class, 'store'])->name('estoque.store')->middleware('permission:adicionar_produtos');
  Route::put('/estoque/{produto}', [ProdutoController::class, 'update'])->name('estoque.update')->middleware('permission:editar_produtos');
  Route::delete('/estoque/{produto}', [ProdutoController::class, 'destroy'])->name('estoque.destroy')->middleware('permission:excluir_produtos');
  Route::post('/estoque/movimentacoes', [MovimentacaoController::class, 'store'])->name('estoque.movimentacoes.store')->middleware('permission:movimentar_estoque');
  Route::post('/estoque/fornecedores', [FornecedorController::class, 'store'])->name('estoque.fornecedores.store')->middleware('permission:adicionar_produtos');
  Route::put('/estoque/fornecedores/{fornecedor}', [FornecedorController::class, 'update'])->name('estoque.fornecedores.update')->middleware('permission:editar_produtos');
  Route::delete('/estoque/fornecedores/{fornecedor}', [FornecedorController::class, 'destroy'])->name('estoque.fornecedores.destroy')->middleware('permission:excluir_produtos');

  // ── Financeiro ────────────────────────────────────────────────────────────
  Route::get('/financeiro', [LancamentoController::class, 'index'])->name('financeiro.index')->middleware('permission:ver_financeiro');
  Route::post('/financeiro', [LancamentoController::class, 'store'])->name('financeiro.store')->middleware('permission:criar_receitas');
  Route::put('/financeiro/{lancamento}', [LancamentoController::class, 'update'])->name('financeiro.update')->middleware('permission:editar_lancamentos');
  Route::delete('/financeiro/{lancamento}', [LancamentoController::class, 'destroy'])->name('financeiro.destroy')->middleware('permission:excluir_lancamentos');

  Route::middleware('permission:configurar_clinica')->group(function () {
    Route::get('/financeiro/categorias', [CategoriaFinanceiraController::class, 'index'])->name('financeiro.categorias.index');
    Route::post('/financeiro/categorias', [CategoriaFinanceiraController::class, 'store'])->name('financeiro.categorias.store');
    Route::put('/financeiro/categorias/{categoriaFinanceira}', [CategoriaFinanceiraController::class, 'update'])->name('financeiro.categorias.update');
    Route::delete('/financeiro/categorias/{categoriaFinanceira}', [CategoriaFinanceiraController::class, 'destroy'])->name('financeiro.categorias.destroy');
  });

  // ── Chat ──────────────────────────────────────────────────────────────────
  Route::middleware('permission:acessar_chat')->group(function () {
    Route::get('/conversas', [ConversaController::class, 'index'])->name('chat.index');
    Route::post('/conversas', [ConversaController::class, 'store'])->name('chat.store')->middleware('permission:criar_conversas');
    Route::get('/conversas/{conversa}', [ConversaController::class, 'show'])->name('chat.show');
    Route::put('/conversas/{conversa}', [ConversaController::class, 'update'])->name('chat.update')->middleware('permission:gerenciar_conversas_grupo');
    Route::delete('/conversas/{conversa}', [ConversaController::class, 'destroy'])->name('chat.destroy');
    Route::post('/conversas/{conversa}/mensagens', [MensagemController::class, 'store'])->name('chat.mensagens.store')->middleware('permission:enviar_mensagens');
    Route::post('/conversas/{conversa}/digitando', [MensagemController::class, 'digitando'])->name('chat.digitando');
    Route::delete('/mensagens/{mensagem}', [MensagemController::class, 'destroy'])->name('chat.mensagens.destroy')->middleware('permission:excluir_mensagens');
    Route::patch('/mensagens/{mensagem}/fixar', [MensagemController::class, 'fixar'])->name('chat.mensagens.fixar');
    Route::post('/mensagens/{mensagem}/lida', [MensagemController::class, 'marcarLida'])->name('chat.mensagens.lida');
  });

  // ── Planos e Assinatura ───────────────────────────────────────────────────
  Route::get('/planos', [AssinaturaController::class, 'index'])->name('planos.index');
  Route::post('/planos/checkout/stripe', [AssinaturaController::class, 'checkoutStripe'])->name('planos.checkout.stripe');
  Route::post('/planos/checkout/pix', [AssinaturaController::class, 'checkoutPix'])->name('planos.checkout.pix');
  Route::post('/planos/portal', [AssinaturaController::class, 'portal'])->name('planos.portal');
  Route::post('/planos/cancelar', [AssinaturaController::class, 'cancelar'])->name('planos.cancelar');

  // ── Unidades da conta ─────────────────────────────────────────────────────
  Route::middleware('permission:configurar_clinica')->group(function () {
    Route::get('/conta/unidades', [ContaController::class, 'index'])->name('conta.unidades');
    Route::post('/conta/unidades', [ContaController::class, 'store'])->name('conta.unidades.store');
    Route::put('/conta/unidades/{clinica}', [ContaController::class, 'update'])->name('conta.unidades.update');
    Route::patch('/conta/unidades/{clinica}/ativar', [ContaController::class, 'ativar'])->name('conta.unidades.ativar');
  });

  // ── Perfil do usuário ─────────────────────────────────────────────────────
  Route::get('/conta/perfil', [ContaController::class, 'perfil'])->name('conta.perfil');
  Route::put('/conta/perfil', [ContaController::class, 'atualizarPerfil'])->name('conta.perfil.update');

  // ── Admin ─────────────────────────────────────────────────────────────────
  Route::prefix('admin')->name('admin.')->group(function () {
    Route::middleware('permission:gerenciar_usuarios')->group(function () {
      Route::get('/usuarios', [AdminUserController::class, 'index'])->name('usuarios.index');
      Route::post('/usuarios', [AdminUserController::class, 'store'])->name('usuarios.store');
      Route::put('/usuarios/{user}', [AdminUserController::class, 'update'])->name('usuarios.update');
      Route::post('/usuarios/{user}/reenviar-convite', [AdminUserController::class, 'reenviarConvite'])->name('usuarios.reenviar_convite');
    });

    Route::middleware('permission:configurar_clinica')->group(function () {
      Route::get('/clinica', [ClinicaConfigController::class, 'show'])->name('clinica');
      Route::put('/clinica', [ClinicaConfigController::class, 'update'])->name('clinica.update');
      Route::post('/clinica/logo', [ClinicaConfigController::class, 'uploadLogo'])->name('clinica.logo');

      // Tipos de serviço
      Route::get('/tipos-servico', [TipoServicoController::class, 'index'])->name('tipos-servico.index');
      Route::post('/tipos-servico', [TipoServicoController::class, 'store'])->name('tipos-servico.store');
      Route::put('/tipos-servico/{tipoServico}', [TipoServicoController::class, 'update'])->name('tipos-servico.update');
      Route::delete('/tipos-servico/{tipoServico}', [TipoServicoController::class, 'destroy'])->name('tipos-servico.destroy');

      // Especialidades
      Route::get('/especialidades', [EspecialidadeController::class, 'index'])->name('especialidades.index');
      Route::post('/especialidades', [EspecialidadeController::class, 'store'])->name('especialidades.store');
      Route::put('/especialidades/{especialidade}', [EspecialidadeController::class, 'update'])->name('especialidades.update');
      Route::delete('/especialidades/{especialidade}', [EspecialidadeController::class, 'destroy'])->name('especialidades.destroy');

      // Laboratórios
      Route::get('/laboratorios', [LaboratorioController::class, 'index'])->name('laboratorios.index');
      Route::post('/laboratorios', [LaboratorioController::class, 'store'])->name('laboratorios.store');
      Route::put('/laboratorios/{laboratorio}', [LaboratorioController::class, 'update'])->name('laboratorios.update');
      Route::delete('/laboratorios/{laboratorio}', [LaboratorioController::class, 'destroy'])->name('laboratorios.destroy');

      // Tipos de exame
      Route::post('/tipos-exame', [TipoExameController::class, 'store'])->name('tipos-exame.store');
      Route::put('/tipos-exame/{tipoExame}', [TipoExameController::class, 'update'])->name('tipos-exame.update');
      Route::delete('/tipos-exame/{tipoExame}', [TipoExameController::class, 'destroy'])->name('tipos-exame.destroy');

      // Horários de atendimento
      Route::get('/horarios', [HorarioAtendimentoController::class, 'index'])->name('horarios.index');
      Route::post('/horarios', [HorarioAtendimentoController::class, 'store'])->name('horarios.store');
      Route::delete('/horarios/{horarioAtendimento}', [HorarioAtendimentoController::class, 'destroy'])->name('horarios.destroy');
      Route::post('/horarios/toggle-dia', [HorarioAtendimentoController::class, 'toggleDia'])->name('horarios.toggleDia');


      Route::get('/frequencias', [FrequenciaController::class, 'index'])->name('frequencias.index');
      Route::post('/frequencias', [FrequenciaController::class, 'store'])->name('frequencias.store');
      Route::put('/frequencias/{frequencia}', [FrequenciaController::class, 'update'])->name('frequencias.update');
      Route::delete('/frequencias/{frequencia}', [FrequenciaController::class, 'destroy'])->name('frequencias.destroy');

      Route::get('/procedimentos-catalogo', [ProcedimentoCatalogoController::class, 'index'])->name('procedimentos-catalogo.index');
      Route::post('/procedimentos-catalogo', [ProcedimentoCatalogoController::class, 'store'])->name('procedimentos-catalogo.store');
      Route::put('/procedimentos-catalogo/{procedimentoCatalogo}', [ProcedimentoCatalogoController::class, 'update'])->name('procedimentos-catalogo.update');
      Route::delete('/procedimentos-catalogo/{procedimentoCatalogo}', [ProcedimentoCatalogoController::class, 'destroy'])->name('procedimentos-catalogo.destroy');

      Route::get('/vacinas-catalogo', [VacinaCatalogoController::class, 'index'])->name('vacinas-catalogo.index');
      Route::post('/vacinas-catalogo', [VacinaCatalogoController::class, 'store'])->name('vacinas-catalogo.store');
      Route::put('/vacinas-catalogo/{vacinaCatalogo}', [VacinaCatalogoController::class, 'update'])->name('vacinas-catalogo.update');
      Route::delete('/vacinas-catalogo/{vacinaCatalogo}', [VacinaCatalogoController::class, 'destroy'])->name('vacinas-catalogo.destroy');
    });
  });
});
