import { defineStore } from 'pinia'
import * as authService from '@/services/auth'

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: null,
    initialized: false,
  }),
  getters: {
    isAuthenticated: (state) => state.user !== null,
  },
  actions: {
    async register(payload) {
      this.user = await authService.register(payload)
    },
    async login(payload) {
      this.user = await authService.login(payload)
    },
    async logout() {
      await authService.logout()
      this.user = null
    },
    async loadCurrentUser() {
      try {
        this.user = await authService.fetchCurrentUser()
      } catch {
        this.user = null
      } finally {
        this.initialized = true
      }
    },
  },
})
