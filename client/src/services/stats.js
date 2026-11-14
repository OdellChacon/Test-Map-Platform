import http from './http'

export function fetchStats() {
  return http.get('/api/v1/public/stats').then((response) => response.data.data)
}
