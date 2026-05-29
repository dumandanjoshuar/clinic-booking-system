import axios from 'axios'

const api = axios.create({
  baseURL: import.meta.env.VITE_API_BASE_URL || '/api',
  headers: {
    Accept: 'application/json',
  },
})

api.interceptors.request.use((config) => {
  const token = localStorage.getItem('clinic_auth_token')

  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }

  return config
})

export function getErrorMessage(error) {
  const response = error?.response?.data

  if (response?.errors) {
    const firstKey = Object.keys(response.errors)[0]
    return response.errors[firstKey]?.[0] || response.message || 'Please check the form and try again.'
  }

  return response?.message || error?.message || 'Something went wrong. Please try again.'
}

export default api
