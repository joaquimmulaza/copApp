import type { Config } from 'tailwindcss'

const config: Config = {
  // Enable class-based dark mode (shadcn/ui requirement)
  darkMode: ['class'],

  // Scan all TypeScript/TSX source files
  content: ['./src/**/*.{ts,tsx}'],

  theme: {
    extend: {
      // ─── Colors — Quiet Luxury Dark palette ─────────────────
      // Values are HSL-ready CSS variable references so shadcn tokens
      // work alongside our custom palette without duplication.
      colors: {
        // Backgrounds
        background:          'var(--background)',
        surface:             'var(--surface)',
        'surface-elevated':  'var(--surface-elevated)',
        'surface-overlay':   'var(--surface-overlay)',

        // Borders
        border:              'var(--border)',
        'border-strong':     'var(--border-strong)',

        // Text
        foreground:          'var(--foreground)',
        'muted-foreground':  'var(--muted-foreground)',
        subtle:              'var(--subtle)',

        // Brand — FIFA Gold
        gold: {
          DEFAULT:           '#C9A84C',
          muted:             '#8B6F32',
        },

        // Semantic
        success:             '#22C55E',
        warning:             '#F59E0B',
        danger:              '#EF4444',
        info:                '#3B82F6',

        // shadcn/ui base tokens (required for component compat)
        primary: {
          DEFAULT:           'hsl(var(--primary))',
          foreground:        'hsl(var(--primary-foreground))',
        },
        secondary: {
          DEFAULT:           'hsl(var(--secondary))',
          foreground:        'hsl(var(--secondary-foreground))',
        },
        muted: {
          DEFAULT:           'hsl(var(--muted))',
          foreground:        'hsl(var(--muted-foreground-hsl))',
        },
        accent: {
          DEFAULT:           'hsl(var(--accent))',
          foreground:        'hsl(var(--accent-foreground))',
        },
        destructive: {
          DEFAULT:           'hsl(var(--destructive))',
          foreground:        'hsl(var(--destructive-foreground))',
        },
        card: {
          DEFAULT:           'hsl(var(--card))',
          foreground:        'hsl(var(--card-foreground))',
        },
        popover: {
          DEFAULT:           'hsl(var(--popover))',
          foreground:        'hsl(var(--popover-foreground))',
        },
        input:               'hsl(var(--input))',
        ring:                'hsl(var(--ring))',
      },

      // ─── Typography ─────────────────────────────────────────
      fontFamily: {
        // Body text, labels
        sans:    ['Inter', 'system-ui', 'sans-serif'],
        // Scores, stats, numeric data
        mono:    ['JetBrains Mono', 'monospace'],
        // Page titles, team names
        display: ['Bricolage Grotesque', 'Inter', 'sans-serif'],
      },

      // ─── Border Radius (shadcn/ui token mapping) ────────────
      borderRadius: {
        lg: 'var(--radius)',
        md: 'calc(var(--radius) - 2px)',
        sm: 'calc(var(--radius) - 4px)',
      },

      // ─── Backdrop Blur extras ────────────────────────────────
      backdropBlur: {
        xs: '2px',
      },

      // ─── Custom Animations ───────────────────────────────────
      animation: {
        // Loading skeleton shimmer (replace blink with wave)
        'skeleton-pulse': 'skeleton 1.5s ease-in-out infinite',
        'skeleton-shimmer': 'skeleton-shimmer 1.5s infinite',

        // Score updated (spring bounce + gold flash)
        'score-update': 'scoreUpdate 0.5s cubic-bezier(0.34, 1.56, 0.64, 1)',

        // Page / card entrance
        'slide-up':  'slideUp 0.3s ease-out',
        'fade-in':   'fadeIn 0.25s ease-out',

        // LIVE badge pulse
        'pulse-live': 'pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite',
      },

      // ─── Keyframes ───────────────────────────────────────────
      keyframes: {
        // Opacity-only skeleton pulse (subtle, non-distracting)
        skeleton: {
          '0%, 100%': { opacity: '1' },
          '50%':      { opacity: '0.4' },
        },

        // Wave shimmer (the gradient sweep skeleton)
        'skeleton-shimmer': {
          '0%':   { backgroundPosition: '200% 0' },
          '100%': { backgroundPosition: '-200% 0' },
        },

        // Score update bounce with gold colour flash
        scoreUpdate: {
          '0%':   { transform: 'scale(1)' },
          '50%':  { transform: 'scale(1.15)', color: '#C9A84C' },
          '100%': { transform: 'scale(1)' },
        },

        // Entrance from below
        slideUp: {
          '0%':   { transform: 'translateY(8px)', opacity: '0' },
          '100%': { transform: 'translateY(0)',   opacity: '1' },
        },

        // Simple fade entrance
        fadeIn: {
          '0%':   { opacity: '0' },
          '100%': { opacity: '1' },
        },
      },

      // ─── Spacing additions ───────────────────────────────────
      // Navbar height constant — used in layout padding-top
      spacing: {
        navbar: '64px',
      },
    },
  },

  plugins: [
    // Provides animate-* utilities required by shadcn/ui (accordion, dialog, etc.)
    require('tailwindcss-animate'),
  ],
}

export default config
