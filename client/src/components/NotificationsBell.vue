<script setup>
import { onBeforeUnmount, onMounted, ref } from 'vue'
import * as notificationsService from '@/services/notifications'
import { formatDateTime } from '@/utils/time'

const POLL_INTERVAL_MS = 60000

const notifications = ref([])
const unreadCount = ref(0)
const isOpen = ref(false)

let pollTimer = null

async function loadNotifications() {
  const response = await notificationsService.fetchNotifications()
  notifications.value = response.data
  unreadCount.value = response.unread_count
}

async function toggle() {
  isOpen.value = !isOpen.value

  if (isOpen.value) {
    await loadNotifications()
  }
}

async function handleRead(notification) {
  if (notification.read_at) {
    return
  }

  await notificationsService.markNotificationAsRead(notification.id)
  notification.read_at = new Date().toISOString()
  unreadCount.value = Math.max(0, unreadCount.value - 1)
}

onMounted(() => {
  loadNotifications()
  pollTimer = setInterval(loadNotifications, POLL_INTERVAL_MS)
})

onBeforeUnmount(() => {
  clearInterval(pollTimer)
})
</script>

<template>
  <div class="notifications-bell">
    <button type="button" class="btn btn-secondary" @click="toggle">
      Notifications
      <span v-if="unreadCount > 0" class="badge">{{ unreadCount }}</span>
    </button>

    <div v-if="isOpen" class="notifications-bell__panel card stack">
      <p v-if="notifications.length === 0" class="state-message">No notifications yet.</p>
      <div
        v-for="notification in notifications"
        :key="notification.id"
        class="notifications-bell__item"
        :class="{ 'notifications-bell__item--unread': !notification.read_at }"
        @click="handleRead(notification)"
      >
        <p>{{ notification.data.message }}</p>
        <span class="label-mono">{{ formatDateTime(notification.created_at) }}</span>
      </div>
    </div>
  </div>
</template>

<style scoped>
.notifications-bell {
  position: relative;
}

.notifications-bell__panel {
  position: absolute;
  right: 0;
  top: calc(100% + 0.5rem);
  width: 280px;
  max-height: 320px;
  overflow-y: auto;
  z-index: 20;
}

.notifications-bell__item {
  padding: 0.5rem;
  border-radius: var(--radius);
  cursor: pointer;
}

.notifications-bell__item--unread {
  background: var(--color-accent-soft);
}
</style>
