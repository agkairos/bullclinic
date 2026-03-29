# 📋 BULLCLINIC - Documentação Completa do Projeto

## 📌 Visão Geral

**Bullclinic** é um **SaaS (Software as a Service)** desenvolvido para **clínicas veterinárias de pequeno e médio porte**, oferecendo uma solução completa para gestão de agendamentos, prontuário eletrônico, controle de estoque, gestão financeira e administração de clínicas. Opção de multi-clinicas (unidades). Cor primária #00c1b3.

### Público-Alvo

- Clínicas veterinárias de pequeno e médio porte
- Veterinários autônomos
- Redes de clínicas veterinárias

---

## 🛠️ Stack Tecnológica

### Backend

- **Framework:** Laravel 12
- **Linguagem:** PHP 8.2+
- **Banco de Dados:** MySQL
- **ORM:** Eloquent
- **Autenticação:** Laravel Sanctum
- **Migrations:** Sistema completo de versionamento do banco
- **Queue:** Laravel Queue (para processamento assíncrono)
- **Redis:** Para cache e sessões
- **Broadcasting:** Laravel Reverb ou Pusher (WebSockets para chat em tempo real)

### Frontend

- **Framework JavaScript:** Vue 3 (Composition API)
- **Integração Backend/Frontend:** Inertia.js 2.0
- **CSS Framework:** Tailwind CSS 4.1
- **Build Tool:** Vite 6.2
- **Componentes UI:** shadcn-vue

### Infraestrutura e Arquitetura

- **Arquitetura:** Multi-tenant (uma clínica = um tenant)
- **Storage:** AWS S3 (via Flysystem)
- **PDF:** DomPDF (geração de relatórios)
- **Pagamentos:** Stripe Integration e woovi (pix)
- **TypeScript:** Suporte completo com tipagem

---

## 🏗️ Arquitetura Multi-Tenant e Multi-Clínicas (Unidades)

### Conceito de Multi-Clínicas

O sistema permite que **uma conta/assinatura gerencie múltiplas clínicas (unidades)** de acordo com o plano contratado:

- **Conta Master:** Uma assinatura pode ter múltiplas clínicas vinculadas
- **Limite por plano:** Cada plano define quantas clínicas podem ser gerenciadas
- **Gestão centralizada:** Usuário admin pode alternar entre suas unidades
- **Dados isolados:** Cada clínica mantém seus dados separados (pacientes, agendamentos, estoque, financeiro)

**Exemplo de uso:**

- Uma rede veterinária com 3 unidades em bairros diferentes
- Cada unidade tem sua própria equipe, pacientes e agenda
- O dono/admin pode alternar entre unidades e visualizar dados consolidados

### Arquitetura Multi-Tenant

- Cada **clínica/unidade** é um **tenant isolado**
- Todos os dados são segregados por `clinica_id`
- Middleware `EnsureClinicaActive` garante isolamento
- Trait `BelongsToClinica` aplicado nos models para scoping automático
- Usuários podem ter acesso a múltiplas clínicas da mesma conta

### Implementação

```php
// Middleware aplicado em todas as rotas autenticadas
Route::middleware(['auth', 'clinica.active', 'single.session'])
```

**Principais recursos:**

- Cada clínica tem seus próprios usuários, pacientes, agendamentos
- Configurações independentes por clínica
- Uma assinatura pode gerenciar múltiplas unidades (limitado pelo plano)
- Auditoria por clínica
- Seletor de unidade para alternar entre clínicas

---

## 📦 Módulos e Funcionalidades

### 1. **Gestão de Clínicas e Unidades** 👨‍⚕️

**Funcionalidades:**

- Cadastro e configuração de clínicas/unidades
- Gestão de múltiplas unidades sob uma mesma conta
- Gestão de planos (Grátis, Básico, Profissional, Empresarial)
- Sistema de assinaturas com Stripe (recorrente) e Woovi (PIX)
- Controle de status de pagamento
- Trial period (período de teste)
- Histórico de alterações
- Webhooks Stripe e Woovi para sincronização
- Seletor de unidade ativa (dropdown no header)
- Dashboard consolidado de todas as unidades (opcional para admin)

**Status da Clínica:**

- `ativa`: Clínica operacional
- `status_pagamento`: pendente, pago, atrasado, cancelado
- `data_vencimento`: Data de vencimento da assinatura
- `data_trial_fim`: Final do período trial

**Gestão de Unidades:**

- Adicionar nova unidade (respeitando limite do plano)
- Configurações independentes por unidade
- Transferir usuários entre unidades
- Relatórios por unidade ou consolidado

---

### 2. **Gestão de Usuários e Permissões** 🔐

**Sistema de Permissões (RBAC):**

