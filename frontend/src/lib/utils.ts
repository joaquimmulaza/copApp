import { clsx, type ClassValue } from 'clsx'
import { twMerge } from 'tailwind-merge'
import { format, isToday, isTomorrow, isYesterday, parseISO } from 'date-fns'
import { ptBR } from 'date-fns/locale'
import type { FixtureStatusShort } from '@/types/fixture'
import { LIVE_STATUSES, FINISHED_STATUSES } from '@/lib/constants'

// ─── Class merge helper ───────────────────────────────────────
export function cn(...inputs: ClassValue[]): string {
  return twMerge(clsx(inputs))
}

// ─── Team name initials (2 chars) ─────────────────────────────
export function getInitials(name: string): string {
  const words = name.trim().split(/\s+/)
  if (words.length === 1) return words[0].slice(0, 2).toUpperCase()
  return (words[0][0] + words[words.length - 1][0]).toUpperCase()
}

// ─── Status helpers ───────────────────────────────────────────
export function isLive(status: FixtureStatusShort): boolean {
  return (LIVE_STATUSES as readonly string[]).includes(status)
}

export function isFinished(status: FixtureStatusShort): boolean {
  return (FINISHED_STATUSES as readonly string[]).includes(status)
}

// ─── Date formatting ──────────────────────────────────────────
// Returns human-readable label for a kickoff date string (ISO-8601 UTC)
export function formatDateLabel(isoDate: string): string {
  const date = parseISO(isoDate)
  if (isToday(date))     return 'Hoje'
  if (isTomorrow(date))  return 'Amanhã'
  if (isYesterday(date)) return 'Ontem'
  return format(date, "EEEE, d 'de' MMMM", { locale: ptBR })
}

// Returns only the date portion "YYYY-MM-DD" for grouping
export function getDateKey(isoDate: string): string {
  return isoDate.slice(0, 10)
}

// Formats kickoff time as "HH:mm" in local timezone
export function formatKickoff(isoDate: string): string {
  return format(parseISO(isoDate), 'HH:mm')
}

// ─── Group array by a key-extractor ──────────────────────────
export function groupBy<T>(
  arr: readonly T[],
  keyFn: (item: T) => string,
): Map<string, T[]> {
  const map = new Map<string, T[]>()
  for (const item of arr) {
    const key = keyFn(item)
    const existing = map.get(key)
    if (existing) {
      existing.push(item)
    } else {
      map.set(key, [item])
    }
  }
  return map
}

