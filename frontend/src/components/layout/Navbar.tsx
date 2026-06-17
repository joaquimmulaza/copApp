// Navbar — glassmorphism top navigation with push notification toggle
// Applies .nav-glass utility from index.css
import { Link, NavLink } from "react-router-dom";
import { Trophy, Calendar, Shield, HeartPulse, Bot, Bell, BellOff } from "lucide-react";
import { cn } from "@/lib/utils";
import { useThemeStore } from "@/stores/themeStore";
import { usePushNotifications } from "@/hooks/usePushNotifications";
import { Switch } from "@/components/ui/switch";
import { useState } from "react";

const NAV_ITEMS = [
  { to: "/", label: "Jogos", icon: Calendar },
  { to: "/standings", label: "Classificação", icon: Trophy },
  { to: "/injuries", label: "Desfalques", icon: HeartPulse },
  { to: "/ai", label: "Análise IA", icon: Bot },
] as const;

export const Navbar = () => {
  const { toggleTheme, theme } = useThemeStore();
  const { isPermissionGranted, permissionStatus, requestPermission } =
    usePushNotifications();
  const [isRequesting, setIsRequesting] = useState(false);

  // Notifications are not applicable on unsupported browsers
  const notificationsSupported =
    typeof Notification !== "undefined" && "serviceWorker" in navigator;

  const handleNotificationToggle = async (checked: boolean) => {
    if (!checked || isPermissionGranted) return; // Only act on "enable"
    if (isRequesting) return;

    setIsRequesting(true);
    try {
      await requestPermission();
    } finally {
      setIsRequesting(false);
    }
  };

  const NotificationBellIcon = isPermissionGranted ? Bell : BellOff;

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

        {/* ─── Right controls ─── */}
        <div className="flex items-center gap-3">
          {/* ─── Push notification toggle ─── */}
          {notificationsSupported && (
            <div
              className="hidden md:flex items-center gap-2"
              title={
                permissionStatus === "denied"
                  ? "Notificações bloqueadas nas definições do browser"
                  : isPermissionGranted
                  ? "Notificações push activadas"
                  : "Activar notificações de escalação"
              }
            >
              <NotificationBellIcon
                className={cn(
                  "size-4 transition-colors duration-200",
                  isPermissionGranted
                    ? "text-gold"
                    : "text-muted-foreground",
                )}
                aria-hidden="true"
              />
              <Switch
                id="notification-toggle"
                aria-label="Activar ou desactivar notificações push"
                checked={isPermissionGranted}
                onCheckedChange={handleNotificationToggle}
                disabled={
                  isRequesting ||
                  permissionStatus === "denied"
                }
                className={cn(
                  "transition-opacity duration-200",
                  // Subtle gold glow when active
                  isPermissionGranted &&
                    "data-[state=checked]:bg-gold-muted shadow-[0_0_0_1px_var(--gold-glow)]",
                )}
              />
            </div>
          )}

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
      </div>
    </header>
  );
};