- **Roles padrão:**
  - `super_admin`: Acesso total ao sistema
  - `admin`: Administrador da clínica
  - `veterinario`: Veterinário com acesso ao prontuário
  - `recepcionista`: Acesso limitado (agenda, check-in)
  - `personalizado`: Roles customizadas

**Permissões por módulo:**

**Agenda:**

- `ver_agenda`
- `criar_agendamentos`
- `editar_agendamentos`
- `excluir_agendamentos`
- `fazer_checkin`
- `iniciar_atendimento`
- `finalizar_atendimento`
- `configuracoes_agenda`

**Clientes e Pacientes:**

- `ver_clientes`
- `criar_clientes`
- `editar_clientes`
- `excluir_clientes`
- `ver_pacientes`
- `criar_pacientes`
- `editar_pacientes`
- `excluir_pacientes`

**Prontuário:**

- `ver_prontuario`
- `criar_consultas`
- `editar_consultas`
- `excluir_consultas`
- `prescrever_receitas`
- `solicitar_exames`
- `registrar_resultados_exames`
- `registrar_procedimentos`
- `registrar_vacinas`

**Estoque:**

- `ver_estoque`
- `adicionar_produtos`
- `editar_produtos`
- `excluir_produtos`
- `movimentar_estoque`

**Financeiro:**

- `ver_financeiro`
- `criar_receitas`
- `criar_despesas`
- `editar_lancamentos`
- `excluir_lancamentos`
- `gerar_relatorios`

**Administração:**

- `gerenciar_usuarios`
- `gerenciar_roles`
- `gerenciar_permissoes`
- `configurar_clinica`
- `ver_auditoria`
- `gerenciar_unidades` (criar/editar/remover unidades)
- `alternar_entre_unidades` (trocar unidade ativa)
- `ver_relatorio_consolidado` (visualizar dados de todas as unidades)

**Conversas/Chat:**

- `acessar_chat` (visualizar conversas)
- `criar_conversas` (iniciar novas conversas)
- `enviar_mensagens` (enviar texto, emojis, áudios)
- `excluir_mensagens` (apagar suas próprias mensagens)
- `gerenciar_conversas_grupo` (criar e gerenciar grupos)

---

### 3. **Gestão de Clientes** 👥

**Campos principais:**

- Dados pessoais (nome, CPF, RG, email, telefone)
- Endereço completo (CEP (busca automatica viacep), cidade, estado)
- Observações
- Status (ativo/inativo)

**Relacionamentos:**

- Um cliente pode ter vários pacientes (Pets)

---

### 4. **Gestão de Pacientes (Pets)** 🐕🐈

**Informações:**

- Dados básicos (nome, espécie, raça, sexo, data_nascimento)
- Características físicas (cor, peso, altura)
- Identificação (microchip, tatuagem)
- Status de vacinação e vermifugação
- Alergias e condições especiais
- Restrições alimentares
- Medicamentos crônicos
- Histórico médico resumido
- Fotos do animal
- Marcadores (tags personalizadas)

**Relacionamentos:**

- Pertence a um cliente
- Possui histórico de consultas
- Vinculado a agendamentos

---

### 5. **Agenda e Agendamentos** 📅

**Funcionalidades principais:**

- Agendamento de consultas
- Calendário visual
- Confirmação de agendamentos
- Lembretes automáticos
- Configuração de horários por veterinário

**Status do Agendamento:**

- `agendado`: Agendamento criado
- `confirmado`: Cliente confirmou presença
- `aguardando`: Cliente fez check-in (na fila)
- `em_atendimento`: Atendimento iniciado
- `finalizado`: Atendimento concluído
- `cancelado`: Agendamento cancelado

**Informações do Agendamento:**

- Cliente e paciente
- Veterinário responsável
- Tipo de atendimento (consulta, retorno, cirurgia, etc.)
- Data e horário (hora_inicio, hora_fim, duracao_estimada)
- Primeira consulta (boolean)
- Urgência (boolean)
- Observações públicas e internas
- Valor estimado
- Timestamps de cada mudança de status

**Configurações de Veterinário:**

- Horários de atendimento por dia da semana
- Duração padrão das consultas
- Intervalos entre atendimentos
- Intervalos por dia (na segunda o profissional tem intervalo de 13 as 15, mas na sexta de 11 as 12 - precisa ser customizável por dia)
- Especialidades - te cadastro de especialidade por clínica

---

### 6. **Atendimento e Prontuário Eletrônico** 🏥

**Abas de consulta** `Todos os itens de consulta estarem em um unica pagina com tabs para facilitar a gestão`

#### 6.1 Consulta

**Estrutura da Consulta:**

