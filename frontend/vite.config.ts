import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import { resolve } from 'path'

// https://vitejs.dev/config/
export default defineConfig(({ command, mode }) => {
  // Get base path from environment variable, default to '/'
  // Ensure base path starts and ends with '/' for proper routing
  let basePath = process.env.VITE_BASE_PATH || '/'
  if (!basePath.startsWith('/')) basePath = '/' + basePath
  if (!basePath.endsWith('/')) basePath = basePath + '/'
  
  // Debug logging to verify base path is being read correctly
  console.log(`Vite base path configured as: ${basePath}`)
  
  return {
    plugins: [vue()],
    base: basePath,
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
      // Force assets to use the base path in development
      origin: command === 'serve' ? `http://localhost:3000${basePath}` : undefined,
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
        '/api/media/proxy':{
          target: 'http://localhost:8000',
          changeOrigin: true,
          secure: false,
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
      sourcemap: mode === 'development',
      // Ensure assets are built with the correct base path
      assetsDir: 'assets',
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
  }
})
