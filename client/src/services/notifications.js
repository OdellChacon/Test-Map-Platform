import http from './http'

export function fetchNotifications() {
  return http.get('/api/v1/notifications').then((response) => response.data)
}

export function markNotificationAsRead(notificationId) {
  return http.post(`/api/v1/notifications/${notificationId}/read`)
}
