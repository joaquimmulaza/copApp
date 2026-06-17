// MobileDrawer — sheet-based side navigation for small screens
// Includes push notification toggle at the bottom of the panel
import type { ReactNode } from "react";
import { NavLink } from "react-router-dom";
import { Trophy, Calendar, Shield, HeartPulse, Bot, Bell, BellOff, X } from "lucide-react";
import { cn } from "@/lib/utils";
import { Switch } from "@/components/ui/switch";
import { usePushNotifications } from "@/hooks/usePushNotifications";
import { useState } from "react";

const NAV_ITEMS = [
  { to: "/", label: "Jogos", icon: Calendar },
  { to: "/standings", label: "Classificação", icon: Trophy },
  { to: "/injuries", label: "Desfalques", icon: HeartPulse },
  { to: "/ai", label: "Análise IA", icon: Bot },
] as const;

interface MobileDrawerProps {
  readonly isOpen: boolean;
  readonly onClose: () => void;
  readonly children?: ReactNode;
}

/**
 * Mobile navigation drawer.
 * Includes a push notification toggle control at the footer section.
 */
export const MobileDrawer = ({
  isOpen,
  onClose,
  children,
}: MobileDrawerProps) => {
  const { isPermissionGranted, permissionStatus, requestPermission } =
    usePushNotifications();
  const [isRequesting, setIsRequesting] = useState(false);

  const notificationsSupported =
    typeof Notification !== "undefined" && "serviceWorker" in navigator;

  const handleNotificationToggle = async (checked: boolean) => {
    if (!checked || isPermissionGranted) return;
    if (isRequesting) return;
    setIsRequesting(true);
    try {
      await requestPermission();
    } finally {
      setIsRequesting(false);
    }
  };

  const NotificationBellIcon = isPermissionGranted ? Bell : BellOff;

  if (!isOpen) return null;

  return (
    <div
      role="dialog"
      aria-modal="true"
      aria-label="Menu de navegação"
      className="fixed inset-0 z-50 flex"
    >
      {/* ─── Overlay ─── */}
      <button
        type="button"
        onClick={onClose}
        className="absolute inset-0 bg-black/60 backdrop-blur-sm"
        aria-label="Fechar menu"
        tabIndex={0}
        onKeyDown={(e) => e.key === "Enter" && onClose()}
      />

      {/* ─── Panel ─── */}
      <div className="relative z-10 w-72 h-full bg-surface-elevated border-r border-border flex flex-col">
        {/* Header */}
        <div className="flex items-center justify-between px-6 py-5 border-b border-border">
          <div className="flex items-center gap-2">
            <Shield className="size-5 text-gold" aria-hidden="true" />
            <span className="font-display font-semibold text-base">
              Copa<span className="text-gold">App</span>
            </span>
          </div>
          <button
            type="button"
            onClick={onClose}
            aria-label="Fechar menu"
            className="p-1.5 rounded-md text-muted-foreground hover:text-foreground hover:bg-surface-overlay transition-colors"
          >
            <X className="size-4" />
          </button>
        </div>

        {/* Navigation links */}
        <nav
          role="navigation"
          aria-label="Navegação móvel"
          className="flex-1 px-4 py-4 flex flex-col gap-1"
        >
          {NAV_ITEMS.map(({ to, label, icon: Icon }) => (
            <NavLink
              key={to}
              to={to}
              end={to === "/"}
              onClick={onClose}
              className={({ isActive }) =>
                cn(
                  "flex items-center gap-3 px-3 py-3 rounded-lg text-sm font-medium",
                  "transition-colors duration-150",
                  isActive
                    ? "text-gold bg-surface-overlay"
                    : "text-muted-foreground hover:text-foreground hover:bg-surface-overlay",
                )
              }
            >
              <Icon className="size-5 shrink-0" aria-hidden="true" />
              {label}
            </NavLink>
          ))}

          {children}
        </nav>

        {/* ─── Footer: notification toggle ─── */}
        {notificationsSupported && (
          <div className="px-6 py-5 border-t border-border">
            <div className="flex items-center justify-between">
              <div className="flex items-center gap-2.5">
                <NotificationBellIcon
                  className={cn(
                    "size-4 transition-colors",
                    isPermissionGranted ? "text-gold" : "text-muted-foreground",
                  )}
                  aria-hidden="true"
                />
                <div>
                  <p className="text-sm font-medium text-foreground">
                    Notificações
                  </p>
                  <p className="text-xs text-muted-foreground mt-0.5">
                    {permissionStatus === "denied"
                      ? "Bloqueadas no browser"
                      : isPermissionGranted
                      ? "Escalações e alertas"
                      : "Activar alertas de jogo"}
                  </p>
                </div>
              </div>

              <Switch
                id="mobile-notification-toggle"
                aria-label="Activar ou desactivar notificações push"
                checked={isPermissionGranted}
                onCheckedChange={handleNotificationToggle}
                disabled={isRequesting || permissionStatus === "denied"}
                className={cn(
                  "transition-opacity duration-200",
                  isPermissionGranted &&
                    "data-[state=checked]:bg-gold-muted shadow-[0_0_0_1px_var(--gold-glow)]",
                )}
              />
            </div>
          </div>
        )}
      </div>
    </div>
  );
};
