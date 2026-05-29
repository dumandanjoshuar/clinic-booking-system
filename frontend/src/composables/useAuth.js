import { reactive } from 'vue'
import api from '../services/api'

const state = reactive({
  token: localStorage.getItem('clinic_auth_token'),
  user: null,
  loading: false,
})

export function useAuth() {
  async function login(credentials) {
    state.loading = true

    try {
      const { data } = await api.post('/auth/login', credentials)
      state.token = data.token
      state.user = data.user
      localStorage.setItem('clinic_auth_token', data.token)
      return data.user
    } finally {
      state.loading = false
    }
  }

  async function fetchMe() {
    if (!state.token) {
      state.user = null
      return null
    }

    const { data } = await api.get('/auth/me')
    state.user = data.user
    return data.user
  }

  async function logout() {
    try {
      if (state.token) {
        await api.post('/auth/logout')
      }
    } finally {
      state.token = null
      state.user = null
      localStorage.removeItem('clinic_auth_token')
    }
  }

  async function changePassword(payload) {
    state.loading = true

    try {
      const { data } = await api.post('/auth/change-password', payload)
      state.user = data.user
      return data.user
    } finally {
      state.loading = false
    }
  }

  return {
    state,
    login,
    logout,
    fetchMe,
    changePassword,
  }
}
