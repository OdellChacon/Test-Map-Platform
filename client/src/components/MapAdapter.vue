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
  markers: { type: Array, default: () => [] },
  reportBounds: { type: Boolean, default: false },
})

const emit = defineEmits(['update:point', 'update:bounds'])

const container = ref(null)
let map = null
let centerMarker = null
let radiusCircle = null
let markersLayer = null

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

function emitBounds() {
  const bounds = map.getBounds()

  emit('update:bounds', {
    south_west_lat: bounds.getSouth(),
    south_west_lng: bounds.getWest(),
    north_east_lat: bounds.getNorth(),
    north_east_lng: bounds.getEast(),
  })
}

function buildPopupElement(marker) {
  const popup = document.createElement('div')
  popup.className = 'map-popup'

  const addLine = (text) => {
    const line = document.createElement('p')
    line.textContent = text
    popup.appendChild(line)
  }

  addLine(`Radius: ${marker.radiusKm} km`)

  if (marker.startedAtLabel) {
    addLine(marker.startedAtLabel)
  }

  if (marker.remainingLabel) {
    addLine(marker.remainingLabel)
  }

  if (marker.address) {
    addLine(marker.address)
  }

  if (marker.description) {
    addLine(marker.description)
  }

  if (typeof marker.confirmationsCount === 'number') {
    addLine(`${marker.confirmationsCount} confirmations`)
  }

  return popup
}

function renderMarkers(markers) {
  markersLayer.clearLayers()

  markers.forEach((marker) => {
    const point = L.circleMarker([marker.latitude, marker.longitude], {
      radius: 6,
      color: '#e5484d',
      weight: 2,
      fillColor: '#e5484d',
      fillOpacity: 1,
    }).addTo(markersLayer)

    const circle = L.circle([marker.latitude, marker.longitude], {
      radius: metersFromKm(marker.radiusKm),
      color: '#e5484d',
      weight: 1.5,
      fillColor: '#e5484d',
      fillOpacity: 0.12,
    }).addTo(markersLayer)

    point.bindPopup(buildPopupElement(marker))
    circle.bindPopup(buildPopupElement(marker))
  })
}

onMounted(() => {
  map = L.map(container.value).setView([props.latitude, props.longitude], props.zoom)

  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; OpenStreetMap contributors',
    maxZoom: 19,
  }).addTo(map)

  markersLayer = L.layerGroup().addTo(map)
  renderMarkers(props.markers)

  if (props.selectable) {
    renderPoint(props.latitude, props.longitude, props.radiusKm)

    map.on('click', (event) => {
      emit('update:point', { latitude: event.latlng.lat, longitude: event.latlng.lng })
    })
  }

  if (props.reportBounds) {
    map.on('moveend', emitBounds)
    emitBounds()
  }
})

onBeforeUnmount(() => {
  map?.remove()
})

watch(
  () => [props.latitude, props.longitude, props.radiusKm],
  ([lat, lng, radiusKm]) => {
    if (!map || !props.selectable) {
      return
    }

    renderPoint(lat, lng, radiusKm)
  },
)

watch(
  () => props.markers,
  (markers) => {
    if (!map) {
      return
    }

    renderMarkers(markers)
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

<style>
.map-popup {
  font-family: var(--font-sans);
  font-size: 0.85rem;
}

.map-popup p {
  margin: 0 0 0.35rem;
  color: var(--color-text);
}

.map-popup p:last-child {
  margin-bottom: 0;
}
</style>
