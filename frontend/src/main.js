/**
 * SmartLib — Vue 3 entry point.
 *
 * Creates the app, wires the router, and configures Axios so every
 * subsequent component just imports the shared client from `@/api/http`.
 */

import { createApp } from 'vue'
import App from './App.vue'
import router from './router'
import './assets/main.css'

// Make sure axios is configured BEFORE the app mounts.
import './api/http.js'

createApp(App).use(router).mount('#app')
