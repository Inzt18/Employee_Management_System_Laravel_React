import { createContext, useCallback, useContext, useEffect, useMemo, useState } from 'react'
import { setUnauthorizedHandler, tokenStorage } from '../api/client'
import { authApi } from '../api/services'

const AuthContext = createContext(null)

export function AuthProvider({ children }) {
  const [user, setUser] = useState(null)
  const [loading, setLoading] = useState(Boolean(tokenStorage.get()))
  const [notice, setNotice] = useState(null)

  const signOutLocally = useCallback((reason = null) => {
    tokenStorage.clear()
    setUser(null)
    setNotice(reason)
  }, [])

  /**
   * Stores a token (from login, register or Google) and loads the profile.
   */
  const acceptToken = useCallback(async (token, employee = null) => {
    tokenStorage.set(token)
    const profile = employee ?? (await authApi.me())
    setUser(profile)
    setNotice(null)
    return profile
  }, [])

  const login = useCallback(
    async (credentials) => {
      const { token, employee } = await authApi.login(credentials)
      return acceptToken(token, employee)
    },
    [acceptToken],
  )

  const register = useCallback(
    async (values) => {
      const { token, employee } = await authApi.register(values)
      return acceptToken(token, employee)
    },
    [acceptToken],
  )

  const logout = useCallback(async () => {
    try {
      await authApi.logout()
    } finally {
      signOutLocally()
    }
  }, [signOutLocally])

  const refreshUser = useCallback(async () => setUser(await authApi.me()), [])

  useEffect(() => {
    setUnauthorizedHandler((message) => signOutLocally(message || 'Your session has ended. Please sign in again.'))
  }, [signOutLocally])

  useEffect(() => {
    if (!tokenStorage.get()) return
    authApi
      .me()
      .then(setUser)
      .catch(() => signOutLocally())
      .finally(() => setLoading(false))
  }, [signOutLocally])

  const value = useMemo(
    () => ({
      user,
      loading,
      notice,
      isAdmin: user?.role === 'Admin',
      login,
      register,
      logout,
      acceptToken,
      refreshUser,
    }),
    [user, loading, notice, login, register, logout, acceptToken, refreshUser],
  )

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>
}

export function useAuth() {
  const context = useContext(AuthContext)
  if (!context) throw new Error('useAuth must be used inside <AuthProvider>')
  return context
}

export function homePathFor(user) {
  return user?.role === 'Admin' ? '/admin' : '/dashboard'
}
