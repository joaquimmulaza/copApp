// App root — composes all providers and the router
import { QueryProvider } from '@/app/providers/QueryProvider'
import { ThemeProvider } from '@/app/providers/ThemeProvider'
import { AppRouter } from '@/app/router'

const App = () => (
  <ThemeProvider>
    <QueryProvider>
      <AppRouter />
    </QueryProvider>
  </ThemeProvider>
)

export default App
