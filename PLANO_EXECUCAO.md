# BULLCLINIC — Plano de Execução

> SaaS para clínicas veterinárias | Laravel 12 + Vue 3 + Inertia.js + Tailwind CSS 4
> Arquitetura: Multi-tenant por `clinica_id` | Cor primária: `#00c1b3`
> Atualizado: 2026-03-27

---

## ✅ JÁ CONCLUÍDO

- Migrations completas (14 arquivos, todas rodadas)
- Models completos (todos os módulos)
- Traits, Middlewares (`BelongsToClinica`, `EnsureClinicaActive`, `CheckPermission`), `AuditService`, `ClinicaContext`
- Auth backend + frontend (Login, Register, ForgotPassword, ResetPassword)
- Backend: Clientes, Pacientes, Agenda, FilaEspera, Prontuário (Consulta/Receita/Exame/Procedimento/Vacinação), Estoque, Financeiro, Chat, Dashboard, Admin (Usuários + ClinicaConfig)
- Frontend base: Layout, Dashboard, Agenda (calendário), Em espera, Clientes (lista+drawer+show), Prontuários (lista+paciente+consulta), Estoque, Financeiro, Chat, Admin (Usuários + Config), Documentos (placeholder)
- **Admin cadastros base**: TiposServico, Especialidades, Laboratorios, Horarios (backend + frontend completos)
- **Seeders**: CadastrosBaseSeeder (tipos de serviço, categorias financeiras, categorias de produto por clínica)
- **Pacientes**: `Pacientes/Index.vue` (lista+busca+drawer), `Pacientes/Show.vue` (perfil completo), nav item adicionado

---

## PENDENTE — EM ORDEM DE PRIORIDADE

---

### ~~1. SEEDERS~~ ✅ CONCLUÍDO

### ~~2. ADMIN — Cadastros Base~~ ✅ CONCLUÍDO

### ~~3. PACIENTES~~ ✅ CONCLUÍDO (fotos e tags pendentes de integrar no autocomplete)

**Frontend**

- [x] `Pacientes/Index.vue` — lista com busca + drawer de cadastro/edição
- [x] `Pacientes/Show.vue` — perfil completo (saúde, consultas, vacinas, agendamentos, galeria, tags)
- [x] Upload de foto para S3 (`{clinica_id}/pacientes/fotos/`) — galeria com set principal / remover
- [x] Gerenciar tags inline (adicionar com Enter, remover com ×, auto-sync via API)
- [x] `PacienteFotoController` + `PacienteTagController` + rotas
- [x] Disk `s3_images` configurado em `filesystems.php`
- [x] Integrar busca de paciente (autocomplete) no modal de novo agendamento — `GET /pacientes/buscar?q=`

---

### ~~4. AGENDA~~ ✅ CONCLUÍDO

- [x] Drawer de detalhes do agendamento — ver dados + transições de status + link prontuário
- [x] Modal de novo agendamento com autocomplete de paciente
- [x] Filtro por veterinário (pills no header, integrado com URL)
- [x] Indicador visual de urgência nos cards (ring vermelho + ícone raio)
- [x] Slots livres via `DisponibilidadeService` exibidos no modal
- [x] Aba "Relatórios" — cards resumo, gráfico de barras por dia, taxa por veterinário
- [x] `DisponibilidadeService` + `GET /agendamentos/disponibilidade`

---

### ~~5. PRONTUÁRIO~~ ✅ CONCLUÍDO

- [x] `ProntuarioPdfController` — PDF do prontuário completo + PDF da receita (DomPDF)
- [x] Views Blade: `pdf/prontuario.blade.php`, `pdf/receita.blade.php`
- [x] Rotas: `GET /prontuarios/paciente/{id}/pdf`, `GET /receitas/{id}/pdf`
- [x] Modal de procedimento (tipo, descrição, status, valor) — criar + editar + excluir
- [x] Modal de vacinação (nome, fabricante, lote, data, próxima dose, obs)
- [x] Upload de resultado de exame inline (PDF/imagem → S3)
- [x] Alertas de vencimento de vacina (vermelho = vencida, âmbar = vence em 30 dias)
- [x] Sidebar da consulta com foto do paciente + alerta de alergias em destaque

