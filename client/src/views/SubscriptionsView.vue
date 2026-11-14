<script setup>
import { onMounted, reactive, ref } from 'vue'
import MapAdapter from '@/components/MapAdapter.vue'
import * as subscriptionsService from '@/services/subscriptions'

const subscriptions = ref([])
const isLoading = ref(false)
const loadError = ref('')
const createError = ref('')
const isCreating = ref(false)

const form = reactive({
  latitude: 40.4168,
  longitude: -3.7038,
  radius_km: 10,
})

async function loadSubscriptions() {
  isLoading.value = true
  loadError.value = ''

  try {
    subscriptions.value = await subscriptionsService.fetchSubscriptions()
  } catch {
    loadError.value = 'Could not load your subscriptions. Try again in a moment.'
  } finally {
    isLoading.value = false
  }
}

function handlePointSelected({ latitude, longitude }) {
  form.latitude = latitude
  form.longitude = longitude
}

async function handleCreate() {
  createError.value = ''
  isCreating.value = true

  try {
    await subscriptionsService.createSubscription(form)
    await loadSubscriptions()
  } catch (error) {
    createError.value = error.response?.data?.message ?? 'Could not create the subscription.'
  } finally {
    isCreating.value = false
  }
}

async function handleDelete(subscription) {
  await subscriptionsService.deleteSubscription(subscription.id)
  await loadSubscriptions()
}

onMounted(loadSubscriptions)
</script>

<template>
  <div class="page stack-lg">
    <section class="window-panel">
      <div class="window-panel__header">
        <span class="window-panel__dot window-panel__dot--accent"></span>
        <span class="window-panel__dot"></span>
        <span class="window-panel__dot"></span>
        <span class="window-panel__caption">subscribe.map</span>
      </div>
      <div class="card stack">
        <h2>Follow an area</h2>
        <p>Click the map to set the center of the area, then choose how wide it should be.</p>

        <MapAdapter
          :latitude="form.latitude"
          :longitude="form.longitude"
          :radius-km="form.radius_km"
          selectable
          @update:point="handlePointSelected"
        />

        <div class="field">
          <label>Radius: {{ form.radius_km }} km</label>
          <input v-model.number="form.radius_km" type="range" min="0.5" max="25" step="0.5" />
        </div>

        <span v-if="createError" class="field-error">{{ createError }}</span>

        <button type="button" class="btn btn-primary" :disabled="isCreating" @click="handleCreate">
          {{ isCreating ? 'Saving...' : 'Notify me about this area' }}
        </button>
      </div>
    </section>

    <section class="stack">
      <h2>My subscriptions</h2>

      <p v-if="isLoading" class="state-message">Loading your subscriptions...</p>
      <p v-else-if="loadError" class="state-message">{{ loadError }}</p>
      <p v-else-if="subscriptions.length === 0" class="state-message">
        You are not following any area yet.
      </p>

      <div v-else class="stack">
        <div v-for="subscription in subscriptions" :key="subscription.id" class="card stack">
          <p>Radius: {{ subscription.radius_km }} km</p>
          <p class="label-mono">{{ subscription.latitude }}, {{ subscription.longitude }}</p>
          <button type="button" class="btn btn-secondary" @click="handleDelete(subscription)">
            Stop following
          </button>
        </div>
      </div>
    </section>
  </div>
</template>
