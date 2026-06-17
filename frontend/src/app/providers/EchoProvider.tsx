// EchoProvider — configures Laravel Echo with Reverb (WebSocket)
// This provider sets up the global Echo instance and makes it
// available via React context to all descendant components.
import {
  createContext,
  useContext,
  useEffect,
  useRef,
  type ReactNode,
} from 'react'

// Echo and Pusher types are declared in lib/echo.ts
// The actual Echo instance is a singleton (module-level)
interface EchoContextValue {
  readonly echo: import('@/lib/echo').EchoInstance | null
}

const EchoContext = createContext<EchoContextValue>({ echo: null })

interface EchoProviderProps {
  readonly children: ReactNode
}

export const EchoProvider = ({ children }: EchoProviderProps) => {
  const echoRef = useRef<import('@/lib/echo').EchoInstance | null>(null)

  useEffect(() => {
    // Lazy-import Echo only on the client (avoids SSR issues)
    import('@/lib/echo').then(({ createEchoInstance }) => {
      echoRef.current = createEchoInstance()
    })

    return () => {
      // Disconnect cleanly on unmount (page navigations, HMR)
      echoRef.current?.disconnect()
    }
  }, [])

  return (
    <EchoContext.Provider value={{ echo: echoRef.current }}>
      {children}
    </EchoContext.Provider>
  )
}

export const useEchoContext = () => useContext(EchoContext)
