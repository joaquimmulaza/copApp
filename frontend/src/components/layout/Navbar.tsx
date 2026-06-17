// Navbar — glassmorphism top navigation
// Applies .nav-glass utility from index.css
import { Link, NavLink } from "react-router-dom";
import { Trophy, Calendar, Shield, HeartPulse, Bot } from "lucide-react";
import { cn } from "@/lib/utils";
import { useThemeStore } from "@/stores/themeStore";

const NAV_ITEMS = [
  { to: "/", label: "Jogos", icon: Calendar },
  { to: "/standings", label: "Classificação", icon: Trophy },
  { to: "/injuries", label: "Desfalques", icon: HeartPulse },
  { to: "/ai", label: "Análise IA", icon: Bot },
] as const;

export const Navbar = () => {
  const { toggleTheme, theme } = useThemeStore();

  return (
    <header
      role="banner"
      className="fixed top-0 inset-x-0 z-50 nav-glass h-[64px] flex items-center px-4 md:px-6"
    >
      <div className="flex items-center justify-between w-full max-w-screen-xl mx-auto">
        {/* ─── Logo ─── */}
        <Link
          to="/"
          className="flex items-center gap-2 focus-visible:outline-gold"
          aria-label="CopApp — ir para a página inicial"
        >
          <Shield className="size-6 text-gold" aria-hidden="true" />
          <span className="font-display font-semibold text-lg tracking-tight">
            Copa<span className="text-gold">App</span>
          </span>
        </Link>

        {/* ─── Desktop nav ─── */}
        <nav
          role="navigation"
          aria-label="Navegação principal"
          className="hidden md:flex items-center gap-1"
        >
          {NAV_ITEMS.map(({ to, label, icon: Icon }) => (
            <NavLink
              key={to}
              to={to}
              end={to === "/"}
              className={({ isActive }) =>
                cn(
                  "flex items-center gap-1.5 px-3 py-2 rounded-md text-sm font-medium",
                  "transition-colors duration-150",
                  isActive
                    ? "text-gold bg-surface-overlay"
                    : "text-muted-foreground hover:text-foreground hover:bg-surface-overlay",
                )
              }
            >
              <Icon className="size-4" aria-hidden="true" />
              {label}
            </NavLink>
          ))}
        </nav>

        {/* ─── Theme toggle ─── */}
        <button
          type="button"
          onClick={toggleTheme}
          aria-label={`Mudar para modo ${theme === "dark" ? "claro" : "escuro"}`}
          className="p-2 rounded-md text-muted-foreground hover:text-foreground hover:bg-surface-overlay transition-colors"
        >
          {theme === "dark" ? "☀️" : "🌙"}
        </button>
      </div>
    </header>
  );
};
