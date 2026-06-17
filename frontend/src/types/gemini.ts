// ─── Gemini AI domain types ──────────────────────────────────
import type { GeminiChipId } from '@/lib/constants'

export type MessageRole = 'user' | 'assistant'

export interface ChatMessage {
  readonly id: string          // UUID — local-only, not from server
  readonly role: MessageRole
  readonly content: string
  readonly timestamp: number   // Date.now()
  readonly isStreaming?: boolean // true while the assistant is typing
}

export interface GeminiChatRequest {
  readonly fixture_id: number
  readonly message: string
  readonly chip_type?: GeminiChipId | null
}

export interface GeminiChatResponse {
  readonly response: string
  readonly tokens_used: number | null
  readonly cached: boolean
}

export interface GeminiChip {
  readonly id: GeminiChipId
  readonly label: string
}

// Cache entry shape from the backend resource
export interface GeminiCacheEntry {
  readonly cache_key: string
  readonly fixture_id_api: number
  readonly chip_type: GeminiChipId
  readonly response: string
  readonly tokens_used: number | null
  readonly expires_at: string
}
