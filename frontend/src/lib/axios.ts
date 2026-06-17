import axios from "axios";
import { API_BASE_URL } from "@/lib/constants";

// ─── Configured Axios instance ────────────────────────────────
// All API calls should import this instead of the bare axios module.
export const api = axios.create({
  baseURL: '/api',
  timeout: 10_000,
  headers: {
    Accept: "application/json",
    "Content-Type": "application/json",
    "X-Requested-With": "XMLHttpRequest",
  },
  withCredentials: true, // Required for Laravel Sanctum cookie auth
});

// ─── Request Interceptor ──────────────────────────────────────
// Attach CSRF token from the meta tag (standard Laravel SPA pattern)
api.interceptors.request.use((config) => {
  const csrfMeta = document.querySelector<HTMLMetaElement>(
    'meta[name="csrf-token"]',
  );
  if (csrfMeta) {
    config.headers["X-CSRF-TOKEN"] = csrfMeta.content;
  }
  return config;
});

// ─── Response Interceptor ─────────────────────────────────────
// Normalise error shape so hooks always deal with a consistent structure
api.interceptors.response.use(
  (response) => response,
  (error: unknown) => {
    if (axios.isAxiosError(error)) {
      // Let the caller handle — TanStack Query will catch and surface it
      return Promise.reject(error);
    }
    return Promise.reject(new Error("Unknown network error"));
  },
);
