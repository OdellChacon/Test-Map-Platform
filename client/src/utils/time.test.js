import { describe, expect, it } from 'vitest'
import { formatDateTime, formatRemainingTime } from './time'

describe('formatDateTime', () => {
  it('formats an ISO string into a locale date time', () => {
    const result = formatDateTime('2026-01-01T12:00:00Z')

    expect(result).toBe(new Date('2026-01-01T12:00:00Z').toLocaleString())
  })
})

describe('formatRemainingTime', () => {
  it('returns "Active until closed" for until_closed reports', () => {
    expect(formatRemainingTime('2026-01-01T00:00:00Z', 'until_closed')).toBe('Active until closed')
  })

  it('returns "Expiring soon" when the expiry is in the past', () => {
    const past = new Date(Date.now() - 60000).toISOString()

    expect(formatRemainingTime(past, 'fixed')).toBe('Expiring soon')
  })

  it('returns hours and minutes remaining for a future expiry', () => {
    const future = new Date(Date.now() + 2 * 3600000 + 5 * 60000).toISOString()

    expect(formatRemainingTime(future, 'fixed')).toBe('2h 5m remaining')
  })

  it('returns only minutes when under an hour remains', () => {
    const future = new Date(Date.now() + 30 * 60000).toISOString()

    expect(formatRemainingTime(future, 'fixed')).toBe('30m remaining')
  })
})