---

### ~~6. ESTOQUE — Detalhamento~~ ✅ CONCLUÍDO

**Backend**

- [x] `FornecedorController` — CRUD fornecedores

**Frontend**

- [x] Drawer de edição de produto (ao clicar na linha)
- [x] `Estoque/Movimentacoes/Index.vue` — histórico de entradas/saídas com filtros
- [x] Drawer de nova movimentação (tipo: entrada/saída/ajuste, produto, quantidade, valor)
- [x] `Estoque/Fornecedores/Index.vue` — lista + drawer de fornecedor
- [x] Alertas visuais de estoque crítico no Dashboard

---

### ~~7. FINANCEIRO~~ ✅ CONCLUÍDO

**Frontend**

- [x] Drawer de edição de lançamento (ao clicar na linha)
- [x] Botão "Marcar como pago" direto na tabela
- [x] Filtro por status na tabela
- [x] Gráfico simples de receitas vs despesas (bar chart — últimos 6 meses)
- [x] Card "A pagar" no resumo

**Backend**

- [x] `CategoriaFinanceiraController` — CRUD categorias financeiras
- [x] Automação: gerar receita automaticamente ao finalizar consulta (procedimentos realizados)

---

### ~~8. CHAT~~ ✅ CONCLUÍDO

**Backend**

- [x] Broadcasting Event `NovaMensagemEvent` (Laravel Reverb) — canal `conversa.{id}`, `ShouldBroadcastNow`
- [x] Broadcasting Event `UsuarioDigitandoEvent`
- [x] `routes/channels.php` — autorização canal privado `conversa.{conversaId}`
- [x] `MensagemController::digitando()` — broadcast digitando
- [x] `MensagemController::marcarLida()` — `POST /mensagens/{id}/lida`
- [x] `MensagemController::store()` — broadcast `NovaMensagemEvent` + `MensagemLeitura` para remetente
- [x] `resources/js/echo.ts` — configura `window.Echo` com Reverb

**Frontend**

- [x] WebSocket real-time via `window.Echo.private('conversa.{id}').listen('.nova-mensagem', ...)`
- [x] Indicador "Digitando..." em tempo real com debounce 3s
- [x] Indicador de leitura (✓✓ lido) — `MensagemLeitura` por destinatário
- [x] Gravação e envio de áudio (MediaRecorder API → multipart/form-data)
- [x] Player de áudio nos balões de mensagem com controle de posição
- [x] Marcar conversa como lida ao abrir
- [x] Scroll automático para última mensagem via `nextTick`
- [x] Optimistic UI — mensagem adicionada localmente antes da resposta do servidor
- [x] `onUnmounted` — `Echo.leave(canal)` para liberar recursos

---

### ~~9. DOCUMENTOS~~ ✅ CONCLUÍDO

**Backend**

- [x] `DocumentoController` — presigned URL S3, store metadados, delete, listagem polimórfica com paginação
- [x] Validação de limite de storage no presigned-url (sem trafegar o arquivo pelo servidor)
- [x] Atualização de `limite_storage_usado_gb` ao salvar/excluir

**Frontend**

- [x] `Documentos/Index.vue` — lista com filtros (busca + categoria) + paginação
- [x] Upload via presigned URL (browser → S3 direto, sem passar pelo servidor)
- [x] Drag & drop + seletor de arquivo
- [x] Fila de upload com progresso real por arquivo (XHR `onprogress`)
- [x] Upload paralelo em lotes de 3
- [x] Barra de uso de storage com alerta quando ≥ 90%
- [x] Preview de PDF (iframe) e imagem inline no modal
- [x] Associação polimórfica (paciente/consulta/cliente) já modelada no backend

---

### ~~10. DASHBOARD — Detalhamento~~ ✅ CONCLUÍDO

**Frontend**

- [x] Gráfico de atendimentos por período (últimos 7 dias) — ApexCharts area chart
- [x] Gráfico de receitas vs despesas (últimos 30 dias) — ApexCharts area chart
- [x] Card de taxa de comparecimento — ApexCharts radial chart
- [x] Lista de agendamentos de hoje (sidebar lateral)
- [x] Produtos com estoque crítico (mini-lista com alertas)
- [x] Próximas vacinações a vencer (próximos 30 dias)
- [x] KPI Cards redesenhados (4 cards: consultas, pacientes, em atendimento, receita)
- [x] Design moderno e atraente com hover effects e gradientes

