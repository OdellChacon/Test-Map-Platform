import http from './http'

export function fetchModerationQueue() {
  return http.get('/api/v1/moderation/reports').then((response) => response.data.data)
}

export function moderateReport(reportId, action, reason) {
  return http
    .post(`/api/v1/moderation/reports/${reportId}`, { action, reason })
    .then((response) => response.data.data)
}
