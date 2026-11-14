<script setup>
import { onMounted, reactive, ref } from 'vue'
import MapAdapter from '@/components/MapAdapter.vue'
import * as reportsService from '@/services/reports'
import { useOutageChannel } from '@/composables/useOutageChannel'

const reports = ref([])
const isLoadingList = ref(false)
const listError = ref('')

const createForm = reactive({
  latitude: 40.4168,
  longitude: -3.7038,
  radius_km: 2,
  duration_type: 'fixed',
  duration_hours: 4,
  description: '',
})
const createErrors = ref({})
const isCreating = ref(false)

const editingReportId = ref(null)
const editForm = reactive({ radius_km: 0, duration_hours: null, description: '' })
const editErrors = ref({})
const isSavingEdit = ref(false)

async function loadReports() {
  isLoadingList.value = true
  listError.value = ''

  try {
    const response = await reportsService.fetchMyReports()
    reports.value = response.data
  } catch {
    listError.value = 'Could not load your reports. Try again in a moment.'
  } finally {
    isLoadingList.value = false
  }
}

function handlePointSelected({ latitude, longitude }) {
  createForm.latitude = latitude
  createForm.longitude = longitude
}

async function handleCreate() {
  createErrors.value = {}
  isCreating.value = true

  const payload = {
    latitude: createForm.latitude,
    longitude: createForm.longitude,
    radius_km: createForm.radius_km,
    duration_type: createForm.duration_type,
    description: createForm.description,
  }

  if (createForm.duration_type === 'fixed') {
    payload.duration_hours = createForm.duration_hours
  }

  try {
    await reportsService.createReport(payload)
    createForm.description = ''
    await loadReports()
  } catch (error) {
    createErrors.value = error.response?.data?.errors ?? {}
  } finally {
    isCreating.value = false
  }
}

function startEdit(report) {
  editingReportId.value = report.id
  editForm.radius_km = report.radius_km
  editForm.duration_hours = report.duration_hours
  editForm.description = report.description ?? ''
  editErrors.value = {}
}

function cancelEdit() {
  editingReportId.value = null
}

async function saveEdit(report) {
  isSavingEdit.value = true
  editErrors.value = {}

  const payload = {
    radius_km: editForm.radius_km,
    description: editForm.description,
  }

  if (report.duration_type === 'fixed') {
    payload.duration_hours = editForm.duration_hours
  }

  try {
    await reportsService.updateReport(report.id, payload)
    editingReportId.value = null
    await loadReports()
  } catch (error) {
    editErrors.value = error.response?.data?.errors ?? {}
  } finally {
    isSavingEdit.value = false
  }
}

async function handleClose(report) {
  await reportsService.closeReport(report.id)
  await loadReports()
}

function handleReportChanged({ action, id, report }) {
  if (action === 'removed') {
    return
  }

  reports.value = reports.value.map((item) =>
    item.id === id
      ? { ...item, address: report.address, confirmations_count: report.confirmations_count }
      : item,
  )
}

useOutageChannel(handleReportChanged)

onMounted(loadReports)
</script>

