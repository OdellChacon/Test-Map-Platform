<script setup>
import { onBeforeUnmount, onMounted, ref } from 'vue'
import MapAdapter from '@/components/MapAdapter.vue'
import * as publicReportsService from '@/services/publicReports'
import * as reportsService from '@/services/reports'
import { useAuthStore } from '@/stores/auth'
import { useOutageChannel } from '@/composables/useOutageChannel'
import { formatDateTime, formatRemainingTime } from '@/utils/time'

defineProps({
  showHero: { type: Boolean, default: true },
})

const FALLBACK_POLL_INTERVAL_MS = 120000

const authStore = useAuthStore()
const markers = ref([])
const isLoading = ref(false)
const loadError = ref('')
const confirmingId = ref(null)
const confirmedIds = ref(new Set())
const confirmErrors = ref({})

let currentBounds = null
let pollTimer = null

function withDisplayLabels(report) {
  return {
    ...report,
    startedAtLabel: `Started ${formatDateTime(report.createdAt)}`,
    remainingLabel: formatRemainingTime(report.expiresAt, report.durationType),
  }
}

function isWithinCurrentBounds(latitude, longitude) {
  if (!currentBounds) {
    return false
  }

  return (
    latitude >= currentBounds.south_west_lat &&
    latitude <= currentBounds.north_east_lat &&
    longitude >= currentBounds.south_west_lng &&
    longitude <= currentBounds.north_east_lng
  )
}

async function loadReports() {
  if (!currentBounds) {
    return
  }

  isLoading.value = true
  loadError.value = ''

  try {
    const reports = await publicReportsService.fetchPublicReports(currentBounds)
    markers.value = reports.map(withDisplayLabels)
  } catch {
    loadError.value = 'Could not load live outage data. Try again in a moment.'
  } finally {
    isLoading.value = false
  }
}

function handleBoundsChange(bounds) {
  currentBounds = bounds
  loadReports()
}

function handleReportChanged({ action, id, report }) {
  if (action === 'removed') {
    markers.value = markers.value.filter((marker) => marker.id !== id)
    return
  }

  const exists = markers.value.some((marker) => marker.id === id)

  if (!exists && !isWithinCurrentBounds(report.latitude, report.longitude)) {
    return
  }

  const updatedMarker = withDisplayLabels({
    id,
    latitude: report.latitude,
    longitude: report.longitude,
    radiusKm: report.radius_km,
    durationType: report.duration_type,
    description: report.description,
    address: report.address,
    confirmationsCount: report.confirmations_count,
    expiresAt: report.expires_at,
    createdAt: report.created_at,
  })

  markers.value = exists
    ? markers.value.map((marker) => (marker.id === id ? updatedMarker : marker))
    : [...markers.value, updatedMarker]
}

async function handleConfirm(marker) {
  confirmingId.value = marker.id
  confirmErrors.value = { ...confirmErrors.value, [marker.id]: '' }

  try {
    const { confirmations_count: confirmationsCount } = await reportsService.confirmReport(marker.id)
    confirmedIds.value = new Set(confirmedIds.value).add(marker.id)
    markers.value = markers.value.map((item) =>
      item.id === marker.id ? { ...item, confirmationsCount } : item,
    )
  } catch (error) {
    if (error.response?.status === 422) {
      confirmedIds.value = new Set(confirmedIds.value).add(marker.id)
    }

    confirmErrors.value = {
      ...confirmErrors.value,
      [marker.id]: error.response?.data?.message ?? 'Could not confirm this report.',
    }
  } finally {
    confirmingId.value = null
  }
}

useOutageChannel(handleReportChanged)

onMounted(() => {
  pollTimer = setInterval(loadReports, FALLBACK_POLL_INTERVAL_MS)
})

onBeforeUnmount(() => {
  clearInterval(pollTimer)
})
</script>

<template>
  <div class="page stack-lg">
    <template v-if="showHero">
      <section class="hero">
        <h1 class="heading-two-tone">
          Know your outage.
          <span class="line-muted">See it the moment it's reported.</span>
        </h1>
        <p>A live map of power outages reported by your community, updated in real time.</p>
      </section>

      <section class="stack">
        <div class="how-it-works">
          <div class="step">
            <span class="step-number">01</span>
            <h3>Report an outage</h3>
            <p>Click a point on the map, set the affected radius and how long it will likely last.</p>
          </div>
          <div class="step">
            <span class="step-number">02</span>
            <h3>See it live</h3>
            <p>Anyone, logged in or not, sees active outages appear, update and disappear in real time.</p>
          </div>
          <div class="step">
            <span class="step-number">03</span>
            <h3>Confirm or close</h3>
            <p>Neighbors confirm an outage is still ongoing; the reporter closes it once power is back.</p>
          </div>
        </div>
      </section>
    </template>

    <section class="window-panel">
      <div class="window-panel__header">
        <span class="window-panel__dot window-panel__dot--accent"></span>
        <span class="window-panel__dot"></span>
        <span class="window-panel__dot"></span>
        <span class="window-panel__caption">live-outages.map</span>
      </div>
      <div class="card stack">
        <h2>Live outage map</h2>
        <p>Click an outage on the map for its details.</p>

        <MapAdapter :markers="markers" report-bounds @update:bounds="handleBoundsChange" />

        <p v-if="loadError" class="state-message">{{ loadError }}</p>
        <p v-else-if="!isLoading && markers.length === 0" class="state-message">
          No active outages in this area right now.
        </p>
      </div>
    </section>

    <section v-if="markers.length" class="stack">
      <h3>Reports in view</h3>

      <div v-for="marker in markers" :key="marker.id" class="card stack">
        <div style="display: flex; justify-content: space-between; align-items: center">
          <span class="label-mono">#{{ marker.id }}</span>
          <span class="badge">{{ marker.confirmationsCount }} confirmations</span>
        </div>

        <p>Radius: {{ marker.radiusKm }} km</p>
        <p>{{ marker.startedAtLabel }}</p>
        <p>{{ marker.remainingLabel }}</p>
        <p v-if="marker.address">{{ marker.address }}</p>
        <p v-if="marker.description">{{ marker.description }}</p>

        <template v-if="authStore.isAuthenticated">
          <button
            v-if="!confirmedIds.has(marker.id)"
            type="button"
            class="btn btn-secondary"
            :disabled="confirmingId === marker.id"
            @click="handleConfirm(marker)"
          >
            {{ confirmingId === marker.id ? 'Confirming...' : 'Confirm this outage' }}
          </button>
          <span v-else class="badge">Confirmed</span>
        </template>

        <span v-if="confirmErrors[marker.id]" class="field-error">{{ confirmErrors[marker.id] }}</span>
      </div>
    </section>
  </div>
</template>
