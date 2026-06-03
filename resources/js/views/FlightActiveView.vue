<template>
  <div class="max-w-2xl mx-auto px-4 py-6 space-y-6">
    <div class="card text-center">
      <p class="text-emerald-400 text-sm font-semibold mb-2">✈️ Flight in Progress</p>

      <div class="text-5xl font-mono font-bold text-white mb-1">{{ elapsed }}</div>
      <p class="text-slate-400 text-sm">Started {{ startTime }}</p>
    </div>

    <div v-if="flight" class="card space-y-2">
      <div class="flex justify-between text-sm">
        <span class="text-slate-400">Drone</span>
        <span class="font-medium">{{ droneName }}</span>
      </div>
      <div class="flex justify-between text-sm">
        <span class="text-slate-400">Battery</span>
        <span class="font-medium">{{ batteryName }}</span>
      </div>
      <div v-if="flight.accessories?.length" class="flex justify-between text-sm">
        <span class="text-slate-400">Accessories</span>
        <span class="font-medium">{{ accessoryNames }}</span>
      </div>
      <div v-if="flight.location_description" class="flex justify-between text-sm">
        <span class="text-slate-400">Location</span>
        <span class="font-medium text-right max-w-[60%]">{{ flight.location_description }}</span>
      </div>
    </div>

    <RouterLink :to="`/flights/${flightId}/end`" class="btn-danger block text-center">
      🛬 End Flight
    </RouterLink>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { useRoute } from 'vue-router';
import { useFlightsStore } from '../stores/flights';
import { useFleetStore } from '../stores/fleet';

const route = useRoute();
const flightId = computed(() => route.params.id);
const flights = useFlightsStore();
const fleet = useFleetStore();

const flight = computed(() => flights.recentFlights.find((f) => String(f.id) === String(flightId.value)));
const startTime = computed(() => flight.value
  ? new Date(flight.value.started_at).toLocaleTimeString(undefined, { hour: '2-digit', minute: '2-digit' })
  : '');

const elapsed = ref('00:00');
let timer;

function tick() {
  if (!flight.value?.started_at) return;
  const ms = Date.now() - new Date(flight.value.started_at).getTime();
  const s = Math.floor(ms / 1000);
  const h = Math.floor(s / 3600);
  const m = Math.floor((s % 3600) / 60);
  const sec = s % 60;
  elapsed.value = h > 0
    ? `${String(h).padStart(2, '0')}:${String(m).padStart(2, '0')}:${String(sec).padStart(2, '0')}`
    : `${String(m).padStart(2, '0')}:${String(sec).padStart(2, '0')}`;
}

const droneName = computed(() => {
  const d = fleet.drones.find((x) => x.id === flight.value?.drone_id);
  return d?.name ?? '—';
});

const batteryName = computed(() => {
  const b = fleet.batteries.find((x) => x.id === flight.value?.battery_id);
  return b?.name ?? '—';
});

const accessoryNames = computed(() => {
  if (!flight.value?.accessories?.length) return '';
  return flight.value.accessories
    .map((id) => fleet.accessories.find((a) => a.id === id)?.name ?? id)
    .join(', ');
});

onMounted(async () => {
  await flights.loadRecent();
  tick();
  timer = setInterval(tick, 1000);
});

onUnmounted(() => clearInterval(timer));
</script>
