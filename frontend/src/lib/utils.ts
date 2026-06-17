import { type ClassValue, clsx } from 'clsx'
import { twMerge } from 'tailwind-merge'
import { format, formatDistanceToNow } from 'date-fns'
import { ptBR } from 'date-fns/locale'

// ─── cn() — shadcn/ui compatible class merger ─────────────────
// Merges Tailwind classes intelligently (no duplicates / conflicts)
export const cn = (...inputs: ClassValue[]) => twMerge(clsx(inputs))

// ─── Date formatters ─────────────────────────────────────────
/**
 * Format a UTC date string to locale-aware kick-off time.
 * Example: "20:00" or "20:00 UTC+1"
 */
export const formatKickoffTime = (utcDate: string): string => {
  try {
    return format(new Date(utcDate), 'HH:mm')
  } catch {
    return '--:--'
  }
}

/**
 * Format a UTC date string to a readable date.
 * Example: "Ter, 18 Jun"
 */
export const formatMatchDate = (utcDate: string): string => {
  try {
    return format(new Date(utcDate), "EEE, d MMM", { locale: ptBR })
  } catch {
    return '—'
  }
}

/**
 * Relative time from now.
 * Example: "em 2 horas" or "há 5 minutos"
 */
export const formatRelative = (utcDate: string): string => {
  try {
    return formatDistanceToNow(new Date(utcDate), {
      addSuffix: true,
      locale: ptBR,
    })
  } catch {
    return '—'
  }
}

// ─── Score helpers ────────────────────────────────────────────
/**
 * Returns a formatted score string. Null scores display as "–".
 */
export const formatScore = (
  home: number | null,
  away: number | null
): string => {
  if (home === null || away === null) return '– : –'
  return `${home} : ${away}`
}

// ─── String helpers ───────────────────────────────────────────
/**
 * Truncate a string to a maximum length with an ellipsis.
 */
export const truncate = (str: string, maxLength: number): string =>
  str.length > maxLength ? `${str.slice(0, maxLength)}…` : str

/**
 * Return initials from a full name.
 * "Cristiano Ronaldo" → "CR"
 */
export const getInitials = (name: string): string =>
  name
    .split(' ')
    .slice(0, 2)
    .map((n) => n[0]?.toUpperCase() ?? '')
    .join('')

// ─── Status helpers ───────────────────────────────────────────
/**
 * Returns true if a fixture status code represents a live match.
 */
export const isLiveStatus = (status: string): boolean =>
  ['1H', 'HT', '2H', 'ET', 'P', 'BT', 'INT'].includes(status)

/**
 * Returns true if the fixture has been completed (any end state).
 */
export const isFinishedStatus = (status: string): boolean =>
  ['FT', 'AET', 'PEN'].includes(status)

/**
 * Maps a fixture status short code to a human-readable label (PT).
 */
export const getStatusLabel = (status: string): string => {
  const labels: Record<string, string> = {
    NS:  'Por jogar',
    '1H': '1.ª Parte',
    HT:  'Intervalo',
    '2H': '2.ª Parte',
    ET:  'Prorrogação',
    P:   'Penáltis',
    FT:  'Terminado',
    AET: 'Após Prorrogação',
    PEN: 'Após Penáltis',
    PST: 'Adiado',
    CANC:'Cancelado',
    BT:  'Intervalo (Prorrog.)',
    INT: 'Interrompido',
    ABD: 'Abandonado',
    AWD: 'Walkover',
    WO:  'Walkover',
  }
  return labels[status] ?? status
}

// ─── Async / Promise helpers ──────────────────────────────────
/**
 * Typed sleep — await sleep(ms) to add a non-blocking delay.
 * Useful for skeleton minimum display durations.
 */
export const sleep = (ms: number): Promise<void> =>
  new Promise((resolve) => setTimeout(resolve, ms))
