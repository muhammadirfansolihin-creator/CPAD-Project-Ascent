import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import axios from 'axios'

const API = '/api'

export const useAuthStore = defineStore('auth', () => {
  const user  = ref(JSON.parse(localStorage.getItem('ce_user')  || 'null'))
  const token = ref(localStorage.getItem('ce_token') || null)

  const isLoggedIn = computed(() => !!token.value)
  const role       = computed(() => user.value?.role || null)

  function setAuth(t, u) {
    token.value = t
    user.value  = u
    localStorage.setItem('ce_token', t)
    localStorage.setItem('ce_user',  JSON.stringify(u))
    axios.defaults.headers.common['Authorization'] = `Bearer ${t}`
  }

  function initAxios() {
    if (token.value) axios.defaults.headers.common['Authorization'] = `Bearer ${token.value}`
  }

  async function login(email, password) {
    const { data } = await axios.post(`${API}/auth/login`, { email, password })
    setAuth(data.token, data.user)
    return data.user
  }

  async function register(name, email, password, role) {
    const { data } = await axios.post(`${API}/auth/register`, { name, email, password, role })
    setAuth(data.token, data.user)
    return data.user
  }

  function logout() {
    token.value = null
    user.value  = null
    localStorage.removeItem('ce_token')
    localStorage.removeItem('ce_user')
    delete axios.defaults.headers.common['Authorization']
  }

  initAxios()
  return { user, token, isLoggedIn, role, login, register, logout }
})
