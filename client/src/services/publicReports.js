import http from './http'

export function fetchPublicReports(bounds) {
  return http.get('/api/v1/public/reports', { params: bounds }).then((response) =>
    response.data.data.map((report) => ({
      id: report.id,
      latitude: report.latitude,
      longitude: report.longitude,
      radiusKm: report.radius_km,
      durationType: report.duration_type,
      description: report.description,
      address: report.address,
      confirmationsCount: report.confirmations_count,
      expiresAt: report.expires_at,
      createdAt: report.created_at,
    })),
  )
}
