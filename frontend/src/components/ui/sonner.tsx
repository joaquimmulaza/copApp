/**
 * sonner.tsx — CopApp-patched Toaster component
 *
 * Overrides the default shadcn/sonner wrapper to use our Zustand themeStore
 * instead of next-themes (which is not in this stack).
 */
import { Toaster as Sonner, type ToasterProps } from "sonner";
import {
  CircleCheckIcon,
  InfoIcon,
  TriangleAlertIcon,
  OctagonXIcon,
  Loader2Icon,
} from "lucide-react";
import { useThemeStore } from "@/stores/themeStore";

const Toaster = ({ ...props }: ToasterProps) => {
  const rawTheme = useThemeStore((s) => s.theme);
  // Narrow to the exact union Sonner expects (never undefined)
  const resolvedTheme: "dark" | "light" = rawTheme === "light" ? "light" : "dark";

  return (
    <Sonner
    theme={resolvedTheme}
      className="toaster group"
      icons={{
        success: <CircleCheckIcon className="size-4" />,
        info: <InfoIcon className="size-4" />,
        warning: <TriangleAlertIcon className="size-4" />,
        error: <OctagonXIcon className="size-4" />,
        loading: <Loader2Icon className="size-4 animate-spin" />,
      }}
      style={
        {
          "--normal-bg": "var(--surface-elevated)",
          "--normal-text": "var(--foreground)",
          "--normal-border": "var(--border)",
          "--border-radius": "var(--radius)",
          "--success-bg": "var(--surface-elevated)",
          "--success-border": "var(--border)",
          "--success-text": "var(--foreground)",
        } as React.CSSProperties
      }
      toastOptions={{
        classNames: {
          toast:
            "font-sans text-sm !bg-[var(--surface-elevated)] !border-[var(--border)] !text-[var(--foreground)]",
          description: "!text-[var(--muted-foreground)]",
        },
      }}
      position="bottom-right"
      richColors
      closeButton
      {...props}
    />
  );
};

export { Toaster };
