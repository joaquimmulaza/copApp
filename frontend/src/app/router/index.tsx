import { BrowserRouter, Routes, Route, Navigate } from "react-router-dom";
import { lazy, Suspense } from "react";
import { PageWrapper } from "@/components/layout/PageWrapper";
import { SkeletonCard } from "@/components/common/SkeletonCard";

// ─── Lazy-loaded pages — split into separate chunks ─────────────
// Each page is a separate Vite chunk → faster initial load
const HomePage = lazy(() => import("@/pages/HomePage"));
const FixturePage = lazy(() => import("@/pages/FixturePage"));
const StandingsPage = lazy(() => import("@/pages/StandingsPage"));
const InjuriesPage = lazy(() => import("@/pages/InjuriesPage"));
const AiPage = lazy(() => import("@/pages/AiPage"));

// Suspense fallback — reuse the skeleton card for a smooth transition
const PageFallback = () => (
  <div className="page-wrapper flex flex-col gap-4 py-8">
    <SkeletonCard />
    <SkeletonCard />
    <SkeletonCard />
  </div>
);

export const AppRouter = () => (
  <BrowserRouter>
    <PageWrapper>
      <Suspense fallback={<PageFallback />}>
        <Routes>
          {/* ─── Main routes ─── */}
          <Route path="/" element={<HomePage />} />
          <Route path="/fixture/:id" element={<FixturePage />} />
          <Route path="/standings" element={<StandingsPage />} />
          <Route path="/injuries" element={<InjuriesPage />} />
          <Route path="/ai" element={<AiPage />} />

          {/* ─── Fallback — redirect unknown paths to home ─── */}
          <Route path="*" element={<Navigate to="/" replace />} />
        </Routes>
      </Suspense>
    </PageWrapper>
  </BrowserRouter>
);
