import http from './http'

export function fetchMyReports(page = 1) {
  return http.get('/api/v1/reports', { params: { page } }).then((response) => response.data)
}

export function createReport(payload) {
  return http.post('/api/v1/reports', payload).then((response) => response.data.data)
}

export function updateReport(reportId, payload) {
  return http.patch(`/api/v1/reports/${reportId}`, payload).then((response) => response.data.data)
}

export function closeReport(reportId) {
  return http.post(`/api/v1/reports/${reportId}/close`).then((response) => response.data.data)
}
