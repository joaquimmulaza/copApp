// MobileDrawer — sheet-based side navigation for small screens
// Uses shadcn Sheet component (to be installed separately)
// Placeholder structure — sheet component added when shadcn is initialised
import type { ReactNode } from "react";

interface MobileDrawerProps {
  readonly isOpen: boolean;
  readonly onClose: () => void;
  readonly children?: ReactNode;
}

/**
 * Mobile navigation drawer.
 * Wraps shadcn Sheet — install with: npx shadcn@latest add sheet
 */
export const MobileDrawer = ({
  isOpen,
  onClose,
  children,
}: MobileDrawerProps) => {
  // Sheet component will replace this placeholder once shadcn is initialised
  if (!isOpen) return null;

  return (
    <div
      role="dialog"
      aria-modal="true"
      aria-label="Menu de navegação"
      className="fixed inset-0 z-50 flex"
    >
      {/* Overlay */}
      <button
        type="button"
        onClick={onClose}
        className="absolute inset-0 bg-black/50 backdrop-blur-sm"
        aria-label="Fechar menu"
        tabIndex={0}
        onKeyDown={(e) => e.key === "Enter" && onClose()}
      />

      {/* Panel */}
      <div className="relative z-10 w-72 h-full bg-surface-elevated border-r border-border p-6">
        {children}
      </div>
    </div>
  );
};
