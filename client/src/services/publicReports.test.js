import { describe, expect, it, vi } from 'vitest'
import { fetchPublicReports } from './publicReports'
import http from './http'

vi.mock('./http')

describe('fetchPublicReports', () => {
  it('requests the public endpoint with the given bounds and maps snake_case fields to camelCase', async () => {
    http.get.mockResolvedValue({
      data: {
        data: [
          {
            id: 1,
            latitude: 40.4,
            longitude: -3.7,
            radius_km: 2,
            duration_type: 'fixed',
            description: 'Test',
            address: 'Madrid',
            confirmations_count: 3,
            expires_at: '2026-01-01T00:00:00Z',
            created_at: '2025-12-31T00:00:00Z',
          },
        ],
      },
    })

    const bounds = { south_west_lat: 1, south_west_lng: 2, north_east_lat: 3, north_east_lng: 4 }
    const result = await fetchPublicReports(bounds)

    expect(http.get).toHaveBeenCalledWith('/api/v1/public/reports', { params: bounds })
    expect(result).toEqual([
      {
        id: 1,
        latitude: 40.4,
        longitude: -3.7,
        radiusKm: 2,
        durationType: 'fixed',
        description: 'Test',
        address: 'Madrid',
        confirmationsCount: 3,
        expiresAt: '2026-01-01T00:00:00Z',
        createdAt: '2025-12-31T00:00:00Z',
      },
    ])
  })
})
