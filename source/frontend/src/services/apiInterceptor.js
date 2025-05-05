import axios from 'axios';

// Create axios instance with default config
const apiClient = axios.create({
  baseURL: import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000/api',
  headers: {
    'Content-Type': 'application/json',
    Accept: 'application/json',
  },
  timeout: 10000, // 10 seconds
});

// Request interceptor
apiClient.interceptors.request.use(
  (config) => {
    // You can add auth token here if needed
    // const token = localStorage.getItem('token');
    // if (token) {
    //   config.headers.Authorization = `Bearer ${token}`;
    // }

    return config;
  },
  (error) => {
    return Promise.reject(error);
  }
);

// Response interceptor
apiClient.interceptors.response.use(
  (response) => {
    return response;
  },
  (error) => {
    // Handle common error responses
    const { response } = error;

    if (response) {
      // Log detailed error information for debugging
      console.error('API Error:', {
        url: response.config.url,
        status: response.status,
        statusText: response.statusText,
        data: response.data,
      });

      // Handle specific status codes
      switch (response.status) {
        case 401:
          // Handle unauthorized
          console.warn('Authentication error - user not authorized');
          break;
        case 403:
          // Handle forbidden
          console.warn('Permission denied');
          break;
        case 404:
          // Handle not found
          console.warn('Resource not found');
          break;
        case 422:
          // Handle validation errors
          console.warn('Validation errors:', response.data.errors);
          break;
        case 500:
          // Handle server errors
          console.error('Server error');
          break;
      }
    } else {
      // Network error or request cancelled
      console.error('Network error or request cancelled', error);
    }

    return Promise.reject(error);
  }
);

export default apiClient;
