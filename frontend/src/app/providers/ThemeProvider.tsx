import { createContext, useContext, useEffect, useState } from 'react'
import type { ReactNode } from 'react'

type Theme = 'dark' | 'light'

interface ThemeContextValue {
  readonly theme: Theme
  readonly toggleTheme: () => void
  readonly setTheme: (theme: Theme) => void
}

const ThemeContext = createContext<ThemeContextValue | null>(null)

interface ThemeProviderProps {
  readonly children: ReactNode
  readonly defaultTheme?: Theme
}

export const ThemeProvider = ({
  children,
  defaultTheme = 'dark',
}: ThemeProviderProps) => {
  // Persist theme in localStorage; fall back to defaultTheme
  const [theme, setThemeState] = useState<Theme>(() => {
    try {
      const stored = localStorage.getItem('copapp-theme') as Theme | null
      return stored === 'dark' || stored === 'light' ? stored : defaultTheme
    } catch {
      // localStorage unavailable (SSR / private browsing edge case)
      return defaultTheme
    }
  })

  // Sync <html> class and localStorage whenever theme changes
  useEffect(() => {
    const root = document.documentElement
    root.classList.toggle('dark', theme === 'dark')
    root.classList.toggle('light', theme === 'light')

    try {
      localStorage.setItem('copapp-theme', theme)
    } catch {
      // Silently ignore — theme won't persist but UX is unaffected
    }
  }, [theme])

  const setTheme = (next: Theme) => setThemeState(next)

  const toggleTheme = () =>
    setThemeState((current) => (current === 'dark' ? 'light' : 'dark'))

  return (
    <ThemeContext.Provider value={{ theme, toggleTheme, setTheme }}>
      {children}
    </ThemeContext.Provider>
  )
}

// Convenience hook — throws if used outside provider
export const useTheme = (): ThemeContextValue => {
  const ctx = useContext(ThemeContext)
  if (!ctx) {
    throw new Error('useTheme must be used inside <ThemeProvider>')
  }
  return ctx
}
