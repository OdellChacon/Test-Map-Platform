<script setup>
import { onMounted, onBeforeUnmount, ref, watch } from 'vue'
import L from 'leaflet'
import 'leaflet/dist/leaflet.css'

const props = defineProps({
  latitude: { type: Number, default: 40.4168 },
  longitude: { type: Number, default: -3.7038 },
  radiusKm: { type: Number, default: 1 },
  selectable: { type: Boolean, default: false },
  zoom: { type: Number, default: 12 },
})

const emit = defineEmits(['update:point'])

const container = ref(null)
let map = null
let centerMarker = null
let radiusCircle = null

function metersFromKm(km) {
  return km * 1000
}

function renderPoint(lat, lng, radiusKm) {
  if (centerMarker) {
    centerMarker.setLatLng([lat, lng])
  } else {
    centerMarker = L.circleMarker([lat, lng], {
      radius: 6,
      color: '#e5484d',
      weight: 2,
      fillColor: '#e5484d',
      fillOpacity: 1,
    }).addTo(map)
  }

  if (radiusCircle) {
    radiusCircle.setLatLng([lat, lng])
    radiusCircle.setRadius(metersFromKm(radiusKm))
  } else {
    radiusCircle = L.circle([lat, lng], {
      radius: metersFromKm(radiusKm),
      color: '#e5484d',
      weight: 1.5,
      fillColor: '#e5484d',
      fillOpacity: 0.15,
    }).addTo(map)
  }
}

onMounted(() => {
  map = L.map(container.value).setView([props.latitude, props.longitude], props.zoom)

  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; OpenStreetMap contributors',
    maxZoom: 19,
  }).addTo(map)

  renderPoint(props.latitude, props.longitude, props.radiusKm)

  if (props.selectable) {
    map.on('click', (event) => {
      emit('update:point', { latitude: event.latlng.lat, longitude: event.latlng.lng })
    })
  }
})

onBeforeUnmount(() => {
  map?.remove()
})

watch(
  () => [props.latitude, props.longitude, props.radiusKm],
  ([lat, lng, radiusKm]) => {
    if (!map) {
      return
    }

    renderPoint(lat, lng, radiusKm)
  },
)
</script>

<template>
  <div ref="container" class="map-adapter"></div>
</template>

<style scoped>
.map-adapter {
  width: 100%;
  height: 320px;
  border-radius: var(--radius);
}
</style>