- Vinculada a agendamento (opcional)
- Paciente e veterinário
- Tipo de consulta (primeira consulta, retorno, emergência)
- Anamnese (história clínica)
- Exame físico
- Diagnóstico
- Observações
- Status (em_andamento, finalizada)
- Auto-save automático

#### 6.2 Exames Solicitados

**Anexar documentos | Gestão de Arquivos:**

- Uma forma de importar documentos, cada arquivo precisa salvar o tamanho pois cada clinica terá um limit de storage;

**Informações:**

- Tipo de exame (configurável)
- Laboratório responsável
- Descrição e instruções
- Status (solicitado, em_analise, concluido, cancelado)
- Datas de solicitação, previsão e conclusão
- Resultado do exame (attachment)

#### 6.3 Receitas Médicas

**Estrutura:**

- Veterinário prescritor (com CRMV)
- Paciente
- Lista de medicamentos (ItemReceita)
- Instruções gerais
- Observações

**Itens da Receita:**

- Medicamento (nome, dosagem, forma_farmaceutica)
- Quantidade
- Frequência
- Duração do tratamento
- Via de administração
- Instruções específicas
- Ordem de exibição

#### 6.4 Procedimentos

**Informações:**

- Tipo de procedimento
- Descrição detalhada
- Status (planejado, realizado, cancelado)
- Data de realização
- Veterinário responsável
- Materiais utilizados (relação com estoque)
- Valor cobrado

#### 6.5 Vacinações

**Controle de vacinas:**

- Nome da vacina
- Fabricante e lote
- Data de aplicação
- Próxima dose (data prevista)
- Veterinário aplicador
- Observações e reações adversas

#### 6.6 Resultados de Exames

**Registro de resultados:**

- Vinculado ao exame solicitado
- Arquivo PDF/imagem do resultado
- Interpretação do veterinário
- Data de recebimento

**Geração de PDFs:**

- Impressão de prontuário
- Receitas médicas
- Atestados
- Relatórios

---

### 7. **Estoque e Produtos** 📦

#### 7.1 Produtos

**Informações:**

- Nome comercial
- Código de barras
- Categoria
- Fornecedor
- Unidade de medida
- Quantidade em estoque
- Estoque mínimo
- Preço de custo e venda
- Status (ativo/inativo)

#### 7.2 Categorias de Produtos - Cada clinica terá seu cadastro de categorias de produtos

**Exemplos:**

- Medicamentos
- Vacinas
- Rações
- Acessórios
- Material cirúrgico
- Produtos de higiene

#### 7.3 Fornecedores

**Dados:**

- Razão social e nome fantasia
- CNPJ
- Contatos (telefone, email, site)
- Endereço completo
- Observações

#### 7.4 Movimentações de Estoque

**Tipos de movimentação: Te opção de cadastro de tipo de movimentação (entras e saidas)**

- `entrada`: Compra, doação, devolução
- `saida`: Venda, uso interno, perda
- `ajuste`: Inventário, correção

**Informações:**

- Produto e quantidade
- Tipo e motivo
- Usuário responsável
- Data da movimentação
- Valor unitário
- Paciente (se aplicável - uso em atendimento)

---

### 8. **Financeiro** 💰

#### 8.1 Receitas Financeiras

**Origem das receitas:**

- Consultas finalizadas (automático)
- Procedimentos realizados
- Venda de produtos
- Lançamento manual

**Informações:**

- Descrição
- Valor
- Data de pagamento
- Forma de pagamento (dinheiro, cartão, PIX, etc.)
- Categoria
- Cliente (opcional)
- Status (pago, pendente, atrasado, cancelado)

#### 8.2 Despesas

**Tipos de despesas:**

- Compra de produtos (estoque)
- Salários e encargos
- Aluguel e condomínio
- Água, luz, telefone
- Marketing
- Manutenção
- Impostos

**Informações:**

- Descrição
- Valor
- Data de vencimento e pagamento
- Categoria
- Fornecedor (opcional)
- Recorrente (mensal, anual)
- Status

#### 8.3 Categorias Financeiras - cadastráveis por clinica

**Categorias de receita:**

- Consultas veterinárias
- Procedimentos cirúrgicos
- Vacinas
- Exames laboratoriais
- Venda de produtos

**Categorias de despesa:**

- Pessoal
- Infraestrutura
- Estoque
- Marketing
- Impostos
- Outras

**Dashboards e Relatórios:**

- Receitas vs Despesas (período)
- Fluxo de caixa
- Análise por categoria
- Gráficos de evolução

---

### 9. **Dashboard e Relatórios** 📊

**Indicadores principais (por unidade ativa):**

- Total de agendamentos do dia
- Consultas em andamento
- Fila de espera
- Consultas finalizadas
- Taxa de ocupação
- Receitas do período
- Produtos com estoque baixo
- Últimas atividades

