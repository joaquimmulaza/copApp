import Echo from 'laravel-echo'
import Pusher from 'pusher-js'

// Expose Pusher globally so Laravel Echo can find it
declare global {
  interface Window {
    Pusher: typeof Pusher
  }
}

window.Pusher = Pusher

// ─── Echo instance type alias ─────────────────────────────────
export type EchoInstance = Echo<'reverb'>

let echoInstance: EchoInstance | null = null

// ─── Factory — creates or returns the singleton Echo instance ─
export const createEchoInstance = (): EchoInstance => {
  if (echoInstance) return echoInstance

  echoInstance = new Echo({
    broadcaster: 'reverb',
    key:         import.meta.env.VITE_REVERB_APP_KEY,
    wsHost:      import.meta.env.VITE_REVERB_HOST     ?? 'localhost',
    wsPort:      Number(import.meta.env.VITE_REVERB_PORT ?? 8080),
    wssPort:     Number(import.meta.env.VITE_REVERB_PORT ?? 8080),
    forceTLS:    import.meta.env.VITE_REVERB_SCHEME === 'https',
    enabledTransports: ['ws', 'wss'],
    disableStats: true,
  })

  return echoInstance
}

export const getEchoInstance = (): EchoInstance | null => echoInstance