<template>
  <div class="page stack-lg">
    <section class="window-panel">
      <div class="window-panel__header">
        <span class="window-panel__dot window-panel__dot--accent"></span>
        <span class="window-panel__dot"></span>
        <span class="window-panel__dot"></span>
        <span class="window-panel__caption">new-report.map</span>
      </div>
      <div class="card">
        <h2>Report an outage</h2>
        <p>Click the map to set the location, then set the radius and duration.</p>

        <form class="stack" style="margin-top: 1rem" @submit.prevent="handleCreate">
          <MapAdapter
            :latitude="createForm.latitude"
            :longitude="createForm.longitude"
            :radius-km="createForm.radius_km"
            selectable
            @update:point="handlePointSelected"
          />

          <div class="field">
            <label for="radius_km">Radius: {{ createForm.radius_km }} km</label>
            <input
              id="radius_km"
              v-model.number="createForm.radius_km"
              type="range"
              min="0.5"
              max="25"
              step="0.5"
            />
            <span v-if="createErrors.radius_km" class="field-error">{{
              createErrors.radius_km[0]
            }}</span>
          </div>

          <div class="field">
            <label for="duration_type">Duration</label>
            <select id="duration_type" v-model="createForm.duration_type">
              <option value="fixed">Fixed number of hours</option>
              <option value="until_closed">Until I close it</option>
            </select>
          </div>

          <div v-if="createForm.duration_type === 'fixed'" class="field">
            <label for="duration_hours">Hours (1-72)</label>
            <input
              id="duration_hours"
              v-model.number="createForm.duration_hours"
              type="number"
              min="1"
              max="72"
            />
            <span v-if="createErrors.duration_hours" class="field-error">{{
              createErrors.duration_hours[0]
            }}</span>
          </div>

          <div class="field">
            <label for="description">Description (optional)</label>
            <textarea
              id="description"
              v-model="createForm.description"
              maxlength="500"
              rows="2"
            ></textarea>
          </div>

          <span v-if="createErrors.reports" class="field-error">{{ createErrors.reports[0] }}</span>

          <button type="submit" class="btn btn-primary" :disabled="isCreating">
            {{ isCreating ? 'Reporting...' : 'Report outage' }}
          </button>
        </form>
      </div>
    </section>

    <section class="stack">
      <h2>My reports</h2>

      <p v-if="isLoadingList" class="state-message">Loading your reports...</p>
      <p v-else-if="listError" class="state-message">{{ listError }}</p>
      <p v-else-if="reports.length === 0" class="state-message">
        You have not reported any outages yet.
      </p>

      <div v-else class="stack">
        <div v-for="report in reports" :key="report.id" class="card stack">
          <div style="display: flex; justify-content: space-between; align-items: center">
            <span class="badge" :class="{ 'badge-muted': report.status !== 'active' }">{{
              report.status
            }}</span>
            <div style="display: flex; gap: 0.5rem; align-items: center">
              <span class="badge badge-muted">{{ report.confirmations_count }} confirmations</span>
              <span class="label-mono">#{{ report.id }}</span>
            </div>
          </div>

          <p v-if="report.address">{{ report.address }}</p>

          <template v-if="editingReportId === report.id">
            <div class="field">
              <label>Radius: {{ editForm.radius_km }} km</label>
              <input v-model.number="editForm.radius_km" type="range" min="0.5" max="25" step="0.5" />
            </div>
            <div v-if="report.duration_type === 'fixed'" class="field">
              <label>Hours (1-72)</label>
              <input v-model.number="editForm.duration_hours" type="number" min="1" max="72" />
            </div>
            <div class="field">
              <label>Description</label>
              <textarea v-model="editForm.description" maxlength="500" rows="2"></textarea>
            </div>
            <span v-if="Object.keys(editErrors).length" class="field-error">
              {{ Object.values(editErrors)[0][0] }}
            </span>
            <div style="display: flex; gap: 0.5rem">
              <button
                type="button"
                class="btn btn-primary"
                :disabled="isSavingEdit"
                @click="saveEdit(report)"
              >
                Save
              </button>
              <button type="button" class="btn btn-secondary" @click="cancelEdit">Cancel</button>
            </div>
          </template>

          <template v-else>
            <p>Radius: {{ report.radius_km }} km</p>
            <p v-if="report.duration_type === 'fixed'">
              Expires: {{ new Date(report.expires_at).toLocaleString() }}
            </p>
            <p v-else>Until closed (expires {{ new Date(report.expires_at).toLocaleString() }})</p>
            <p v-if="report.description">{{ report.description }}</p>

            <div v-if="report.status === 'active'" style="display: flex; gap: 0.5rem">
              <button type="button" class="btn btn-secondary" @click="startEdit(report)">
                Edit
              </button>
              <button type="button" class="btn btn-secondary" @click="handleClose(report)">
                Power is back
              </button>
            </div>
          </template>
        </div>
      </div>
    </section>
  </div>
</template>
