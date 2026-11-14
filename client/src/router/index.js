import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import HistoryView from '@/views/HistoryView.vue'
import LoginView from '@/views/LoginView.vue'
import ModerationView from '@/views/ModerationView.vue'
import PublicMapView from '@/views/PublicMapView.vue'
import RegisterView from '@/views/RegisterView.vue'
import ReportsView from '@/views/ReportsView.vue'
import SubscriptionsView from '@/views/SubscriptionsView.vue'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    { path: '/', name: 'map', component: PublicMapView },
    { path: '/history', name: 'history', component: HistoryView },
    { path: '/login', name: 'login', component: LoginView, meta: { guestOnly: true } },
    { path: '/register', name: 'register', component: RegisterView, meta: { guestOnly: true } },
    {
      path: '/reports',
      name: 'reports',
      component: ReportsView,
      meta: { requiresAuth: true },
    },
    {
      path: '/subscriptions',
      name: 'subscriptions',
      component: SubscriptionsView,
      meta: { requiresAuth: true },
    },
    {
      path: '/moderation',
      name: 'moderation',
      component: ModerationView,
      meta: { requiresAuth: true, requiresModerator: true },
    },
  ],
})

router.beforeEach(async (to) => {
  const authStore = useAuthStore()

  if (!authStore.initialized) {
    await authStore.loadCurrentUser()
  }

  if (to.meta.requiresAuth && !authStore.isAuthenticated) {
    return { name: 'login' }
  }

  if (to.meta.requiresModerator && !authStore.user?.is_moderator) {
    return { name: 'map' }
  }

  if (to.meta.guestOnly && authStore.isAuthenticated) {
    return { name: 'reports' }
  }

  return true
})

export default router
