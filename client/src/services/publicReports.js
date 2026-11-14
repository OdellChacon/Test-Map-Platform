import http from './http'

export function fetchPublicReports(bounds) {
  return http.get('/api/v1/public/reports', { params: bounds }).then((response) =>
    response.data.data.map((report) => ({
      id: report.id,
      latitude: report.latitude,
      longitude: report.longitude,
      radiusKm: report.radius_km,
      address: report.address,
      confirmationsCount: report.confirmations_count,
    })),
  )
}
