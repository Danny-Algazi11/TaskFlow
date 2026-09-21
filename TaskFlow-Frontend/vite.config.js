import react from '@vitejs/plugin-react'
import { defineConfig } from 'vite'

// https://vite.dev/config/
export default defineConfig({
  plugins: [react()],
  server: {
    // Pinned, not just Vite's default: the backend's SANCTUM_STATEFUL_DOMAINS
    // and CORS config are locked to localhost:5173 specifically. strictPort
    // fails loudly instead of silently running on 5174+ if 5173 is busy,
    // which would otherwise break Sanctum's cookie auth in a confusing way.
    port: 5173,
    strictPort: true,
  },
  test: {
    environment: 'jsdom',
    setupFiles: './src/test/setup.js',
    globals: true,
  },
})
