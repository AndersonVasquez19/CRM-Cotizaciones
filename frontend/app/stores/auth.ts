import { defineStore } from 'pinia'
import type { User } from '~/types'

export const useAuthStore = defineStore('auth', () => {
  const user = ref<User | null>(null)
  const token = useCookie<string | null>('auth_token', {
    maxAge: 60 * 60 * 24 * 7,
    sameSite: 'lax',
  })

  const isAuthenticated = computed(() => Boolean(token.value))

  async function login(email: string, password: string): Promise<void> {
    const api = useApi()
    const res = await api.post<{ token: string; user: User }>('/auth/login', {
      email,
      password,
    })
    token.value = res.token
    user.value = res.user
  }

  async function fetchMe(): Promise<User | null> {
    if (!token.value) {
      user.value = null
      return null
    }
    try {
      const api = useApi()
      const res = await api.get<{ user: User }>('/auth/me')
      user.value = res.user
      return res.user
    } catch {
      token.value = null
      user.value = null
      return null
    }
  }

  async function logout(): Promise<void> {
    try {
      if (token.value) {
        const api = useApi()
        await api.post('/auth/logout')
      }
    } finally {
      token.value = null
      user.value = null
      await navigateTo('/login')
    }
  }

  return {
    user,
    token,
    isAuthenticated,
    login,
    fetchMe,
    logout,
  }
})
