import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import { resolve } from 'path'

// https://vitejs.dev/config/
export default defineConfig(({ command, mode }) => ({
  plugins: [vue()],
  resolve: {
    alias: {
      '@': resolve(__dirname, 'src'),
    },
  },
  esbuild: {
    // Strip console logs and debugger statements in production
    drop: mode === 'production' ? ['console', 'debugger'] : []
  },
  server: {
    port: 3000,
    host: true,
    proxy: {
      // Proxy media file serving to backend
      '/media/': {
        target: 'http://localhost:8000',
        changeOrigin: true,
        secure: false,
      },
      '/uploads/':{
        target: 'http://localhost:8000',
        changeOrigin: true,
        secure: false,
      },
      '/storage/': {
        target: 'http://localhost:8000',
        changeOrigin: true,
        secure: false
      },
      // Proxy thumbnail serving to backend
      '/thumbnails': {
        target: 'http://localhost:8000',
        changeOrigin: true,
        secure: false,
      },
      // Proxy secure media serving to backend
      '/secure-media': {
        target: 'http://localhost:8000',
        changeOrigin: true,
        secure: false,
      },
      // Proxy stock media original media serving to backend
      'api/media/proxy':{
        target: 'http://localhost:8000',
        changeOrigin: true,
        secure: true,
        rewrite: (path) => path.replace(/^\/api\/media\/proxy/, '/api/media/proxy')
      },
      // Proxy API endpoints to backend
      '/api': {
        target: 'http://localhost:8000',
        changeOrigin: true,
        secure: false,
      },
    },
  },
  build: {
    outDir: 'dist',
    sourcemap: true,
    rollupOptions: {
      output: {
        manualChunks: {
          vendor: ['vue', 'vue-router', 'pinia'],
          konva: ['konva', 'vue-konva'],
          utils: ['axios', '@vueuse/core', 'lodash-es']
        }
      }
    }
  },
  optimizeDeps: {
    include: ['konva', 'vue-konva']
  }
}))
