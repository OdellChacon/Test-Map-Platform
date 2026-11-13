<script setup>
import { onBeforeUnmount, onMounted, ref } from 'vue'
import MapAdapter from '@/components/MapAdapter.vue'
import * as publicReportsService from '@/services/publicReports'

const POLL_INTERVAL_MS = 30000

const markers = ref([])
const isLoading = ref(false)
const loadError = ref('')

let currentBounds = null
let pollTimer = null

async function loadReports() {
  if (!currentBounds) {
    return
  }

  isLoading.value = true
  loadError.value = ''

  try {
    markers.value = await publicReportsService.fetchPublicReports(currentBounds)
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

onMounted(() => {
  pollTimer = setInterval(loadReports, POLL_INTERVAL_MS)
})

onBeforeUnmount(() => {
  clearInterval(pollTimer)
})
</script>

<template>
  <div class="page stack-lg">
    <section class="window-panel">
      <div class="window-panel__header">
        <span class="window-panel__dot window-panel__dot--accent"></span>
        <span class="window-panel__dot"></span>
        <span class="window-panel__dot"></span>
        <span class="window-panel__caption">live-outages.map</span>
      </div>
      <div class="card stack">
        <h2>Live outage map</h2>
        <p>Active outages reported by the community, refreshed automatically.</p>

        <MapAdapter :markers="markers" report-bounds @update:bounds="handleBoundsChange" />

        <p v-if="loadError" class="state-message">{{ loadError }}</p>
        <p v-else-if="!isLoading && markers.length === 0" class="state-message">
          No active outages in this area right now.
        </p>
      </div>
    </section>
  </div>
</template>
