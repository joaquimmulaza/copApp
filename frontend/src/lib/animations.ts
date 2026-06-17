// ─── Framer Motion animation variants ────────────────────────
// Import these in components instead of defining inline variants.
// Consistency across the app is critical for Quiet Luxury feel.

import type { Variants } from "framer-motion";

// ─── Page / card entrance ─────────────────────────────────────
export const fadeIn: Variants = {
  initial: { opacity: 0, y: 8 },
  animate: { opacity: 1, y: 0 },
  exit: { opacity: 0, y: -4 },
  // transition is added inline where needed for override flexibility
};

export const fadeInTransition = {
  duration: 0.25,
  ease: "easeOut",
} as const;

// ─── Stagger parent ───────────────────────────────────────────
// Wrap a list with this + children with fadeIn
export const stagger: Variants = {
  animate: {
    transition: { staggerChildren: 0.07 },
  },
};

// ─── Lineup player cards (spring) ─────────────────────────────
export const playerCardVariant: Variants = {
  initial: { opacity: 0, scale: 0.95 },
  animate: { opacity: 1, scale: 1 },
};

export const playerCardTransition = {
  type: "spring",
  stiffness: 300,
  damping: 25,
} as const;

// ─── Slide-up (for sheet / drawer content) ───────────────────
export const slideUp: Variants = {
  initial: { opacity: 0, y: 16 },
  animate: { opacity: 1, y: 0 },
  exit: { opacity: 0, y: 16 },
};

export const slideUpTransition = {
  type: "spring",
  stiffness: 400,
  damping: 30,
} as const;

// ─── Score update (scale + gold flash) ────────────────────────
// Applied imperatively via useAnimation when a live score changes.
export const scoreUpdateKeyframes = {
  scale: [1, 1.15, 1],
  color: ["#E8EAF0", "#C9A84C", "#E8EAF0"],
} as const;

export const scoreUpdateTransition = {
  duration: 0.5,
  ease: [0.34, 1.56, 0.64, 1], // cubic-bezier spring
} as const;

// ─── Chat message entrance ────────────────────────────────────
export const chatMessage: Variants = {
  initial: { opacity: 0, x: -8 },
  animate: { opacity: 1, x: 0 },
  exit: { opacity: 0 },
};

export const chatMessageTransition = {
  duration: 0.2,
  ease: "easeOut",
} as const;
