import http from './http'

export function register(payload) {
  return http.post('/api/v1/register', payload).then((response) => response.data.data)
}

export function login(payload) {
  return http.post('/api/v1/login', payload).then((response) => response.data.data)
}

export function logout() {
  return http.post('/api/v1/logout')
}

export function fetchCurrentUser() {
  return http.get('/api/v1/me').then((response) => response.data.data)
}
