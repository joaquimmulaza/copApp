// App root — composes all providers, the router, and global singletons
import { useEffect } from "react";
import { QueryProvider } from "@/app/providers/QueryProvider";
import { ThemeProvider } from "@/app/providers/ThemeProvider";
import { AppRouter } from "@/app/router";
import { Toaster } from "@/components/ui/sonner";
import { registerFcmServiceWorker } from "@/lib/firebase";

/**
 * FCM SW bootstrap — called once on app mount.
 * We register the service worker early so getToken() can reference it.
 * This is a no-op if the browser doesn't support service workers.
 */
const FcmBootstrap = () => {
  useEffect(() => {
    // Register in the background — don't block UI render
    registerFcmServiceWorker().catch(console.warn);
  }, []);

  return null;
};

const App = () => (
  <ThemeProvider>
    <QueryProvider>
      <FcmBootstrap />
      <AppRouter />
      {/* Global toast renderer — positioned bottom-right, styled for Quiet Luxury */}
      <Toaster />
    </QueryProvider>
  </ThemeProvider>
);

export default App;
