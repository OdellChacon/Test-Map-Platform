export function formatDateTime(value) {
  return new Date(value).toLocaleString()
}

export function formatRemainingTime(expiresAt, durationType) {
  if (durationType === 'until_closed') {
    return 'Active until closed'
  }

  const remainingMs = new Date(expiresAt).getTime() - Date.now()

  if (remainingMs <= 0) {
    return 'Expiring soon'
  }

  const hours = Math.floor(remainingMs / 3600000)
  const minutes = Math.floor((remainingMs % 3600000) / 60000)

  return hours >= 1 ? `${hours}h ${minutes}m remaining` : `${minutes}m remaining`
}
