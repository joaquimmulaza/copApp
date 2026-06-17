// TypingIndicator — animated dots while Gemini is generating a response
import { motion } from "framer-motion";

const DOT_VARIANTS = {
  animate: (i: number) => ({
    opacity: [0.3, 1, 0.3],
    scale: [0.9, 1.1, 0.9],
    transition: {
      delay: i * 0.2,
      duration: 1.2,
      repeat: Infinity,
      ease: "easeInOut",
    },
  }),
};

export const TypingIndicator = () => (
  <div
    role="status"
    aria-label="Gemini está a processar..."
    className="flex items-center gap-1.5 px-4 py-3 card-glass w-fit rounded-2xl border border-border/50"
  >
    {[0, 1, 2].map((i) => (
      <motion.span
        key={i}
        custom={i}
        variants={DOT_VARIANTS}
        animate="animate"
        className="size-1.5 rounded-full bg-gold shadow-[0_0_6px_rgba(201,168,76,0.5)]"
        aria-hidden="true"
      />
    ))}
    <span className="sr-only">Gemini está a processar a resposta</span>
  </div>
);