**Visão Consolidada (todas as unidades):**

- Total de consultas de todas as unidades
- Receitas consolidadas
- Comparativo de desempenho entre unidades
- Ranking de unidades por faturamento

**Gráficos:**

- Atendimentos por dia/semana/mês
- Receitas e despesas mensais
- Tipos de atendimento mais comuns
- Veterinários mais produtivos
- Comparativo entre unidades (se mais de uma)

---

### 10. **Administração** ⚙️

#### 10.1 Gestão de Usuários

**Funcionalidades:**

- Criar/editar/inativar usuários
- Atribuir roles
- Configurar permissões customizadas
- Definir horários de atendimento (veterinários)
- Adicionar especialidades (veterinários)
- Controle de sessões únicas

#### 10.2 Gestão de Tipos de Serviço

**Configuração:**

- Tipos de atendimento (consulta, retorno, cirurgia, etc.)
- Preços por tipo
- Duração estimada
- Status (ativo/inativo)

#### 10.3 Tipos de Exames

**Cadastro:**

- Nome do exame
- Categoria
- Descrição
- Laboratórios parceiros

#### 10.4 Laboratórios Parceiros

**Informações:**

- Nome e CNPJ
- Contatos
- Exames realizados
- Tempo médio de resultado

---

### 11. **Conversas/Chat Interno** 💬

**Funcionalidades principais:**

- Sistema de mensagens em tempo real entre profissionais da clínica
- Conversas individuais (1:1) e em grupo
- Envio de mensagens de texto com formatação
- Suporte a emojis
- Gravação e envio de mensagens de áudio
- Indicadores de status (online/offline, digitando...)
- Histórico completo de conversas
- Busca de mensagens
- Notificações de novas mensagens

**Tipos de Conversas:**

- **Individual (1:1):** Conversa privada entre dois profissionais
- **Grupo:** Conversa entre vários membros da equipe
- **Canal da Clínica/Unidade:** Avisos importantes para toda a equipe

**Mensagens:**

**Tipo Texto:**

- Mensagens de texto com suporte a markdown
- Emojis integrados
- Menção a outros usuários (@nome)
- Formatação básica (negrito, itálico)
- Preview de links

**Tipo Áudio:**

- Gravação de áudio diretamente pelo navegador
- Duração máxima configurável (ex: 5 minutos)
- Player de áudio integrado
- Indicador de duração do áudio
- Armazenamento no S3

**Funcionalidades avançadas:**

- Status de leitura (visualizado/não visualizado)
- Indicador "digitando..."
- Status online/offline dos usuários
- Anexar imagens (fotos de exames, documentos)
- Responder mensagens específicas (thread)
- Fixar mensagens importantes
- Silenciar conversas
- Marcar mensagens como importantes

**Notificações:**

- Badge com contador de mensagens não lidas
- Notificação browser (desktop)
- Som de notificação (opcional)
- Lista de conversas ordenada por mais recente

**Relacionamentos:**

- Isolado por `clinica_id`
- Usuários podem participar de múltiplas conversas
- Histórico preservado mesmo após remoção de membro
- Conversas podem ser arquivadas

**Limitações por Plano:**

- Plano Grátis: Apenas conversas 1:1, sem áudio
- Plano Básico: Conversas em grupo (até 10 membros), áudio limitado (1 min)
- Plano Profissional: Grupos ilimitados, áudio até 3 minutos
- Plano Empresarial: Todas as funcionalidades, áudio até 5 minutos

---

### 12. **Auditoria e Logs** 🔍

**Rastreamento:**

- Todas as ações importantes são auditadas
- Registro de criação, edição e exclusão
- Usuário responsável
- Data/hora da ação
- Dados antes e depois (JSON)
- IP e User Agent

**Ações auditadas:**

- Alterações em clientes e pacientes
- Agendamentos (criação, cancelamento)
- Consultas e prontuários
- Movimentações financeiras
- Movimentações de estoque
- Alterações de usuários e permissões

---

### 13. **Sistema de Planos e Assinaturas** 💳

#### 13.1 Planos Disponíveis

**Estrutura de planos:**

- Nome e descrição
- Preço mensal e anual
- Limites (usuários, consultas/mês, storage)
- Stripe Product ID e Price IDs
- Woovi para pix

**Limites por plano:**

- `limite_usuarios`: Quantidade de usuários por unidade
- `limite_clinicas`: Quantidade de unidades/clínicas permitidas
- `limite_consultas_mes`: Consultas por mês por unidade
- `limite_storage_gb`: Armazenamento em GB (total da conta)
- `consultas_ilimitadas`: Boolean
- `clinicas_ilimitadas`: Boolean

#### 13.2 Fluxo de Assinatura