**Backend**

- [x] Dados para gráfico de atendimentos (últimos 7 dias)
- [x] Dados para gráfico financeiro (últimos 30 dias)
- [x] Lista de próximas vacinações (próximos 30 dias)

---

### ~~11. PLANOS E ASSINATURA~~ ✅ CONCLUÍDO

**Backend**

- [x] `AssinaturaController` — checkout Stripe (sessão hosted), checkout PIX (Woovi/OpenPix), portal Stripe, cancelamento
- [x] `StripeWebhookController` — checkout.completed, invoice.paid/failed, subscription.deleted/updated
- [x] `WooviWebhookController` — OPENPIX:CHARGE_COMPLETED com verificação de assinatura
- [x] Webhooks excluídos do CSRF (`bootstrap/app.php`)
- [x] `config/services.php` — stripe + woovi configurados

**Frontend**

- [x] `Planos/Index.vue` — comparativo de planos (4 cards) + toggle mensal/anual + economia anual
- [x] Modal PIX com QR code + código copia e cola + botão copiar
- [x] Histórico de faturas Stripe (aba separada)
- [x] Botão "Gerenciar assinatura" → Stripe Customer Portal
- [x] Nav item "Planos" com ícone credit-card

---

### ~~12. COMPONENTES GLOBAIS~~ ✅ CONCLUÍDO

- [x] `useCep.ts` — busca ViaCEP no blur do campo CEP, preenche logradouro/bairro/cidade/estado; integrado em `Clientes/Form.vue` e `Admin/ClinicaConfig.vue`
- [x] `AppToast.vue` + `useToast.ts` — toasts globais com slide-in, auto-dismiss, tipos success/error/warning/info; substituiu flash messages estáticas do AppLayout; lê `$page.props.flash` automaticamente
- [x] `AppConfirm.vue` + `useConfirm.ts` — dialog de confirmação com Promise, suporte a `danger`, substituível em qualquer `confirm()` nativo
- [x] `usePermission.ts` — `can(permissao)` / `canAny(...)` lendo `auth.permissions` do Inertia; `is_super_admin` bypass automático; permissions compartilhadas via `HandleInertiaRequests`
- [x] `StorageIndicator.vue` — barra mini de storage na sidebar do AppLayout, link para /documentos
- [x] `EnsureUniqueSession` middleware — session_token gravado no login, verifica por request; migration `add_session_token_to_users` rodada; registrado em `unique.session` aplicado no grupo `auth`

---

### ~~13. QUALIDADE E ENTREGA~~ ✅ CONCLUÍDO

- [ ] Variável `APP_URL` e `ASSET_URL` configuradas para produção *(configurar no .env do servidor)*
- [x] Laravel Reverb configurado via `reverb:install` + variáveis `REVERB_*` no .env
- [x] Policies criadas: `MensagemPolicy`, `DocumentoPolicy`, `LancamentoFinanceiroPolicy`
- [x] Policies registradas no `AppServiceProvider` via `Gate::policy()`
- [x] `Gate::authorize()` aplicado em `MensagemController::destroy()` e `DocumentoController::destroy()`
- [x] Rate limiting nas rotas de auth: `throttle:auth` (5/min) no login e registro
- [x] Rate limiting nas rotas de senha: `throttle:password-reset` (10/min)
- [x] `RateLimiter::for()` configurado em `bootstrap/app.php`
- [x] Proteção CSRF em todos os fetch() manuais — revisado, 100% coberto

---

### 14. REESTRUTURAÇÃO MULTI-UNIDADES (Conta Master)

> Conforme DOCUMENTACAO_PROJETO.md: uma assinatura gerencia N unidades. O plano define o limite de unidades.
> Hoje a assinatura fica na clínica — precisa migrar para uma camada `Conta` acima das clínicas.

#### Modelo final

