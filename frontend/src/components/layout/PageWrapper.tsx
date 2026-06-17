// PageWrapper — standard page layout shell
// Applies fixed navbar offset and max-width constraint
import type { ReactNode } from "react"
import { Navbar } from "./Navbar"

interface PageWrapperProps {
  readonly children: ReactNode
}

export const PageWrapper = ({ children }: PageWrapperProps) => (
  <>
    <Navbar />
    <main
      id="main-content"
      className="page-wrapper pt-24 pb-8 md:pt-28 md:pb-12"
      // Skip-to-content anchor target for keyboard navigation
    >
      {children}
    </main>
  </>
)