primeira clínica/unidade 3. Escolhe método de pagamento:

- **Stripe Checkout:** Pagamento recorrente automático (cartão de crédito)
- **Woovi (PIX):** Cliente precisa renovar manualmente todo mês

4. Webhook confirma pagamento (Stripe ou Woovi)
5. Assinatura e primeira clínica são ativadas
6. Trial de 14 diasde Pagamento

**Stripe (Cartão de Crédito - Recorrente):**

- `checkout.session.completed`: Assinatura criada
- `customer.subscription.updated`: Assinatura atualizada
- `customer.subscription.deleted`: Assinatura cancelada
- `invoice.payment_succeeded`: Pagamento bem-sucedido
- `invoice.payment_failed`: Pagamento falhou

**Woovi (PIX - Manual):**

- `OPENPIX:CHARGE_COMPLETED`: Pagamento PIX confirmado
- `OPENPIX:CHARGE_EXPIRED`: Pagamento PIX expirado
- Renovação manual: cliente precisa gerar novo PIX a cada vencimento
- Sistema envia lembretes de vencimento próximo

**Middleware:** Sem autenticação (rotas públicas com verificação de assinatura Stripe/Woovi

- `customer.subscription.deleted`: Assinatura cancelada
- `invoice.payment_succeeded`: Pagamento bem-sucedido
- `invoice.payment_failed`: Pagamento falhou

**Middleware:** Sem autenticação (rota pública com verificação de assinatura Stripe)

---

### 14. **Super Admin** 🦸

**Middleware:** `EnsureIsSuperAdmin`

**Acesso especial:**

- Visualizar todas as clínicas do sistema
- Visualizar todas as assinaturas
- Ativar/desativar clínicas e assinaturas
- Gerenciar planos globais
- Reprocessar webhooks (Stripe e Woovi)
- Estatísticas gerais do SaaS
- Logs do sistema
- Métricas de uso por plano
- Receita recorrente mensal (MRR)

---

## 🔐 Segurança

### Middlewares aplicados/unidade está ativa e assinatura válida

3. **single.session:** Apenas uma sessão ativa por usuário
4. **permission:** Verificação de permissões específicas
5. **check.plan.limits:** Verifica limites do plano (usuários, consultas, storage, unidades)

### Proteções

- CSRF Protection (exceto webhooks Stripe/Woovi)
- SQL Injection prevention (Eloquent ORM)
- XSS Protection (sanitização de inputs)
- Rate Limiting (API e formulários)
- Validação de dados (Form Requests)
- Hashing de senhas (bcrypt)
- Auditoria completa de ações
- Isolamento de dados por tenant (clinica_id)
- Validação de dados (Form Requests)
- Hashing de senhas (bcrypt)
- Auditoria completa de ações

---

## 🚀 Fluxos Principais

### Fluxo de Agendamento

1. Recepcionista cria agendamento
2. Sistema verifica disponibilidade do veterinário
3. Cliente é notificado (opcional)
4. Cliente confirma presença (ou admin confirma)
5. Cliente faz check-in na clínica
6. Status muda para "aguardando" (fila)
7. Veterinário inicia atendimento
8. Status muda para "em_atendimento"
9. Consulta é criada automaticamente
10. Veterinário finaliza atendimento e consulta
11. Sistema gera receita financeira automaticamente
12. Status muda para "finalizado"

### Fluxo de Atendimento

1. Veterinário acessa consulta da fila
2. Preenche anamnese e exame físico
3. Sistema salva automaticamente (auto-save)
4. Veterinário registra:
   - Exames solicitados
   - Receitas médicas
   - Procedimentos realizados
   - Vacinas aplicadas
5. Veterinário finaliza consulta
6. Observer gera lançamento financeiro
7. Prontuário fica disponível no histórico

### Fluxo Financeiro (Consulta)

1. Consulta é finalizada
2. `ConsultaObserver` dispara
3. `AtendimentoFinanceiroService` é chamado
4. Verifica valor do tipo de serviço
5. Cria `ReceitaFinanceira` vinculada à consulta
6. Categoria "Consultas" é aplicada
7. Lançamento aparece no financeiro

### Fluxo de Chat/Conversas Internas

**Envio de Mensagem de Texto:**

1. Usuário acessa página de Chat
2. Seleciona conversa existente ou cria nova
3. Digita mensagem no input
4. Sistema exibe "digitando..." para outros participantes (via WebSocket)
5. Usuário adiciona emojis (opcional)
6. Clica em enviar ou pressiona Enter
7. Mensagem é salva no banco
8. WebSocket envia mensagem em tempo real para participantes online
9. Sistema marca mensagem como "enviada" e depois "visualizada"
10. Badge de não lidas é atualizado

**Envio de Áudio:**

1. Usuário clica no botão de gravar áudio
2. Browser solicita permissão ao microfone
3. Usuário grava mensagem (até limite do plano)
4. Áudio é processado e enviado para S3
5. Mensagem com link do á udio é criada
6. WebSocket notifica participantes
7. Player de áudio aparece para destinatários

**Criação de Grupo:**

1. Admin/Veterinário clica em "Novo Grupo"
2. Define nome do grupo
3. Seleciona membros da equipe
4. Sistema cria conversa tipo "grupo"
5. Todos os participantes são notificados
6. Grupo aparece na lista de conversas de todos

---

## 📊 Relacionamentos Principais

```
Assinatura (1) ──→ (N) Clinica (Unidades)
           └──→ (1) Plano

Clinica (1) ──→ (N) Usuario
         └──→ (N) Cliente
         └──→ (N) Paciente
         └──→ (N) Agendamento
         └──→ (N) Consulta
         └──→ (N) Produto
         └──→ (N) ReceitaFinanceira
         └──→ (N) Despesa
         └──→ (N) CategoriaFinanceira
         └──→ (N) CategoriasProduto

Cliente (1) ──→ (N) Paciente
└──→ (N) Agendamento

Paciente (1) ──→ (N) Agendamento
└──→ (N) Consulta
└──→ (N) Vacinacao

Agendamento (1) ──→ (0..1) Consulta

Consulta (1) ──→ (N) ExameSolicitado
└──→ (N) Receita
└──→ (N) Procedimento
└──→ (N) Vacinacao
└──→ (1) ReceitaFinanceira

Receita (1) ──→ (N) ItemReceita

ExameSolicitado (1) ──→ (0..N) ResultadoExame

Usuario (N) ←──→ (N) Clinica (acesso a múltiplas unidades)
(N) ←──→ (N) Role (por clínica)

Role (N) ←──→ (N) Permission

Produto (1) ──→ (N) MovimentacaoEstoque
└──→ (N) ItemReceita
└──→ (N) ProcedimentoMaterial

Plano (1) ──→ (N) Assinatura

Conversa (1) ──→ (N) Mensagem
         └──→ (N) ConversaParticipante

Usuario (N) ←──→ (N) Conversa (via ConversaParticipante)
Mensagem (N) ──→ (1) Usuario (remetente)
```

- **BaseModal:** Modal base para todos os modais. Todos os modais pertinentes, aqueles que podem possuir muitos campos de ser drawer (ou painel deslizante / side panel). É um padrão comum onde um painel desliza da lateral da tela.
- **FlashMessages:** Notificações toast
- **ClinicaSwitcher:** Dropdown para alternar entre unidades (no header)
- **ClinicaCard:** Card de unidade com informações resumidas
- **ChatWindow:** Janela de conversa com mensagens e input
- **ChatList:** Lista de conversas com badge de não lidas
- **MessageBubble:** Componente de mensagem individual (texto/áudio)
- **AudioRecorder:** Gravador de áudio para mensagens
- **EmojiPicker:** Seletor de emojis

### Layout Principal (AppLayout)

- Sidebar com navegação
- Header com:
  - **Seletor de unidade/clínica ativa** (dropdown)
  - Informações do usuário
  - Notificações
- Breadcrumbs
- Área de conteúdo principal
- Sistema de notificações toast

### Páginas Principais

- **Dashboard:** Visão geral, indicadores, gráficos
- **Agenda/Calendário:** Calendário visual de agendamentos
- **Atendimento:** Interface completa de atendimento
- **Prontuário:** Histórico de consultas
- **Clientes:** Lista e CRUD de clientes
- **Pacientes:** Lista e CRUD de pacientes
- **Estoque:** Gestão de produtos e movimentações
- **Financeiro:** Receitas, despesas, relatórios
- **Admin:** Configurações da clínica
- **Unidades:** Gestão de múltiplas clínicas (se plano permitir)
- **Chat:** Sistema de conversas internas

**Páginas de Chat:**

- **Chat/Index:** Interface principal de conversas com lista de conversas e área de mensagens
- **Chat/Conversas:** Lista de todas as conversas (individuais e grupos)
- **Chat/Grupo/Create:** Criar novo grupo de conversa
- **Chat/Grupo/Edit:** Editar grupo (adicionar/remover membros, alterar nome)
- **Chat/Configuracoes:** Configurações de notificações e privacidade

**Páginas de Gestão de Unidades:**

- **Unidades/Index:** Lista todas as unidades da conta
- **Unidades/Create:** Criar nova unidade (verifica limite do plano)
- **Unidades/Edit:** Editar dados de uma unidade
- **Unidades/Configuracoes:** Configurações específicas da unidade
- **Relatorios/Consolidado:** Relatórios consolidados de todas as unidades

---

## 📈 Limites e Planos

### Plano Grátis

- 1 usuário
- **1 unidade/clínica**

### Plano Básico

- 3 usuários por unidade
- 100 consultas/mês por unidade
- 5 GB storage total
- Suporte por email
- **Até 2 unidades/clínicas**

### Plano Profissional

- 10 usuários por unidade
- 500 consultas/mês por unidade
- 25 GB storage total
- Suporte prioritário
- Suporte telefônico
- **Até 5 unidades/clínicas**

### Plano Empresarial

- Usuários ilimitados
- Consultas ilimitadas
- 100 GB storage total
- Backup automático
- Suporte prioritário
- Suporte telefônico
- API externa
- **Unidades/clínicas ilimitadas**

**Observações importantes:**

- Limites de usuários e consultas são **por unidade**
- Storage é **compartilhado entre todas as unidades da conta**
- Possibilidade de contratar unidades extras nos planos Básico e Profissionaltário
- Suporte telefônico
- API externa
- Multi-clínicas

## 📝 Observações Importantes

### Auto-save no Atendimento

- Sistema salva automaticamente a cada alteração
- Evita perda de dados
- Debounce de 2 segundos

## 🔄 Estrutura de Banco de Dados

### Tabelas Principais

**Gestão de Assinaturas e Unidades:**

- `planos`: Planos disponíveis (Grátis, Básico, Profissional, Empresarial)
- `assinaturas`: Assinaturas ativas (uma assinatura pode ter múltiplas clínicas)
- `clinicas`: Unidades/clínicas do sistema (cada uma com clinica_id único)

**Multi-tenant (todas com clinica_id):**

- `usuarios`: Usuários do sistema (podem ter acesso a múltiplas clínicas)
- `clientes`: Clientes/tutores dos pets
- `pacientes`: Pets/animais
- `agendamentos`: Agendamentos de consultas
- `consultas`: Consultas realizadas
- `produtos`: Produtos em estoque
- `receitas_financeiras`: Receitas
- `despesas`: Despesas

**Sistema de Permissões:**

- `roles`: Cargos/funções
- `permissions`: Permissões do sistema
- `role_permissions`: Permissões por cargo
- `user_roles`: Cargos por usuário (por clínica)

**Chat/Conversas Internas (com clinica_id):**

- `conversas`: Conversas (individuais ou em grupo)
- `conversa_participantes`: Participantes de cada conversa
- `mensagens`: Mensagens enviadas nas conversas
- `mensagem_leituras`: Controle de leitura de mensagens
- `mensagem_arquivos`: Arquivos anexados (áudios, imagens)

**Auditoria:**

- `audit_logs`: Logs de auditoria

**Webhooks:**

- `stripe_events`: Eventos Stripe
- `stripe_webhook_logs`: Logs de webhooks Stripe
- `woovi_events`: Eventos Woovi (PIX)
- `woovi_webhook_logs`: Logs de webhooks Woovi

### Indexes Importantes

- Todos os models principais têm index em `clinica_id` para performance
- Index composto `(clinica_id, data)` em tabelas com datas
- Index em campos de busca frequente (nome, email, cpf)

---

## 🎯 Próximos Passos (Sugestões para Novo Projeto)

### Melhorias Técnicas

1. **API REST completa** para integrações externas
2. **Notificações em tempo real** (WebSockets/Pusher)
3. **App Mobile** (React Native ou Flutter)
4. **PWA** (Progressive Web App)
5. **Relatórios avançados** com gráficos interativos
6. **Integração com WhatsApp** (lembretes, confirmações)
7. **Agenda online** para clientes agendarem

### Funcionalidades Adicionais

1. **Telemedicina** (consultas online)
2. **Pet Shop integrado** (venda de produtos)
3. **Programa de fidelidade** para clientes
4. **Integração com laboratórios** (resultados automáticos)
5. **Gestão de internações**
6. **Controle de cirurgias** (centro cirúrgico)
7. **Campanhas de vacinação** em lote
8. **Envio de SMS/Email** automático
9. **Assinatura digital** de receitas
10. **OCR para digitalização** de documentos

### Performance

1. **Cache Redis** para queries frequentes
2. **Filas assíncronas** (Horizon)
3. **CDN** para assets estáticos
4. **Otimização de queries** (N+1 problems)
5. **Lazy loading** de imagens

---

## 📦 Dependências Principais

### Backend (composer.json)

```json
{
  "laravel/framework": "^12.0",
  "inertiajs/inertia-laravel": "^2.0",
  "stripe/stripe-php": "^17.4",
  "barryvdh/laravel-dompdf": "^3.1",
  "league/flysystem-aws-s3-v3": "^3.29"
}
```

### Frontend (package.json)

```json
{
  "vue": "^3.5.14",
  "shadcn-vue": "^latest4",
  "vite": "^6.2.0",
  "typescript": "^5.8.3",
  "tailwindcss": "@tailwindcss/vite@^4.1.7",
  "daisyui": "^5.0.35",
  "lucide-vue-next": "^0.468.0"
}
```

---

## 🎓 Conceitos Técnicos Aplicados

### Design Patterns

- **Repository Pattern** (via Services)
- **Observer Pattern** (Agendamento, Consulta)
- **Factory Pattern** (Seeders)
- **Middleware Pattern** (Laravel)

### Princípios SOLID

- **Single Responsibility:** Services específicos por domínio
- **Open/Closed:** Extensibilidade via Observers
- **Dependency Injection:** Services injetados via container

### Boas Práticas

- **Form Requests** para validação
- **Resource Controllers** para CRUD
- **API Resources** (preparado para API)
- **Scoped Queries** (multi-tenant)
- **Eager Loading** (evitar N+1)
- **Transactions** em operações críticas

---

## 📱 Responsividade

- **Mobile First:** Interface adaptada para mobile
- **Tailwind Breakpoints:** sm, md, lg, xl, 2xl
- **Componentes adaptáveis**
- **Menu lateral colapsável** em telas pequenas

---

## 🌐 Internacionalização

- **Preparado para i18n**
- **Laravel Localization** (pt_BR padrão)
- **Vue I18n** (frontend)
- **Tradução de mensagens** de erro e validação

---

## ✅ Conclusão

O **Bullclinic** é uma solução SaaS completa e robusta para clínicas veterinárias, com arquitetura multi-tenant que suporta **múltiplas unidades/clínicas por assinatura**, sistema de permissões granular (RBAC), integração com **Stripe (cartão recorrente) e Woovi (PIX)** para pagamentos flexíveis, prontuário eletrônico completo, gestão de estoque e financeiro integrados.

**Principais diferenciais:**

- **Multi-unidades:** Uma conta pode gerenciar várias clínicas (de acordo com o plano)
- **Pagamentos flexíveis:** Cartão recorrente (Stripe) ou PIX mensal (Woovi)
- **Interface moderna:** shadcn-vue + Tailwind CSS para UX excepcional
- **Isolamento de dados:** Cada unidade é um tenant isolado com total segurança
- **Escalabilidade:** Planos que crescem junto com o negócio do cliente

A stack tecnológica moderna (**Laravel 12 + Vue 3 + Inertia.js + shadcn-vue + Tailwind**) garante performance, escalabilidade e excelente experiência do usuário.

**Este documento serve como base para a recriação total do projeto** ou como referência arquitetural para novos desenvolvedores.

---

**Versão do Documento:** 2.0  
**Data:** 27 de Março de 2026  
**Autor:** Equipe Bullclinic Development

---

## 📋 Checklist de Implementção

### Fase 1: Fundação

- [ ] Setup Laravel 12 + Vue 3 + Inertia.js
- [ ] Configurar Tailwind CSS + shadcn-vue
- [ ] Estrutura de banco de dados (migrations)
- [ ] Sistema de autenticação
- [ ] Middleware multi-tenant
- [ ] Sistema de roles e permissões (RBAC)

### Fase 2: Core Features

- [ ] Gestão de assinaturas e planos
- [ ] Integração Stripe (cartão recorrente)
- [ ] Integração Woovi (PIX)
- [ ] Gestão de múltiplas unidades/clínicas
- [ ] Seletor de unidade no header
- [ ] CRUD de clientes e pacientes

### Fase 3: Módulos Principais

- [ ] Agenda e agendamentos
- [ ] Sistema de check-in e fila
- [ ] Prontuário eletrônico (consulta, exames, receitas, procedimentos, vacinas)
- [ ] Gestão de estoque
- [ ] Módulo financeiro (receitas e despesas)
- [ ] Chat/Conversas internas (texto, emojis, áudio)
- [ ] Configurar Laravel Reverb/Pusher para WebSockets

### Fase 4: Funcionalidades Avançadas

- [ ] Dashboard com indicadores
- [ ] Relatórios por unidade e consolidado
- [ ] Sistema de auditoria
- [ ] Geração de PDFs (receitas, atestados)
- [ ] Upload de arquivos com controle de storage
- [ ] Sistema de notificações
- [ ] Notificações em tempo real do chat
- [ ] Status online/offline dos usuários

### Fase 5: Polimento e Deploy

- [ ] Testes automatizados
- [ ] Otimização de performance
- [ ] Documentação técnica
- [ ] Deploy em produção
- [ ] Monitoramento e logs