```
Conta (empresa/dono)
 ├── plano_id, stripe_*, woovi_*, status_pagamento, data_vencimento
 ├── Unidade A  (clinica_id: 1)
 └── Unidade B  (clinica_id: 2)
```

---

#### Backend — Banco de dados

- [x] Migration `create_contas_table` — `nome`, `email`, `cnpj`, `plano_id`, Stripe, Woovi, `status_pagamento`, datas, `ativa`
- [x] Migration `add_conta_to_clinicas_and_users` — FK `conta_id` em `clinicas`, FK `conta_ativa_id` em `users`
- [x] `planos` já possui `limite_clinicas` e `clinicas_ilimitadas` — nenhuma migration adicional necessária

#### Backend — Models

- [x] `Conta` model — `hasMany(Clinica)`, `belongsTo(Plano)`, `estaAtiva()`, `podeAdicionarUnidade()`
- [x] `Clinica` model — `belongsTo(Conta)`, campos Stripe/Woovi/status removidos do `$fillable`, `estaAtiva()` delega para `Conta`
- [x] `User` model — `conta_ativa_id` em `$fillable`, relação `contaAtiva()`

#### Backend — Autenticação e Contexto

- [x] `EnsureClinicaActive` middleware — verifica `$clinica->conta->estaAtiva()` em vez de campos diretos da clínica
- [x] `ClinicaSelectorController` — valida que `clinica->conta_id === user->conta_ativa_id`
- [x] `HandleInertiaRequests` — `load('contaAtiva.plano')`, `clinicas` filtradas por `conta_ativa_id`

#### Backend — Controllers

- [x] `ContaController` — listar, criar (valida limite), editar e ativar/desativar unidades
- [x] `AssinaturaController` — opera em `Conta` via `contaAtiva()`; Stripe Customer/Subscription na conta
- [x] `StripeWebhookController` — `Conta::where('stripe_customer_id', ...)`, metadata usa `conta_id`
- [x] `WooviWebhookController` — `Conta::find($contaId)`, `additionalInfo` usa `conta_id`
- [x] `RegisterController` — cria `Conta` + primeira `Clinica` + seta `conta_ativa_id` no user

#### Backend — Rotas

- [x] `GET /conta/unidades` — listar unidades
- [x] `POST /conta/unidades` — criar (valida limite do plano)
- [x] `PUT /conta/unidades/{clinica}` — editar
- [x] `PATCH /conta/unidades/{clinica}/ativar` — ativar/desativar

---

#### Frontend

- [x] `Admin/Unidades/Index.vue` — cards de unidades, drawer criar/editar, toggle ativar/desativar, alerta de limite
- [x] `AppLayout.vue` — nav item "Unidades", ícone `git-branch`, `currentRoute` atualizado, `clinicas` já filtradas pela conta
- [x] `Planos/Index.vue` — prop renomeado de `clinica` → `conta`; exibe `total_unidades / limite_unidades`

---

#### Impacto em arquivos existentes

| Arquivo | Status |
|---|---|
| `clinicas` (migration) | ✅ `conta_id` adicionado |
| `Clinica` model | ✅ `belongsTo(Conta)`, campos assinatura removidos do fillable |
| `User` model | ✅ `conta_ativa_id` + relação `contaAtiva()` |
| `EnsureClinicaActive` | ✅ Verifica `conta->estaAtiva()` |
| `AssinaturaController` | ✅ Opera em `Conta` |
| `StripeWebhookController` | ✅ Busca `Conta` |
| `WooviWebhookController` | ✅ Busca `Conta` |
| `HandleInertiaRequests` | ✅ Filtra clínicas por conta |
| `ClinicaSelectorController` | ✅ Valida `conta_id` |
| `RegisterController` | ✅ Cria `Conta` + `Clinica` |
| `Planos/Index.vue` | ⚠️ Pendente atualizar prop `clinica` → `conta` |

---

## RESUMO DE PROGRESSO

```
Fase 1 — Migrations    ████████████████████  100%
Fase 2 — Backend       ████████████████████  100%
Fase 3 — Frontend      ████████████████████  100%
Qualidade e Segurança  ████████████████████  100%
Multi-Unidades (Conta) ████████████████████  100%
```
