# BullClinic

SaaS para clínicas veterinárias. Multi-tenant por `clinica_id`, onde cada clínica tem seus próprios dados, usuários, roles e plano de assinatura.

## Stack

| Camada | Tecnologia |
|---|---|
| Backend | Laravel 12 (PHP 8.3+) |
| Frontend | Vue 3 + Inertia.js |
| Estilo | Tailwind CSS 4 |
| Banco | MySQL / SQLite (dev) |
| Fila | Database / Redis |
| WebSocket | Laravel Reverb |
| Storage | AWS S3 |
| Pagamentos | Stripe + Woovi (PIX) |

## Módulos

- **Agenda** — calendário, fila de espera, checkin
- **Prontuário** — consultas, receitas, exames, procedimentos, vacinação, PDF
- **Pacientes** — perfil completo, fotos, tags, histórico
- **Clientes** — cadastro com ViaCEP
- **Estoque** — produtos, movimentações, fornecedores
- **Financeiro** — lançamentos, categorias, gráfico de receitas vs despesas
- **Documentos** — upload direto ao S3 (presigned URL), controle de storage
- **Chat** — mensagens em tempo real via Reverb, áudio, fixar, leitura
- **Planos** — Stripe Checkout, PIX via Woovi, Customer Portal
- **Admin** — usuários, roles, configurações da clínica

## Instalação

### 1. Dependências

```bash
composer install
npm install
```

### 2. Ambiente

```bash
cp .env.example .env
php artisan key:generate
```

Edite o `.env` com as credenciais do banco, S3, Stripe, Woovi e Reverb (veja a seção de variáveis abaixo).

### 3. Banco de dados

```bash
# Criar tabelas
php artisan migrate

# Popular dados base (planos, roles, permissões, categorias)
php artisan db:seed

# Ou tudo junto (fresh + seed)
php artisan migrate:fresh --seed
```

### 4. Storage

```bash
# Criar link simbólico para storage público (avatares, logos locais)
php artisan storage:link
```

### 5. Build frontend

```bash
# Desenvolvimento (hot reload)
npm run dev

# Produção
npm run build
```

### 6. Servidor de desenvolvimento

```bash
# Servidor PHP
php artisan serve

# WebSocket (Reverb) — necessário para o Chat funcionar
php artisan reverb:start

# Fila — necessário para jobs/notificações (em dev pode usar sync)
php artisan queue:work
```

## Variáveis de ambiente essenciais

```env
APP_NAME=BullClinic
APP_URL=https://seudominio.com.br
APP_KEY=                         # gerada pelo key:generate

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=bullclinic
DB_USERNAME=root
DB_PASSWORD=

BROADCAST_CONNECTION=reverb
QUEUE_CONNECTION=database        # ou redis em produção

# AWS S3 — upload de documentos, fotos, áudios
AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=
AWS_BUCKET=
AWS_URL=                         # URL pública do bucket (CDN ou endpoint S3)

# Laravel Reverb — WebSocket
REVERB_APP_ID=
REVERB_APP_KEY=
REVERB_APP_SECRET=
REVERB_HOST=localhost
REVERB_PORT=8080
REVERB_SCHEME=http

VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST="${REVERB_HOST}"
VITE_REVERB_PORT="${REVERB_PORT}"
VITE_REVERB_SCHEME="${REVERB_SCHEME}"

# Stripe — assinaturas com cartão
STRIPE_KEY=
STRIPE_SECRET=
STRIPE_WEBHOOK_SECRET=

# Woovi / OpenPix — PIX
WOOVI_APP_ID=
```

## Comandos úteis

```bash
# Limpar e recompilar caches
php artisan optimize:clear

# Cache para produção
php artisan optimize

# Ver rotas registradas
php artisan route:list

# Executar migrations pendentes
php artisan migrate

# Reverter última migration
php artisan migrate:rollback

# Iniciar Reverb em produção (com supervisor recomendado)
php artisan reverb:start --host=0.0.0.0 --port=8080

# Processar fila em produção (com supervisor recomendado)
php artisan queue:work --tries=3 --timeout=90
```

## Produção

1. Defina `APP_ENV=production` e `APP_DEBUG=false` no `.env`
2. Configure `APP_URL` e `ASSET_URL` com o domínio real
3. Execute `php artisan optimize` após cada deploy
4. Use supervisor para manter `queue:work` e `reverb:start` em execução contínua
5. Configure o webhook do Stripe apontando para `https://seudominio.com.br/webhooks/stripe`
6. Configure o webhook do Woovi apontando para `https://seudominio.com.br/webhooks/woovi`
