import { createApp } from 'vue'
import { createPinia } from 'pinia'
import router from './router/index'
import App from './App.vue'
import './assets/css/main.css'
import './assets/css/scrollbars.css'

// Initialize Pinia store
const pinia = createPinia()
const app = createApp(App)

app.use(pinia)
app.use(router)

// Initialize theme store after Pinia is set up
import { useThemeStore } from './stores/theme'
const themeStore = useThemeStore()

app.mount('#app')
