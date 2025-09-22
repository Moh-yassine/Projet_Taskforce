import { createApp } from 'vue'
import App from './App.vue'
import router from './router'
import { authService } from './services/authService'
import { analyticsService } from './services/analyticsService'

import './assets/main.css'
import './assets/styles/global.css'

// Nettoyer les anciens tokens fake au démarrage
authService.cleanupOldTokens()

// Initialiser les services analytics
analyticsService.initialize()

const app = createApp(App)

app.use(router)

app.mount('#app')


