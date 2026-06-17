# frontend/

> React 18 · TypeScript · Vite · Tailwind CSS · shadcn/ui

CopApp frontend — companion app for the FIFA World Cup 2026.

## Quick Start

```bash
# Install dependencies
npm install

# Copy environment template
cp .env.example .env.local
# Fill in VITE_API_BASE_URL, VITE_REVERB_*, VITE_FIREBASE_* values

# Start dev server
npm run dev
```

## shadcn/ui Initialisation (run once after npm install)

```bash
npx shadcn@latest init
# Select: Dark mode → "class", Base color → "zinc", CSS variables → yes
```

Then install components as needed (see CONTEXT.md §6):

```bash
npx shadcn@latest add sheet navigation-menu tabs scroll-area separator
npx shadcn@latest add dialog popover tooltip toast alert
npx shadcn@latest add badge avatar card table skeleton
npx shadcn@latest add input button switch dropdown-menu
```

## Scripts

| Command | Description |
|---------|-------------|
| `npm run dev` | Start Vite dev server (port 5173) |
| `npm run build` | Type-check + production build |
| `npm run test` | Run Vitest in CI mode |
| `npm run test:watch` | Vitest watch mode |
| `npm run lint` | ESLint |
| `npm run format` | Prettier |

## Folder Structure

```
src/
├── app/
│   ├── providers/    # QueryProvider, ThemeProvider, EchoProvider
│   └── router/       # React Router v6 with lazy-loaded pages
├── components/
│   ├── ui/           # shadcn/ui generated components (do not edit directly)
│   ├── layout/       # Navbar, PageWrapper, MobileDrawer
│   ├── fixtures/     # FixtureCard, FixtureList, LiveScore, FixtureDetail/
│   ├── injuries/     # InjuryPanel, InjuryBadge, InjuryTooltip
│   ├── standings/    # StandingsTable, GroupCard
│   ├── gemini/       # ChatWindow, ContextChips, TypingIndicator
│   └── common/       # TeamLogo, PlayerAvatar, SkeletonCard, LiveBadge
├── hooks/            # useFixtures, useLineup, useInjuries, useStandings,
│                     # useGeminiChat, useEcho, usePushNotifications
├── stores/           # themeStore, notificationStore (Zustand)
├── lib/              # axios, echo, firebase, utils, constants, animations
├── pages/            # HomePage, FixturePage, StandingsPage, InjuriesPage, AiPage
└── types/            # fixture, team, player, lineup, injury, standing, gemini
```

## Design System

Palette: **Quiet Luxury Dark** — see `tailwind.config.ts` and `src/index.css`.

| Token | Value |
|-------|-------|
| `--background` | `#0A0C10` |
| `--gold` | `#C9A84C` |
| `--surface` | `#111318` |
| `--foreground` | `#E8EAF0` |

Fonts: **Bricolage Grotesque** (titles), **Inter** (body), **JetBrains Mono** (scores/stats).
