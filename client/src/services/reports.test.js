import { describe, expect, it, vi } from 'vitest'
import { closeReport, confirmReport, createReport, fetchMyReports, updateReport } from './reports'
import http from './http'

vi.mock('./http')

describe('reports service', () => {
  it('fetches my reports with a page parameter', async () => {
    http.get.mockResolvedValue({ data: { data: [], meta: {} } })

    await fetchMyReports(2)

    expect(http.get).toHaveBeenCalledWith('/api/v1/reports', { params: { page: 2 } })
  })

  it('posts a new report and returns its data', async () => {
    http.post.mockResolvedValue({ data: { data: { id: 1 } } })

    const result = await createReport({ latitude: 1, longitude: 2 })

    expect(http.post).toHaveBeenCalledWith('/api/v1/reports', { latitude: 1, longitude: 2 })
    expect(result).toEqual({ id: 1 })
  })

  it('patches a report by id', async () => {
    http.patch.mockResolvedValue({ data: { data: { id: 5 } } })

    await updateReport(5, { radius_km: 3 })

    expect(http.patch).toHaveBeenCalledWith('/api/v1/reports/5', { radius_km: 3 })
  })

  it('closes a report by id', async () => {
    http.post.mockResolvedValue({ data: { data: { id: 7, status: 'closed' } } })

    await closeReport(7)

    expect(http.post).toHaveBeenCalledWith('/api/v1/reports/7/close')
  })

  it('confirms a report by id', async () => {
    http.post.mockResolvedValue({ data: { data: { confirmations_count: 1 } } })

    await confirmReport(9)

    expect(http.post).toHaveBeenCalledWith('/api/v1/reports/9/confirm')
  })
})
