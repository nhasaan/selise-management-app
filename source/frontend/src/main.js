import { createApp } from 'vue';
import { createPinia } from 'pinia';
import App from './App.vue';
import router from './router';
import './assets/styles/main.css';
import axios from 'axios';

// Set up axios defaults
axios.defaults.baseURL =
  import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000';

// Create application
const app = createApp(App);

// Create and use Pinia store
const pinia = createPinia();
app.use(pinia);

// Use Vue Router
app.use(router);

// Mount the app
app.mount('#app');

// For development debugging
if (import.meta.env.DEV) {
  console.log('Running in development mode');
  window.app = app;
}
