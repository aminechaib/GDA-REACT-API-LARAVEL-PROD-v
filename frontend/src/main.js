import './assets/new-ui.css'

import { createApp } from 'vue'
import App from './App.vue'
import router from './router' // We will create this file next
import axios from 'axios'

// 1. Set your Laravel API base URL
axios.defaults.baseURL = 'http://localhost:8000/api'
axios.defaults.headers.common['Accept'] = 'application/json'

// 2. Check if user is already logged in (token exists in localStorage )
const token = localStorage.getItem('auth_token')
if (token) {
    axios.defaults.headers.common['Authorization'] = `Bearer ${token}`
}

// 3. Create and mount the app
const app = createApp(App)
app.use(router) // Tell Vue to use the router
app.mount('#app')
