# CONTEXT.md — CopApp (Mundial 2026)
> Revisão v3 — Guia Definitivo para o Agente de Codificação

---

## ÍNDICE

1. [Visão Geral do Projeto](#1-visão-geral-do-projeto)
2. [Stack Tecnológica Completa](#2-stack-tecnológica-completa)
3. [Dependências Frontend (Completo)](#3-dependências-frontend-completo)
4. [Dependências Backend (Completo)](#4-dependências-backend-completo)
5. [Sistema de Design & Paleta de Cores](#5-sistema-de-design--paleta-de-cores)
6. [Componentes shadcn/ui Necessários](#6-componentes-shadcnui-necessários)
7. [Infraestrutura e Hospedagem](#7-infraestrutura-e-hospedagem)
8. [Decisão de Banco de Dados & Justificativa](#8-decisão-de-banco-de-dados--justificativa)
9. [Estrutura de Banco de Dados & Migrations](#9-estrutura-de-banco-de-dados--migrations)
10. [Estratégia de Sincronização API-Football](#10-estratégia-de-sincronização-api-football)
11. [Estrutura de Pastas do Projeto](#11-estrutura-de-pastas-do-projeto)
12. [Escopo do MVP & Requisitos Funcionais](#12-escopo-do-mvp--requisitos-funcionais)
13. [Arquitetura de Componentes React](#13-arquitetura-de-componentes-react)
14. [Diretivas de UI/UX — Quiet Luxury](#14-diretivas-de-uiux--quiet-luxury)
15. [Fluxo de Trabalho e Guardrails da IA](#15-fluxo-de-trabalho-e-guardrails-da-ia)

---

## 1. Visão Geral do Projeto

**CopApp** é uma aplicação web de consulta e análise tática para o **FIFA World Cup 2026** (11 de junho a 19 de julho de 2026). Serve como companheiro inteligente para fãs e apostadores, integrando dados ao vivo, análise por IA (Gemini Flash) e interface minimalista *Quiet Luxury*.

### Restrições Absolutas

| Restrição | Detalhe |
|---|---|
| **Custo zero** | Toda decisão de arquitetura deve ser validada contra os free tiers da Secção 7 |
| **100 req/dia** | Orçamento total da API-Football. Ver Secção 10 antes de qualquer novo endpoint |
| **Gemini Flash only** | Nunca usar `gemini-pro` em produção. Flash: ~1.500 req/dia, 15 req/min |

### Princípio de Desenvolvimento (Vibe Coding Mindset)

- **Uma tarefa de cada vez.** Nunca implementar múltiplas fases em simultâneo.
- **Granularidade.** O agente recebe instruções específicas, não comandos vagos de alto nível.
- **Este documento é o plano.** Seguir passo a passo; desvios requerem validação explícita.

---

## 2. Stack Tecnológica Completa

```
Backend  → PHP 8.2+ · Laravel 11.x
Frontend → React 18.x · TypeScript · Vite
Styling  → Tailwind CSS 3.x · shadcn/ui · Framer Motion
Icons    → Lucide React
IA       → Google Gemini API (Flash)
Dados    → API-Football (plan Free, 100 req/dia)
DB       → PostgreSQL 16 (self-hosted, Oracle VM)
RT       → Laravel Reverb (WebSockets)
Push     → Firebase Cloud Messaging (FCM)
Deploy   → Oracle Cloud Always Free (backend) + Vercel (frontend)
```

---

## 3. Dependências Frontend (Completo)

### package.json — Dependências de Produção

```json
{
  "dependencies": {
    "react": "^18.3.1",
    "react-dom": "^18.3.1",
    "react-router-dom": "^6.26.0",
    "axios": "^1.7.3",
    "framer-motion": "^11.3.0",
    "lucide-react": "^0.414.0",
    "clsx": "^2.1.1",
    "tailwind-merge": "^2.4.0",
    "class-variance-authority": "^0.7.0",
    "@radix-ui/react-dialog": "^1.1.1",
    "@radix-ui/react-dropdown-menu": "^2.1.1",
    "@radix-ui/react-tooltip": "^1.1.2",
    "@radix-ui/react-tabs": "^1.1.0",
    "@radix-ui/react-popover": "^1.1.1",
    "@radix-ui/react-badge": "^1.0.0",
    "@radix-ui/react-avatar": "^1.1.0",
    "@radix-ui/react-scroll-area": "^1.1.0",
    "@radix-ui/react-separator": "^1.1.0",
    "@radix-ui/react-skeleton": "^1.0.0",
    "@radix-ui/react-switch": "^1.1.0",
    "@radix-ui/react-toast": "^1.2.1",
    "@radix-ui/react-slot": "^1.1.0",
    "firebase": "^10.12.4",
    "laravel-echo": "^1.16.1",
    "pusher-js": "^8.4.0",
    "date-fns": "^3.6.0",
    "date-fns-tz": "^3.1.3",
    "react-intersection-observer": "^9.13.0",
    "zustand": "^4.5.4",
    "react-query": "^5.51.23",
    "@tanstack/react-query": "^5.51.23",
    "@tanstack/react-query-devtools": "^5.51.23"
  }
}
```

### package.json — Dependências de Desenvolvimento

```json
{
  "devDependencies": {
    "typescript": "^5.5.3",
    "@types/react": "^18.3.3",
    "@types/react-dom": "^18.3.0",
    "@types/node": "^22.1.0",
    "vite": "^5.3.5",
    "@vitejs/plugin-react": "^4.3.1",
    "tailwindcss": "^3.4.7",
    "postcss": "^8.4.40",
    "autoprefixer": "^10.4.19",
    "prettier": "^3.3.3",
    "prettier-plugin-tailwindcss": "^0.6.5",
    "eslint": "^9.8.0",
    "@typescript-eslint/eslint-plugin": "^8.0.1",
    "vitest": "^2.0.5",
    "@testing-library/react": "^16.0.0",
    "@testing-library/jest-dom": "^6.4.8",
    "jsdom": "^24.1.1",
    "msw": "^2.3.4"
  }
}
```

### Instalação shadcn/ui (executar na raiz do frontend)

```bash
npx shadcn@latest init
# Escolher: Dark mode → "class", Base color → "zinc", CSS variables → yes
```

---

## 4. Dependências Backend (Completo)

### composer.json — Dependências de Produção

```json
{
  "require": {
    "php": "^8.2",
    "laravel/framework": "^11.0",
    "laravel/reverb": "^1.0",
    "laravel/sanctum": "^4.0",
    "laravel/horizon": "^5.24",
    "predis/predis": "^2.2",
    "guzzlehttp/guzzle": "^7.8",
    "google/cloud-firestore": "^1.28",
    "kreait/firebase-php": "^7.9",
    "spatie/laravel-query-builder": "^5.8",
    "spatie/laravel-data": "^4.7",
    "spatie/laravel-rate-limited-job-middleware": "^2.4",
    "doctrine/dbal": "^3.8"
  },
  "require-dev": {
    "laravel/pint": "^1.16",
    "laravel/sail": "^1.29",
    "laravel/telescope": "^5.2",
    "phpunit/phpunit": "^11.2",
    "mockery/mockery": "^1.6",
    "fakerphp/faker": "^1.23"
  }
}
```

### Pacotes Laravel a Instalar (ordem de execução)

```bash
# 1. Autenticação de API
php artisan install:api

# 2. Reverb (WebSockets)
php artisan reverb:install

# 3. Horizon (gestão de filas — dashboard gratuito)
php artisan horizon:install

# 4. Sanctum (tokens)
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"

# 5. Telescope (debugging — apenas dev)
php artisan telescope:install
```

### Serviços Externos — Variáveis de Ambiente (.env)

```dotenv
# ─── API-Football ───────────────────────────────────────────
API_FOOTBALL_KEY=
API_FOOTBALL_BASE_URL=https://v3.football.api-sports.io
API_FOOTBALL_LEAGUE=1
API_FOOTBALL_SEASON=2026

# ─── Google Gemini ──────────────────────────────────────────
GEMINI_API_KEY=
GEMINI_MODEL=gemini-1.5-flash
GEMINI_MAX_TOKENS=1024

# ─── Firebase FCM ───────────────────────────────────────────
FIREBASE_PROJECT_ID=
FIREBASE_CREDENTIALS_PATH=/path/to/firebase-credentials.json

# ─── Laravel Reverb ─────────────────────────────────────────
REVERB_APP_ID=
REVERB_APP_KEY=
REVERB_APP_SECRET=
REVERB_HOST=0.0.0.0
REVERB_PORT=8080
REVERB_SCHEME=https

# ─── Redis (para cache e filas) ─────────────────────────────
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
QUEUE_CONNECTION=redis
CACHE_STORE=redis
SESSION_DRIVER=redis

# ─── Database (PostgreSQL) ──────────────────────────────────
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=copapp
DB_USERNAME=
DB_PASSWORD=
```

---

## 5. Sistema de Design & Paleta de Cores

### Filosofia Visual — "Quiet Luxury Dark"

Interface minimalista, sóbria e sofisticada. Inspirada em dashboards premium de análise desportiva. Ausência total de banners publicitários. Tipografia refinada. O utilizador sente que está a usar uma ferramenta profissional, não uma app de torcedor.

### Paleta de Cores Principal

```css
/* tailwind.config.ts — extend.colors */

:root {
  /* ─── Backgrounds ─────────────────────────────────────── */
  --background:        #0A0C10;   /* Preto azulado profundo — canvas principal */
  --surface:           #111318;   /* Cards, painéis */
  --surface-elevated:  #181C24;   /* Modais, drawers, dropdowns */
  --surface-overlay:   #1E2330;   /* Hover states, seleções */

  /* ─── Borders & Dividers ─────────────────────────────── */
  --border:            #242938;   /* Bordas padrão (finas, discretas) */
  --border-strong:     #2E3447;   /* Bordas de foco ou ênfase */

  /* ─── Text ───────────────────────────────────────────── */
  --foreground:        #E8EAF0;   /* Texto primário */
  --muted-foreground:  #6B7280;   /* Labels, metadados, texto secundário */
  --subtle:            #3D4454;   /* Placeholders, ícones inativos */

  /* ─── Brand — Dourado FIFA ──────────────────────────── */
  --gold:              #C9A84C;   /* Accent principal — destaque tático */
  --gold-muted:        #8B6F32;   /* Versão desaturada para backgrounds */
  --gold-glow:         rgba(201,168,76,0.15); /* Glow em hover/foco */

  /* ─── Semânticas ─────────────────────────────────────── */
  --success:           #22C55E;   /* Gols, confirmação de escalação */
  --success-muted:     rgba(34,197,94,0.12);
  --warning:           #F59E0B;   /* Cartões amarelos, lesões leves */
  --warning-muted:     rgba(245,158,11,0.12);
  --danger:            #EF4444;   /* Cartões vermelhos, suspensões, erros */
  --danger-muted:      rgba(239,68,68,0.12);
  --info:              #3B82F6;   /* Dados informativos, links */
  --info-muted:        rgba(59,130,246,0.12);

  /* ─── Glassmorphism ──────────────────────────────────── */
  --glass-bg:          rgba(17,19,24,0.75);
  --glass-border:      rgba(255,255,255,0.06);
  --glass-blur:        blur(12px);
}
```

### tailwind.config.ts

```typescript
import type { Config } from 'tailwindcss'

const config: Config = {
  darkMode: ['class'],
  content: ['./src/**/*.{ts,tsx}'],
  theme: {
    extend: {
      colors: {
        background:       'hsl(var(--background))',
        surface:          'hsl(var(--surface))',
        'surface-elevated': 'hsl(var(--surface-elevated))',
        'surface-overlay': 'hsl(var(--surface-overlay))',
        border:           'hsl(var(--border))',
        foreground:       'hsl(var(--foreground))',
        'muted-foreground': 'hsl(var(--muted-foreground))',
        gold: {
          DEFAULT:        '#C9A84C',
          muted:          '#8B6F32',
        },
        success:          '#22C55E',
        warning:          '#F59E0B',
        danger:           '#EF4444',
      },
      fontFamily: {
        sans:  ['Inter', 'system-ui', 'sans-serif'],
        mono:  ['JetBrains Mono', 'monospace'],
        display: ['Bricolage Grotesque', 'Inter', 'sans-serif'],
      },
      borderRadius: {
        lg: 'var(--radius)',
        md: 'calc(var(--radius) - 2px)',
        sm: 'calc(var(--radius) - 4px)',
      },
      backdropBlur: {
        xs: '2px',
      },
      animation: {
        'skeleton-pulse': 'skeleton 1.5s ease-in-out infinite',
        'score-update':   'scoreUpdate 0.5s cubic-bezier(0.34, 1.56, 0.64, 1)',
        'slide-up':       'slideUp 0.3s ease-out',
        'fade-in':        'fadeIn 0.25s ease-out',
      },
      keyframes: {
        skeleton: {
          '0%, 100%': { opacity: '1' },
          '50%':       { opacity: '0.4' },
        },
        scoreUpdate: {
          '0%':   { transform: 'scale(1)' },
          '50%':  { transform: 'scale(1.15)', color: '#C9A84C' },
          '100%': { transform: 'scale(1)' },
        },
        slideUp: {
          '0%':   { transform: 'translateY(8px)', opacity: '0' },
          '100%': { transform: 'translateY(0)',   opacity: '1' },
        },
        fadeIn: {
          '0%':   { opacity: '0' },
          '100%': { opacity: '1' },
        },
      },
    },
  },
  plugins: [require('tailwindcss-animate')],
}

export default config
```

### Regras de Tipografia

| Uso | Fonte | Classe Tailwind |
|---|---|---|
| Títulos de página, Nomes de seleções | Bricolage Grotesque | `font-display font-semibold` |
| Corpo de texto, labels | Inter | `font-sans` |
| Scores, estatísticas numéricas | JetBrains Mono | `font-mono tabular-nums` |
| Resultado ao vivo | Inter Bold | `font-sans font-bold text-3xl` |

---

## 6. Componentes shadcn/ui Necessários

Instalar com `npx shadcn@latest add <nome>`. Executar individualmente, nunca todos de uma vez.

### Lista Completa de Componentes

```bash
# ─── Layout & Navigation ─────────────────────────────────
npx shadcn@latest add sheet          # Drawer lateral (menu mobile, painel de IA)
npx shadcn@latest add navigation-menu # Navbar principal
npx shadcn@latest add tabs           # Abas (Grupos, Eliminatórias, Lesões)
npx shadcn@latest add scroll-area    # Listas de jogadores com scroll suave
npx shadcn@latest add separator      # Divisores minimalistas

# ─── Overlay & Feedback ──────────────────────────────────
npx shadcn@latest add dialog         # Modal de detalhes de jogo
npx shadcn@latest add popover        # Tooltip de lesões/suspensões
npx shadcn@latest add tooltip        # Hover info em ícones
npx shadcn@latest add toast          # Notificações de escalação confirmada
npx shadcn@latest add alert          # Alertas de desfalques críticos

# ─── Data Display ─────────────────────────────────────────
npx shadcn@latest add badge          # Tags: "LIVE", "Lesionado", "Suspenso"
npx shadcn@latest add avatar         # Fotos de jogadores e seleções
npx shadcn@latest add card           # Containers de cards de jogo
npx shadcn@latest add table          # Tabelas de classificação
npx shadcn@latest add skeleton       # Skeleton loading states

# ─── Forms & Inputs ──────────────────────────────────────
npx shadcn@latest add input          # Chat do Gemini
npx shadcn@latest add button         # Botões ghost, outline, default
npx shadcn@latest add switch         # Toggle Dark/Light mode
npx shadcn@latest add dropdown-menu  # Filtros e ordenação
```

### Customização dos Componentes (globals.css)

```css
/* Sobrescrever defaults shadcn para o tema Quiet Luxury */

/* Cards com glassmorphism */
.card-glass {
  background: var(--glass-bg);
  backdrop-filter: var(--glass-blur);
  border: 1px solid var(--glass-border);
  border-radius: 12px;
}

/* Inputs sem borda sólida */
[data-slot="input"] {
  background: var(--surface);
  border-color: var(--border);
  border-radius: 8px;
  transition: border-color 0.2s, box-shadow 0.2s;
}
[data-slot="input"]:focus {
  border-color: var(--gold);
  box-shadow: 0 0 0 3px var(--gold-glow);
  outline: none;
}

/* Navbar com backdrop-blur */
.nav-glass {
  background: rgba(10, 12, 16, 0.85);
  backdrop-filter: blur(16px);
  border-bottom: 1px solid var(--border);
}

/* Badge LIVE pulsante */
.badge-live {
  background: var(--danger-muted);
  color: var(--danger);
  animation: pulse 2s infinite;
}

/* Skeleton personalizado */
.skeleton {
  background: linear-gradient(
    90deg,
    var(--surface) 25%,
    var(--surface-elevated) 50%,
    var(--surface) 75%
  );
  background-size: 200% 100%;
  animation: skeleton-shimmer 1.5s infinite;
}

@keyframes skeleton-shimmer {
  0%   { background-position: 200% 0; }
  100% { background-position: -200% 0; }
}
```

### Convenção de Uso dos Componentes

| Componente | Variante Padrão | Notas |
|---|---|---|
| `Button` principal | `variant="outline"` | Borda gold em hover |
| `Button` IA/chips | `variant="ghost"` | Fundo transparente, texto muted |
| `Button` destrutivo | `variant="destructive"` | Apenas em confirmações |
| `Badge` LIVE | `variant="destructive"` + classe `badge-live` | Pulsante |
| `Badge` status | `variant="secondary"` | Lesionado, Suspenso |
| `Card` jogo | `.card-glass` | Glassmorphism |
| `Skeleton` | Classe `.skeleton` | Nunca blink, só shimmer |

---

## 7. Infraestrutura e Hospedagem

> Restrição: custo zero. Todos os componentes usam planos "Always Free" verificados.

| Componente | Solução | Tier Gratuito | Notas |
|---|---|---|---|
| Backend Laravel (web + queue + Reverb + scheduler) | **Oracle Cloud Always Free** — Ampere A1 ARM | 4 OCPU / 24GB RAM compartilhados (2 OCPU / 12GB recomendado para o MVP) | Risco: capacidade pode estar esgotada na região — tentar várias regiões |
| PostgreSQL | Self-hosted no mesmo VM Oracle | Sem limites adicionais | Evita free tiers com restrições de connections/storage |
| Redis | Self-hosted no mesmo VM Oracle | Sem limites adicionais | Cache, filas e sessions |
| Frontend React | **Vercel** | 100GB bandwidth/mês, sem hibernação | Build estático — adequado; não precisa de processo persistente |
| WebSockets | Laravel Reverb (self-hosted) | Ilimitado | Evita Pusher/Ably (limites de conexões simultâneas nos planos free) |
| Notificações Push | **Firebase Cloud Messaging** | Ilimitado para mensagens | |
| IA | **Google Gemini API** — Flash | ~1.500 req/dia, 15 req/min | Nunca usar Pro em produção |
| CI/CD | **GitHub Actions** | 2.000 min/mês (public repos) | Testes automáticos em cada push |
| Domínio / SSL | **Cloudflare** (free tier) | CDN + SSL gratuito | Proxy reverso para o VM Oracle |

### Configuração do Oracle VM (Recomendada)

```
Shape:  VM.Standard.A1.Flex
OCPU:   2
RAM:    12 GB
OS:     Ubuntu 22.04 LTS (ARM64)
Disk:   100 GB Block Storage (Always Free)
Ports:  80 (HTTP), 443 (HTTPS), 8080 (Reverb)
```

### Serviços no VM (supervisord ou systemd)

```
1. php-fpm                 → Laravel web server
2. php artisan queue:work  → Processamento de jobs (Camada 1, 2, 3)
3. php artisan reverb:start → WebSockets
4. php artisan schedule:run (cron a cada minuto)
5. postgresql              → Banco de dados
6. redis-server            → Cache e filas
7. nginx                   → Reverse proxy
```

---

## 8. Decisão de Banco de Dados & Justificativa

### Escolha: PostgreSQL 16

**Razões técnicas para escolher PostgreSQL em vez de MySQL:**

| Critério | PostgreSQL ✅ | MySQL |
|---|---|---|
| JSON nativo | `jsonb` — indexável, pesquisável | `JSON` — menos eficiente em queries complexas |
| Arrays nativos | `integer[]`, `text[]` — ideal para squads e estatísticas | Não tem arrays nativos |
| Full-text search | Nativo e robusto sem extensão | Requer configuração extra |
| Integridade referencial | DEFERRABLE constraints — melhor para imports de dados de API | Menos flexível |
| Extensão `pg_trgm` | Busca por similaridade de nomes de jogadores | Não disponível |
| Suporte a ENUM | ENUM nativo com tipo forte | ENUM como string simples |
| Transações | MVCC superior, menos locks | Pode travar mais em queries simultâneas |
| Laravel Eloquent | Suporte completo | Suporte completo |
| Self-hosted Oracle VM | Funciona em ARM64 — pacote `postgresql-16` disponível em Ubuntu | Funciona também |

**Conclusão:** PostgreSQL é a escolha superior para este projeto devido ao uso intensivo de dados JSON vindos da API-Football, necessidade de queries complexas de estatísticas, e o custo zero no Oracle VM.

---

## 9. Estrutura de Banco de Dados & Migrations

> Todas as migrations devem ser criadas com `php artisan make:migration` e seguir a ordem abaixo.

### 9.1 Diagrama de Entidades (Resumo)

```
teams ──────────────── fixtures ──── fixture_lineups
  │                      │  │
  │                    home away
players ──── player_statuses
  │
  └─── player_stats (topscorers, assists, cards)

api_quota_logs          (controlo de orçamento)
gemini_response_cache   (cache de respostas IA)
push_subscriptions      (FCM tokens por device)
sync_logs               (histórico de syncs)
```

---

### 9.2 Migrations Completas

#### Migration 1 — `teams`

```php
// database/migrations/2026_01_01_000001_create_teams_table.php
Schema::create('teams', function (Blueprint $table) {
    $table->id();
    $table->unsignedInteger('api_football_id')->unique()->index();
    $table->string('name', 100);
    $table->string('code', 10)->nullable();        // BRA, ARG, POR
    $table->string('country', 100)->nullable();
    $table->string('logo_url', 255)->nullable();
    $table->string('group_name', 10)->nullable();  // A, B, C ... L
    $table->jsonb('venue')->nullable();            // {name, city, capacity}
    $table->jsonb('coach')->nullable();            // {name, nationality, photo}
    $table->timestamps();
});
```

#### Migration 2 — `players`

```php
// database/migrations/2026_01_01_000002_create_players_table.php
Schema::create('players', function (Blueprint $table) {
    $table->id();
    $table->unsignedInteger('api_football_id')->unique()->index();
    $table->foreignId('team_id')->constrained()->cascadeOnDelete()->index();
    $table->string('name', 100);
    $table->string('firstname', 80)->nullable();
    $table->string('lastname', 80)->nullable();
    $table->date('birth_date')->nullable();
    $table->string('nationality', 80)->nullable();
    $table->unsignedSmallInteger('age')->nullable();
    $table->decimal('height', 5, 2)->nullable();  // em cm
    $table->decimal('weight', 5, 2)->nullable();  // em kg
    $table->string('photo_url', 255)->nullable();
    $table->string('position', 30)->nullable();   // Goalkeeper, Defender, Midfielder, Attacker
    $table->string('number')->nullable();
    $table->timestamps();

    $table->index(['team_id', 'position']);
});
```

#### Migration 3 — `player_statuses`

```php
// database/migrations/2026_01_01_000003_create_player_statuses_table.php
Schema::create('player_statuses', function (Blueprint $table) {
    $table->id();
    $table->unsignedInteger('api_football_id')->index();   // player api id
    $table->foreignId('player_id')->constrained()->cascadeOnDelete()->index();
    $table->foreignId('team_id')->constrained()->index();
    $table->string('type', 20);  // 'injury' | 'suspension'
    $table->string('reason', 150)->nullable();            // "Muscular", "Yellow Card Accumulation"
    $table->date('start_date')->nullable();
    $table->date('expected_return')->nullable();
    $table->boolean('is_active')->default(true)->index(); // false = recuperado/cumpriu suspensão
    $table->jsonb('raw_api_data')->nullable();            // payload original para auditoria
    $table->timestamp('synced_at')->nullable();
    $table->timestamps();

    $table->index(['team_id', 'is_active']);
    $table->index(['player_id', 'is_active']);
});
```

#### Migration 4 — `fixtures`

```php
// database/migrations/2026_01_01_000004_create_fixtures_table.php
Schema::create('fixtures', function (Blueprint $table) {
    $table->id();
    $table->unsignedInteger('api_football_id')->unique()->index();
    $table->foreignId('home_team_id')->constrained('teams')->index();
    $table->foreignId('away_team_id')->constrained('teams')->index();
    $table->string('round', 80)->nullable();   // "Group Stage - 1", "Round of 32", "Final"
    $table->string('stage', 30)->nullable();   // 'group' | 'r32' | 'r16' | 'qf' | 'sf' | 'f'
    $table->string('group_name', 10)->nullable();
    $table->string('venue_name', 100)->nullable();
    $table->string('venue_city', 80)->nullable();
    $table->timestamp('kickoff_utc')->index();
    $table->string('status_short', 10)->default('NS'); // NS, 1H, HT, 2H, FT, AET, PEN, PST
    $table->string('status_long', 50)->nullable();
    $table->unsignedSmallInteger('home_score')->nullable();
    $table->unsignedSmallInteger('away_score')->nullable();
    $table->unsignedSmallInteger('home_score_ht')->nullable();  // half-time
    $table->unsignedSmallInteger('away_score_ht')->nullable();
    $table->unsignedSmallInteger('home_score_et')->nullable();  // extra-time
    $table->unsignedSmallInteger('away_score_et')->nullable();
    $table->unsignedSmallInteger('home_score_pen')->nullable(); // penalties
    $table->unsignedSmallInteger('away_score_pen')->nullable();
    $table->unsignedSmallInteger('elapsed_minutes')->nullable();
    $table->boolean('lineup_confirmed')->default(false)->index();
    $table->timestamp('lineup_confirmed_at')->nullable();
    $table->jsonb('raw_api_data')->nullable();
    $table->timestamps();

    $table->index(['kickoff_utc', 'status_short']);  // query principal para jogos do dia
    $table->index(['status_short', 'lineup_confirmed']);
});
```

#### Migration 5 — `fixture_lineups`

```php
// database/migrations/2026_01_01_000005_create_fixture_lineups_table.php
Schema::create('fixture_lineups', function (Blueprint $table) {
    $table->id();
    $table->foreignId('fixture_id')->constrained()->cascadeOnDelete()->index();
    $table->foreignId('team_id')->constrained()->index();
    $table->string('formation', 20)->nullable();   // "4-3-3", "3-5-2"
    $table->jsonb('starting_xi')->default('[]');   // [{player_id, name, number, pos, grid}]
    $table->jsonb('substitutes')->default('[]');   // [{player_id, name, number, pos}]
    $table->jsonb('coach')->nullable();            // {name, photo}
    $table->boolean('is_confirmed')->default(false)->index();
    $table->timestamp('confirmed_at')->nullable();
    $table->timestamps();

    $table->unique(['fixture_id', 'team_id']);
});
```

#### Migration 6 — `standings`

```php
// database/migrations/2026_01_01_000006_create_standings_table.php
Schema::create('standings', function (Blueprint $table) {
    $table->id();
    $table->foreignId('team_id')->constrained()->index();
    $table->string('group_name', 10);              // A, B ... L
    $table->unsignedSmallInteger('rank')->default(1);
    $table->unsignedSmallInteger('played')->default(0);
    $table->unsignedSmallInteger('won')->default(0);
    $table->unsignedSmallInteger('drawn')->default(0);
    $table->unsignedSmallInteger('lost')->default(0);
    $table->smallInteger('goals_for')->default(0);
    $table->smallInteger('goals_against')->default(0);
    $table->smallInteger('goals_diff')->default(0);
    $table->smallInteger('points')->default(0);
    $table->string('form', 15)->nullable();        // "WWDLW"
    $table->string('status', 20)->nullable();      // "same", "up", "down"
    $table->string('description', 100)->nullable(); // "Promotion - Round of 32"
    $table->timestamp('synced_at')->nullable();
    $table->timestamps();

    $table->unique(['team_id', 'group_name']);
    $table->index(['group_name', 'rank']);
});
```

#### Migration 7 — `player_stats`

```php
// database/migrations/2026_01_01_000007_create_player_stats_table.php
Schema::create('player_stats', function (Blueprint $table) {
    $table->id();
    $table->foreignId('player_id')->constrained()->cascadeOnDelete()->index();
    $table->foreignId('team_id')->constrained()->index();
    $table->unsignedSmallInteger('appearances')->default(0);
    $table->unsignedSmallInteger('goals')->default(0);
    $table->unsignedSmallInteger('assists')->default(0);
    $table->unsignedSmallInteger('yellow_cards')->default(0);
    $table->unsignedSmallInteger('red_cards')->default(0);
    $table->unsignedSmallInteger('minutes_played')->default(0);
    $table->decimal('rating', 4, 2)->nullable();    // 0.00 — 10.00
    $table->unsignedSmallInteger('shots_total')->default(0);
    $table->unsignedSmallInteger('shots_on')->default(0);
    $table->unsignedSmallInteger('passes_total')->default(0);
    $table->decimal('passes_accuracy', 5, 2)->nullable();  // %
    $table->unsignedSmallInteger('tackles')->default(0);
    $table->unsignedSmallInteger('dribbles_success')->default(0);
    $table->timestamp('synced_at')->nullable();
    $table->timestamps();

    $table->unique('player_id');  // um registo por jogador (acumulado do torneio)
    $table->index(['goals', 'team_id']);    // topscorers
    $table->index(['assists', 'team_id']);  // topassists
    $table->index(['yellow_cards']);        // topyellowcards
});
```

#### Migration 8 — `api_quota_logs`

```php
// database/migrations/2026_01_01_000008_create_api_quota_logs_table.php
Schema::create('api_quota_logs', function (Blueprint $table) {
    $table->id();
    $table->string('endpoint', 150);                   // /fixtures, /injuries, etc.
    $table->string('layer', 10);                       // 'layer1' | 'layer2' | 'layer3'
    $table->unsignedSmallInteger('cost')->default(1);  // requisições gastas
    $table->unsignedSmallInteger('remaining')->nullable(); // x-ratelimit-requests-remaining
    $table->string('status', 20)->default('success'); // 'success' | 'error' | 'skipped'
    $table->text('notes')->nullable();
    $table->timestamp('called_at')->useCurrent()->index();

    // sem timestamps() pois esta tabela é append-only
});

// Tabela de controlo diário (resumo)
Schema::create('api_quota_daily', function (Blueprint $table) {
    $table->id();
    $table->date('date')->unique()->index();
    $table->unsignedSmallInteger('used')->default(0);
    $table->unsignedSmallInteger('remaining')->default(100);
    $table->unsignedSmallInteger('reserved_for_layer3')->default(0); // calculado com base nos jogos do dia
    $table->boolean('budget_alert_sent')->default(false); // alerta aos 80%
    $table->timestamps();
});
```

#### Migration 9 — `gemini_response_cache`

```php
// database/migrations/2026_01_01_000009_create_gemini_response_cache_table.php
Schema::create('gemini_response_cache', function (Blueprint $table) {
    $table->id();
    $table->string('cache_key', 64)->unique()->index(); // SHA256 de fixture_id + chip_type
    $table->unsignedInteger('fixture_id_api')->nullable()->index();
    $table->string('chip_type', 50)->nullable();        // 'tactical_flash', 'injury_impact', 'head2head'
    $table->longText('response');                       // resposta do Gemini
    $table->unsignedSmallInteger('tokens_used')->nullable();
    $table->timestamp('expires_at')->index();
    $table->timestamp('created_at')->useCurrent();
});
```

#### Migration 10 — `push_subscriptions`

```php
// database/migrations/2026_01_01_000010_create_push_subscriptions_table.php
Schema::create('push_subscriptions', function (Blueprint $table) {
    $table->id();
    $table->string('fcm_token', 512)->unique()->index();
    $table->string('device_type', 20)->nullable();   // 'web' | 'ios' | 'android'
    $table->jsonb('subscribed_fixtures')->default('[]'); // [fixture_api_id, ...]
    $table->jsonb('subscribed_teams')->default('[]');    // [team_api_id, ...]
    $table->boolean('notify_lineups')->default(true);
    $table->boolean('notify_goals')->default(false);    // MVP: não implementado
    $table->timestamp('last_active_at')->nullable();
    $table->timestamps();
});
```

#### Migration 11 — `sync_logs`

```php
// database/migrations/2026_01_01_000011_create_sync_logs_table.php
Schema::create('sync_logs', function (Blueprint $table) {
    $table->id();
    $table->string('job_class', 100)->index();     // SyncTeamsJob, SyncInjuriesJob, etc.
    $table->string('layer', 10);                   // 'layer1' | 'layer2' | 'layer3'
    $table->string('status', 20);                  // 'started' | 'completed' | 'failed' | 'skipped'
    $table->unsignedInteger('records_synced')->default(0);
    $table->unsignedSmallInteger('api_requests_used')->default(0);
    $table->text('error_message')->nullable();
    $table->unsignedInteger('duration_ms')->nullable();
    $table->timestamp('started_at')->useCurrent()->index();
    $table->timestamp('completed_at')->nullable();
});
```

### 9.3 Índices de Performance Adicionais (Migration separada)

```php
// database/migrations/2026_01_01_000012_add_performance_indexes.php
Schema::table('fixtures', function (Blueprint $table) {
    // Index parcial para jogos ativos (PostgreSQL-specific)
    DB::statement("
        CREATE INDEX idx_fixtures_active_today
        ON fixtures (kickoff_utc, status_short)
        WHERE status_short NOT IN ('FT', 'AET', 'PEN', 'PST', 'CANC')
    ");
});

Schema::table('player_statuses', function (Blueprint $table) {
    DB::statement("
        CREATE INDEX idx_active_injuries
        ON player_statuses (team_id, type)
        WHERE is_active = true
    ");
});
```

### 9.4 Seeders Necessários (para desenvolvimento)

```
database/seeders/
├── TeamSeeder.php           # 48 seleções com dados mock
├── PlayerSeeder.php         # ~23 jogadores por seleção
├── FixtureSeeder.php        # 104 jogos do torneio (fase de grupos + eliminatórias)
├── StandingSeeder.php       # Tabelas iniciais dos 12 grupos
└── DatabaseSeeder.php       # Orquestra a ordem de execução
```

---

## 10. Estratégia de Sincronização API-Football

> **Orçamento:** 100 requisições/dia (partilhadas entre todas as camadas).
> O `ApiFootballQuotaService` é **pré-requisito** de todos os jobs. Não implementar jobs sem ele.

### 10.1 Camadas de Sincronização

#### Camada 1 — Dados Estáticos (~5 req/dia)

| Job | Endpoint | Frequência | Req/exec |
|---|---|---|---|
| `SyncTeamsJob` | `/teams?league=1&season=2026` | 1x/dia (03:00 UTC) | 1 |
| `SyncPlayersJob` | `/players?league=1&season=2026&page=N` | 1x/dia (03:30 UTC) | 2-3 |
| `SyncFixturesJob` | `/fixtures?league=1&season=2026` | 1x/dia (04:00 UTC) | 1 |
| `SyncTopStatsJob` | `/topscorers` + `/topassists` + `/topyellowcards` | 1x/dia (04:30 UTC) | 3 |

#### Camada 2 — Dados Semi-Dinâmicos (~5 req/dia)

| Job | Endpoint | Frequência | Req/exec |
|---|---|---|---|
| `SyncInjuriesJob` | `/injuries?league=1&season=2026` | 3x/dia (07h, 13h, 18h UTC) | 1 |
| `SyncStandingsJob` | `/standings?league=1&season=2026` | 2x/dia (após 22h e 01h UTC) | 1 |

#### Camada 3 — Dados ao Vivo (~10-20 req/jogo)

| Job | Endpoint | Janela | Intervalo |
|---|---|---|---|
| `PollLineupJob` | `/fixtures?id={id}` | -70min até kickoff | 10min → 5min |
| — | — | Após confirmação | PARAR imediatamente |

### 10.2 `ApiFootballQuotaService`

```php
// app/Services/ApiFootballQuotaService.php
// Responsabilidade única: gestão do orçamento de 100 req/dia

interface ApiFootballQuotaServiceInterface
{
    public function canProceed(string $layer): bool;
    public function recordUsage(string $endpoint, string $layer, int $remaining): void;
    public function getRemainingBudget(): int;
    public function getReservedForLayer3(): int;    // calculado com base nos jogos do dia
    public function sendAlertIfNeeded(): void;       // alerta a 80% de consumo
}
```

**Lógica de reserva para Camada 3:**
- Contar jogos agendados nas próximas 24h → `$gamesCount`
- Por jogo: máximo ~15 req (10min interval × 7 polls = 7 req, 5min interval × 6 polls = 6 req, margem = 2)
- `$reserved = $gamesCount * 15`
- Camadas 1+2 só prosseguem se `remaining > $reserved + 20` (buffer de segurança)

---

## 11. Estrutura de Pastas do Projeto

### Frontend (React + TypeScript)

```
frontend/
├── public/
│   └── icons/            # PWA icons, favicon
├── src/
│   ├── app/
│   │   ├── providers/
│   │   │   ├── QueryProvider.tsx        # TanStack Query
│   │   │   ├── EchoProvider.tsx         # Laravel Echo / Reverb
│   │   │   └── ThemeProvider.tsx        # Dark/Light mode
│   │   └── router/
│   │       └── index.tsx                # React Router v6
│   ├── components/
│   │   ├── ui/                          # Gerado pelo shadcn (não editar diretamente)
│   │   ├── layout/
│   │   │   ├── Navbar.tsx
│   │   │   ├── MobileDrawer.tsx
│   │   │   └── PageWrapper.tsx
│   │   ├── fixtures/
│   │   │   ├── FixtureCard.tsx          # Card de jogo (glass)
│   │   │   ├── FixtureList.tsx
│   │   │   ├── LiveScore.tsx            # Score com animação
│   │   │   └── FixtureDetail/
│   │   │       ├── index.tsx
│   │   │       ├── LineupGrid.tsx       # Campo tático 11x11
│   │   │       ├── PlayerCard.tsx       # Com status de lesão
│   │   │       └── TacticalFlash.tsx    # Bloco IA Gemini
│   │   ├── injuries/
│   │   │   ├── InjuryPanel.tsx
│   │   │   ├── InjuryBadge.tsx
│   │   │   └── InjuryTooltip.tsx
│   │   ├── standings/
│   │   │   ├── StandingsTable.tsx
│   │   │   └── GroupCard.tsx
│   │   ├── gemini/
│   │   │   ├── ChatWindow.tsx           # Chat livre
│   │   │   ├── ContextChips.tsx         # Chips dinâmicos
│   │   │   └── TypingIndicator.tsx      # Animação de resposta
│   │   └── common/
│   │       ├── TeamLogo.tsx
│   │       ├── PlayerAvatar.tsx
│   │       ├── SkeletonCard.tsx
│   │       └── LiveBadge.tsx
│   ├── hooks/
│   │   ├── useFixtures.ts
│   │   ├── useLineup.ts
│   │   ├── useInjuries.ts
│   │   ├── useStandings.ts
│   │   ├── useGeminiChat.ts
│   │   ├── useEcho.ts                   # WebSocket hook
│   │   └── usePushNotifications.ts      # FCM
│   ├── stores/
│   │   ├── themeStore.ts                # Zustand — dark/light
│   │   └── notificationStore.ts         # Zustand — push tokens
│   ├── lib/
│   │   ├── axios.ts                     # Instância configurada
│   │   ├── echo.ts                      # Laravel Echo setup
│   │   ├── firebase.ts                  # FCM setup
│   │   ├── utils.ts                     # cn(), formatDate(), etc.
│   │   └── constants.ts                 # LEAGUE_ID, etc.
│   ├── pages/
│   │   ├── HomePage.tsx                 # Jogos do dia + próximos
│   │   ├── FixturePage.tsx              # Detalhe de um jogo
│   │   ├── StandingsPage.tsx            # Tabelas dos grupos
│   │   ├── InjuriesPage.tsx             # Painel de desfalques
│   │   └── AiPage.tsx                   # Chat Gemini completo
│   ├── types/
│   │   ├── fixture.ts
│   │   ├── team.ts
│   │   ├── player.ts
│   │   ├── lineup.ts
│   │   ├── injury.ts
│   │   ├── standing.ts
│   │   └── gemini.ts
│   ├── main.tsx
│   └── index.css
├── tailwind.config.ts
├── vite.config.ts
├── tsconfig.json
└── package.json
```

### Backend (Laravel)

```
backend/
├── app/
│   ├── Console/
│   │   └── Commands/
│   │       └── SyncApiFootballCommand.php  # Manual trigger
│   ├── Events/
│   │   ├── LineupConfirmed.php
│   │   └── LiveScoreUpdated.php
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── FixtureController.php
│   │   │   ├── LineupController.php
│   │   │   ├── InjuryController.php
│   │   │   ├── StandingController.php
│   │   │   ├── PlayerController.php
│   │   │   └── GeminiController.php
│   │   └── Resources/
│   │       ├── FixtureResource.php
│   │       ├── LineupResource.php
│   │       └── PlayerStatusResource.php
│   ├── Jobs/
│   │   ├── SyncTeamsJob.php
│   │   ├── SyncPlayersJob.php
│   │   ├── SyncFixturesJob.php
│   │   ├── SyncInjuriesJob.php
│   │   ├── SyncStandingsJob.php
│   │   ├── SyncTopStatsJob.php
│   │   └── PollLineupJob.php
│   ├── Models/
│   │   ├── Team.php
│   │   ├── Player.php
│   │   ├── PlayerStatus.php
│   │   ├── Fixture.php
│   │   ├── FixtureLineup.php
│   │   ├── Standing.php
│   │   ├── PlayerStat.php
│   │   ├── ApiQuotaLog.php
│   │   ├── ApiQuotaDaily.php
│   │   ├── GeminiResponseCache.php
│   │   └── PushSubscription.php
│   └── Services/
│       ├── ApiFootballService.php          # HTTP client para a API
│       ├── ApiFootballQuotaService.php     # Gestão de orçamento (pré-requisito)
│       ├── GeminiService.php               # Integração Gemini Flash
│       ├── LineupPollingService.php        # Gestão do polling adaptativo
│       └── FcmNotificationService.php     # Envio de push via FCM
├── routes/
│   ├── api.php
│   └── channels.php                       # Reverb channel authorization
└── config/
    ├── reverb.php
    └── services.php
```

---

## 12. Escopo do MVP & Requisitos Funcionais

### RF01 — Gestão e Exibição de Desfalques

- **Dados:** `/injuries?league=1&season=2026`, 3x/dia (Camada 2), tabela `player_statuses`
- **UI:** Alertas visuais nos cards de jogadores (opacidade reduzida + ícone) com `Tooltip` shadcn ao hover; Painel centralizado na aba "Lesões/Suspensões" (`InjuryPanel.tsx`)
- **IA:** Ao clicar no chip `🏥 Impacto das Ausências`, Gemini Flash analisa o impacto tático das baixas
- **Componentes:** `InjuryBadge`, `InjuryTooltip`, `InjuryPanel`, `PlayerCard` (com estado de lesão)
- **Animação:** `framer-motion` — `AnimatePresence` para entrada/saída dos badges de lesão

### RF02 — Escalação Oficial em Tempo Real

- **Dados:** Polling adaptativo de `/fixtures?id={id}`, Camada 3, controlado pelo `ApiFootballQuotaService`
- **UI Live:** WebSocket via `Laravel Reverb` → `Event LineupConfirmed` → atualização do `LineupGrid.tsx` sem refresh
- **Push:** `FcmNotificationService` dispara para `push_subscriptions` associadas ao fixture quando `lineup_confirmed = true`
- **IA (Flash Tático):** Após confirmar os 22 titulares → job assíncrono → `GeminiService` → resultado guardado em `gemini_response_cache` → exposto via `TacticalFlash.tsx`
- **Componentes:** `LineupGrid`, `TacticalFlash`, `LiveBadge`, `PlayerCard`
- **Animação:** `framer-motion` — transição de escalação provável → escalação oficial com `layoutId`

### RF03 — Calendário e Resultados

- **Dados:** `/fixtures?league=1&season=2026`, Camada 1
- **UI:** Lista de jogos agrupada por dia; cards com `FixtureCard.tsx`; filtro por grupo/fase
- **Componentes:** `FixtureList`, `FixtureCard`, `LiveScore`

### RF04 — Tabelas de Classificação

- **Dados:** `/standings?league=1&season=2026`, Camada 2
- **UI:** Grid de 12 grupos com `StandingsTable.tsx`; animação de subida/descida de posição
- **Componentes:** `StandingsTable`, `GroupCard`

### RF05 — Módulo de Interação Gemini

- **Chat Livre:** `ChatWindow.tsx` — input com `Button variant="ghost"` de envio
- **Chips Contextuais:** `ContextChips.tsx` — botões horizontais que variam por ecrã ativo
  - Chips disponíveis: `📊 Raio-X do Confronto` · `🏥 Impacto das Ausências` · `⚡ Flash Tático` · `🎯 Palpite Guiado` · `📈 Forma Recente`
- **Contexto Oculto:** Backend injeta dados brutos (fixture, standings, injuries) no prompt antes de enviar ao Gemini
- **Cache:** `gemini_response_cache` — TTL 10min por `(fixture_id + chip_type)`
- **Componentes:** `ChatWindow`, `ContextChips`, `TypingIndicator`
- **Animação:** `framer-motion` — `AnimatePresence` para mensagens; efeito de digitação no `TypingIndicator`

---

## 13. Arquitetura de Componentes React

### Padrão de Composição

Todos os componentes seguem o padrão **Compound Components** quando têm múltiplos sub-elementos, e **Render Props / Hooks customizados** para lógica partilhada.

```typescript
// Exemplo: FixtureCard — composição clara
<FixtureCard fixture={fixture}>
  <FixtureCard.Teams />
  <FixtureCard.Score />
  <FixtureCard.Status />
  <FixtureCard.Actions />
</FixtureCard>
```

### Padrão de Loading States

Nunca bloquear o UI. Usar sempre Skeleton + `AnimatePresence`:

```typescript
// Padrão obrigatório em todos os componentes de dados
if (isLoading) return <SkeletonCard />;
if (isError)   return <ErrorState message={error.message} />;
return <ActualComponent data={data} />;
```

### Gestão de Estado

| Tipo de Estado | Solução |
|---|---|
| Dados do servidor (API REST) | `TanStack Query` — cache, invalidação, refetch |
| Estado global UI (tema, drawer) | `Zustand` |
| Estado de formulários | `useState` local |
| Estado real-time (WebSocket) | `useEcho` hook + `TanStack Query` invalidation |

---

## 14. Diretivas de UI/UX — Quiet Luxury

### Princípios Fundamentais

1. **Menos é mais.** Se um elemento não tem função, remover.
2. **Hierarquia tipográfica rigorosa.** Máximo 3 tamanhos de fonte por ecrã.
3. **Movimento com propósito.** Framer Motion só onde adiciona contexto, nunca decoração.
4. **Dark first.** Desenhar sempre em dark mode; light mode é uma adaptação.
5. **Respirabilidade.** `padding` e `gap` generosos — mínimo `p-4` em containers.

### Regras de Navegação

```typescript
// Navbar com glassmorphism — backdrop-blur obrigatório
<nav className="fixed top-0 w-full z-50 nav-glass">
  {/* height: 64px, padding: 0 24px */}
</nav>
```

### Regras de Formulários e Inputs

```typescript
// Input sem borda sólida — ring no foco
<Input
  className="bg-surface border-border focus:border-gold
             focus:ring-2 focus:ring-gold/15 transition-all duration-200
             placeholder:text-muted-foreground"
/>

// Botão Ghost para chips de IA
<Button variant="ghost"
  className="text-muted-foreground hover:text-foreground
             hover:bg-surface-overlay transition-colors duration-150">
  {chip.label}
</Button>
```

### Regras de Animação (Framer Motion)

```typescript
// Variantes padrão do projeto — importar de lib/animations.ts
export const fadeIn = {
  initial: { opacity: 0, y: 8 },
  animate: { opacity: 1, y: 0 },
  exit:    { opacity: 0, y: -4 },
  transition: { duration: 0.25, ease: 'easeOut' }
};

export const stagger = {
  animate: { transition: { staggerChildren: 0.07 } }
};

// Cards de jogadores na escalação
export const playerCardVariant = {
  initial: { opacity: 0, scale: 0.95 },
  animate: { opacity: 1, scale: 1 },
  transition: { type: 'spring', stiffness: 300, damping: 25 }
};
```

### Regras de Dark/Light Mode

```typescript
// ThemeProvider.tsx — persistir em localStorage
const [theme, setTheme] = useState<'dark' | 'light'>(() =>
  localStorage.getItem('theme') as 'dark' | 'light' ?? 'dark'
);

// Aplicar classe na raiz
document.documentElement.classList.toggle('dark', theme === 'dark');

// Sempre definir ambos os modos nas classes Tailwind
<div className="bg-surface dark:bg-surface text-foreground dark:text-foreground">
```

### Regras de Responsividade

```
Mobile first. Breakpoints:
sm: 640px  — telemóveis maiores
md: 768px  — tablets
lg: 1024px — desktops
xl: 1280px — desktops largos (máximo de 1280px de conteúdo, centralizado)
```

---

## 15. Fluxo de Trabalho e Guardrails da IA

### Testes Obrigatórios

- Escrever testes (unitários + integração) para cada nova funcionalidade antes de marcar como concluída.
- Ao corrigir um bug: primeiro criar um teste que reproduz o bug, depois aplicar a correção.
- **Backend:** PHPUnit + Laravel HTTP Testing para endpoints e jobs.
- **Frontend:** Vitest + Testing Library para componentes e hooks.
- **CI:** GitHub Actions em cada push (`php artisan test` + `vitest run`).

### Controlo de Versão (Git)

- Commits granulares após cada subtarefa. Formato: `feat(rf01): add injury badge to PlayerCard`
- Prefixos obrigatórios: `feat`, `fix`, `refactor`, `test`, `docs`, `chore`
- Nunca commitar `.env`, `firebase-credentials.json` ou qualquer chave de API.

### Sessões de Refatoração

- Ao atingir >3 falhas consecutivas no mesmo problema: parar, avisar o utilizador, aguardar reinício do contexto.
- Periodicamente: quebrar componentes React grandes (>200 linhas) em sub-componentes.
- Remover código morto, comentários desnecessários e imports não utilizados.

### Validação de Orçamento (Antes de Novas Features)

Qualquer nova funcionalidade que envolva chamadas à API-Football deve:
1. Calcular o custo adicional de requisições/dia.
2. Verificar contra o orçamento da Secção 10 — se exceder, propor alternativa (ex.: usar dados já em cache).
3. Atualizar a tabela de camadas neste documento antes de implementar.

### Segurança de Credenciais

- **Nunca** inserir API keys, tokens ou passwords no código-fonte.
- Usar **exclusivamente** variáveis de ambiente (`.env`) e a helper `config()` do Laravel.
- O ficheiro `.env.example` deve listar todas as variáveis com valores placeholder, e ser o único ficheiro de ambiente no repositório.

---

*Última revisão: v3 — documento gerado para uso como guia de codificação do CopApp (Mundial 2026).*
