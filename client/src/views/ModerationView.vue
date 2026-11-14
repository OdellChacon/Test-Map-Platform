<script setup>
import { onMounted, reactive, ref } from 'vue'
import * as moderationService from '@/services/moderation'

const reports = ref([])
const isLoading = ref(false)
const loadError = ref('')
const reasons = reactive({})
const actionErrors = reactive({})
const processingId = ref(null)

async function loadReports() {
  isLoading.value = true
  loadError.value = ''

  try {
    reports.value = await moderationService.fetchModerationQueue()
  } catch {
    loadError.value = 'Could not load reports for moderation.'
  } finally {
    isLoading.value = false
  }
}

async function handleModerate(report, action) {
  processingId.value = report.id
  actionErrors[report.id] = ''

  try {
    await moderationService.moderateReport(report.id, action, reasons[report.id] ?? null)
    await loadReports()
  } catch (error) {
    actionErrors[report.id] = error.response?.data?.message ?? 'Could not moderate this report.'
  } finally {
    processingId.value = null
  }
}

onMounted(loadReports)
</script>

<template>
  <div class="page stack-lg">
    <section class="stack">
      <h2>Moderation queue</h2>
      <p>Active reports across the whole platform. Hiding or closing is logged with your user id.</p>

      <p v-if="isLoading" class="state-message">Loading...</p>
      <p v-else-if="loadError" class="state-message">{{ loadError }}</p>
      <p v-else-if="reports.length === 0" class="state-message">No active reports right now.</p>

      <div v-else class="stack">
        <div v-for="report in reports" :key="report.id" class="card stack">
          <div style="display: flex; justify-content: space-between; align-items: center">
            <span class="label-mono">#{{ report.id }}</span>
            <span class="badge badge-muted">{{ report.confirmations_count }} confirmations</span>
          </div>

          <p>Radius: {{ report.radius_km }} km</p>
          <p v-if="report.address">{{ report.address }}</p>
          <p v-if="report.description">{{ report.description }}</p>

          <div class="field">
            <label>Reason (optional)</label>
            <input v-model="reasons[report.id]" type="text" maxlength="500" />
          </div>

          <span v-if="actionErrors[report.id]" class="field-error">{{ actionErrors[report.id] }}</span>

          <div style="display: flex; gap: 0.5rem">
            <button
              type="button"
              class="btn btn-secondary"
              :disabled="processingId === report.id"
              @click="handleModerate(report, 'hide')"
            >
              Hide
            </button>
            <button
              type="button"
              class="btn btn-secondary"
              :disabled="processingId === report.id"
              @click="handleModerate(report, 'close')"
            >
              Close
            </button>
          </div>
        </div>
      </div>
    </section>
  </div>
</template>
