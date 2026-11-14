import { createPinia, setActivePinia } from 'pinia'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { useAuthStore } from './auth'
import * as authService from '@/services/auth'

vi.mock('@/services/auth')

beforeEach(() => {
  setActivePinia(createPinia())
  vi.clearAllMocks()
})

describe('auth store', () => {
  it('starts unauthenticated', () => {
    const store = useAuthStore()

    expect(store.isAuthenticated).toBe(false)
  })

  it('becomes authenticated after a successful login', async () => {
    authService.login.mockResolvedValue({ id: 1, name: 'Demo', email: 'demo@outagemap.test' })

    const store = useAuthStore()
    await store.login({ email: 'demo@outagemap.test', password: 'password' })

    expect(store.isAuthenticated).toBe(true)
    expect(store.user.email).toBe('demo@outagemap.test')
  });

  it('clears the user after logout', async () => {
    authService.login.mockResolvedValue({ id: 1, name: 'Demo', email: 'demo@outagemap.test' })
    authService.logout.mockResolvedValue()

    const store = useAuthStore()
    await store.login({ email: 'demo@outagemap.test', password: 'password' })
    await store.logout()

    expect(store.isAuthenticated).toBe(false)
    expect(store.user).toBeNull()
  })

  it('treats a failed current-user fetch as a guest', async () => {
    authService.fetchCurrentUser.mockRejectedValue(new Error('unauthenticated'))

    const store = useAuthStore()
    await store.loadCurrentUser()

    expect(store.isAuthenticated).toBe(false)
    expect(store.initialized).toBe(true)
  })
})
