import { create } from "zustand";
import { persist } from "zustand/middleware";
import { STORAGE_KEYS } from "@/lib/constants";

type Theme = "dark" | "light";

interface ThemeStore {
  readonly theme: Theme;
  readonly setTheme: (theme: Theme) => void;
  readonly toggleTheme: () => void;
}

export const useThemeStore = create<ThemeStore>()(
  persist(
    (set, get) => ({
      theme: "dark",

      setTheme: (theme) => {
        set({ theme });
        // Apply class to <html> immediately — ThemeProvider also does this,
        // but the store can be used without the provider in tests.
        document.documentElement.classList.toggle("dark", theme === "dark");
        document.documentElement.classList.toggle("light", theme === "light");
      },

      toggleTheme: () => {
        const next = get().theme === "dark" ? "light" : "dark";
        get().setTheme(next);
      },
    }),
    {
      name: STORAGE_KEYS.THEME,
      // Persist only the 'theme' value — actions are re-created on hydration
      partialize: (state) => ({ theme: state.theme }),
    },
  ),
);
