<script setup>
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const authStore = useAuthStore()

async function handleLogout() {
  await authStore.logout()
  router.push('/login')
}
</script>

<template>
  <header class="navbar">
    <RouterLink to="/" class="label-mono">Outage Map Platform</RouterLink>

    <nav class="navbar__links">
      <template v-if="authStore.isAuthenticated">
        <RouterLink to="/reports">My Reports</RouterLink>
        <button type="button" class="btn btn-secondary" @click="handleLogout">Log out</button>
      </template>
      <template v-else>
        <RouterLink to="/login">Log in</RouterLink>
        <RouterLink to="/register">Register</RouterLink>
      </template>
    </nav>
  </header>
</template>
