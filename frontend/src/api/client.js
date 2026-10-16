import axios from 'axios'

export const API_URL = import.meta.env.VITE_API_URL ?? 'http://127.0.0.1:8000/api'

const TOKEN_KEY = 'em_token'

export const tokenStorage = {
  get: () => localStorage.getItem(TOKEN_KEY),
  set: (token) => localStorage.setItem(TOKEN_KEY, token),
  clear: () => localStorage.removeItem(TOKEN_KEY),
}

const client = axios.create({
  baseURL: API_URL,
  headers: { Accept: 'application/json' },
})

client.interceptors.request.use((config) => {
  const token = tokenStorage.get()
  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }
  return config
})

let onUnauthorized = () => {}

/**
 * Lets the auth context react when the API says the session is no longer valid.
 */
export function setUnauthorizedHandler(handler) {
  onUnauthorized = handler
}

client.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401 && tokenStorage.get()) {
      tokenStorage.clear()
      onUnauthorized(error.response.data?.message)
    }
    return Promise.reject(error)
  },
)

export default client
