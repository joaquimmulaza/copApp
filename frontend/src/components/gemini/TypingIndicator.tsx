// TypingIndicator — animated dots while Gemini is generating a response
import { motion } from "framer-motion";

const DOT_VARIANTS = {
  animate: (i: number) => ({
    y: [0, -4, 0],
    transition: {
      delay: i * 0.15,
      duration: 0.6,
      repeat: Infinity,
      ease: "easeInOut",
    },
  }),
};

export const TypingIndicator = () => (
  <div
    role="status"
    aria-label="Gemini está a processar..."
    className="flex items-center gap-1 p-3 card-glass w-fit rounded-2xl"
  >
    {[0, 1, 2].map((i) => (
      <motion.span
        key={i}
        custom={i}
        variants={DOT_VARIANTS}
        animate="animate"
        className="size-1.5 rounded-full bg-muted-foreground"
        aria-hidden="true"
      />
    ))}
    <span className="sr-only">Gemini está a processar a resposta</span>
  </div>
);
