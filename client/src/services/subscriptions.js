import http from './http'

export function fetchSubscriptions() {
  return http.get('/api/v1/subscriptions').then((response) => response.data.data)
}

export function createSubscription(payload) {
  return http.post('/api/v1/subscriptions', payload).then((response) => response.data.data)
}

export function deleteSubscription(subscriptionId) {
  return http.delete(`/api/v1/subscriptions/${subscriptionId}`)
}
