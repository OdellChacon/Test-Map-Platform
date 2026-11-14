<script setup>
import { computed, onMounted, ref } from 'vue'
import * as statsService from '@/services/stats'

const stats = ref(null)
const isLoading = ref(false)
const loadError = ref('')

const maxCount = computed(() => {
  if (!stats.value) {
    return 1
  }

  return Math.max(1, ...stats.value.reports_per_day.map((day) => day.count))
})

async function loadStats() {
  isLoading.value = true
  loadError.value = ''

  try {
    stats.value = await statsService.fetchStats()
  } catch {
    loadError.value = 'Could not load statistics. Try again in a moment.'
  } finally {
    isLoading.value = false
  }
}

onMounted(loadStats)
</script>

<template>
  <div class="page stack-lg">
    <section class="stack">
      <h2>Outage history</h2>
      <p>Aggregate statistics across every report, no personal data.</p>

      <p v-if="isLoading" class="state-message">Loading statistics...</p>
      <p v-else-if="loadError" class="state-message">{{ loadError }}</p>

      <template v-else-if="stats">
        <div class="stack" style="display: flex; flex-direction: row; gap: 1rem">
          <div class="card" style="flex: 1">
            <p class="label-mono">Total reports</p>
            <h2>{{ stats.total_reports }}</h2>
          </div>
          <div class="card" style="flex: 1">
            <p class="label-mono">Active now</p>
            <h2>{{ stats.active_reports }}</h2>
          </div>
          <div class="card" style="flex: 1">
            <p class="label-mono">Average duration</p>
            <h2>
              {{ stats.average_duration_minutes !== null ? Math.round(stats.average_duration_minutes / 60) + 'h' : '—' }}
            </h2>
          </div>
        </div>

        <div class="card stack">
          <h3>Reports per day (last 14 days)</h3>
          <div v-for="day in stats.reports_per_day" :key="day.date" style="display: flex; align-items: center; gap: 0.75rem">
            <span class="label-mono" style="width: 90px">{{ day.date }}</span>
            <div style="flex: 1; background: var(--color-surface); border-radius: var(--radius)">
              <div
                :style="{
                  width: `${(day.count / maxCount) * 100}%`,
                  background: 'var(--color-accent)',
                  borderRadius: 'var(--radius)',
                  height: '1.25rem',
                }"
              ></div>
            </div>
            <span class="label-mono">{{ day.count }}</span>
          </div>
        </div>
      </template>
    </section>
  </div>
</template>
