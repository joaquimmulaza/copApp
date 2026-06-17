import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import { ReactQueryDevtools } from '@tanstack/react-query-devtools'
import type { ReactNode } from 'react'

// Configure global defaults for all queries in the app
const queryClient = new QueryClient({
  defaultOptions: {
    queries: {
      // Data is considered fresh for 30 seconds
      staleTime: 30 * 1000,
      // Cache retained for 5 minutes when component unmounts
      gcTime: 5 * 60 * 1000,
      // Retry failed requests up to 2 times with exponential backoff
      retry: 2,
      retryDelay: (attemptIndex) => Math.min(1000 * 2 ** attemptIndex, 30_000),
      // Refetch when the window regains focus (important for live data)
      refetchOnWindowFocus: true,
    },
    mutations: {
      // Mutations do not retry by default
      retry: 0,
    },
  },
})

interface QueryProviderProps {
  readonly children: ReactNode
}

export const QueryProvider = ({ children }: QueryProviderProps) => (
  <QueryClientProvider client={queryClient}>
    {children}
    {/* DevTools only rendered in development */}
    {import.meta.env.DEV && (
      <ReactQueryDevtools initialIsOpen={false} position="bottom" />
    )}
  </QueryClientProvider>
)
