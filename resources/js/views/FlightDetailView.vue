<template>
  <div class="max-w-2xl mx-auto px-4 py-6 space-y-5">
    <div class="flex items-center gap-3">
      <RouterLink to="/flights" class="text-slate-400 hover:text-white">←</RouterLink>
      <h1 class="text-xl font-bold">Flight Detail</h1>
      <span v-if="!flight?.synced && flight"
        class="ml-auto text-xs text-amber-400 bg-amber-900/40 px-2 py-0.5 rounded-full">
        ⏳ Pending sync
      </span>
    </div>

    <div v-if="loading" class="text-slate-400 text-center py-12">Loading…</div>

    <template v-else-if="flight">
      <!-- Header info -->
      <div class="card space-y-2 text-sm">
        <div class="flex justify-between">
          <span class="text-slate-400">Date</span>
          <span>{{ formatDate(flight.started_at) }}</span>
        </div>
        <div class="flex justify-between">
          <span class="text-slate-400">Duration</span>
          <span>{{ duration }}</span>
        </div>
        <div class="flex justify-between">
          <span class="text-slate-400">Drone</span>
          <span>{{ droneName }}</span>
        </div>
        <div class="flex justify-between">
          <span class="text-slate-400">Battery</span>
          <span>{{ batteryName }}</span>
        </div>
        <div v-if="flight.battery_pct_start != null || flight.battery_pct_end != null" class="flex justify-between">
          <span class="text-slate-400">Battery %</span>
          <span>
            <template v-if="flight.battery_pct_start != null">{{ flight.battery_pct_start }}% start</template>
            <template v-if="flight.battery_pct_start != null && flight.battery_pct_end != null"> → </template>
            <template v-if="flight.battery_pct_end != null">{{ flight.battery_pct_end }}% end</template>
          </span>
        </div>
        <div v-if="accessoryNames" class="flex justify-between">
          <span class="text-slate-400">Accessories</span>
          <span>{{ accessoryNames }}</span>
        </div>
        <div v-if="flight.lat && flight.lng" class="flex justify-between">
          <span class="text-slate-400">GPS</span>
          <span class="font-mono text-xs">{{ Number(flight.lat).toFixed(5) }}, {{ Number(flight.lng).toFixed(5) }}</span>
        </div>
      </div>

      <!-- Purpose & LAANC -->
      <div class="card space-y-2 text-sm">
        <div class="flex justify-between">
          <span class="text-slate-400">Purpose</span>
          <span class="capitalize">{{ flight.purpose }}</span>
        </div>
        <div v-if="flight.purpose_notes">
          <p class="text-slate-400 mb-1">Purpose notes</p>
          <p class="text-slate-200">{{ flight.purpose_notes }}</p>
        </div>
        <div class="flex justify-between">
          <span class="text-slate-400">LAANC</span>
          <span>{{ laancLabel }}</span>
        </div>
        <div v-if="flight.laanc_authorization_number" class="flex justify-between">
          <span class="text-slate-400">Auth #</span>
          <span class="font-mono text-xs">{{ flight.laanc_authorization_number }}</span>
        </div>
      </div>

      <!-- Location & plan -->
      <div v-if="flight.location_description || flight.flight_plan" class="card text-sm space-y-3">
        <div v-if="flight.location_description">
          <p class="label">Launch Location</p>
          <p class="text-slate-200">{{ flight.location_description }}</p>
        </div>
        <div v-if="flight.flight_plan">
          <p class="label">Flight Plan</p>
          <p class="text-slate-200 whitespace-pre-wrap">{{ flight.flight_plan }}</p>
        </div>
      </div>

      <!-- Checklist -->
      <div v-if="checklist.length" class="space-y-2">
        <h2 class="section-title">Pre-Flight Checklist</h2>
        <div v-for="item in checklist" :key="item.checklist_item_id" class="card text-sm">
          <div class="flex items-start gap-2">
            <span :class="item.checked ? 'text-emerald-400' : 'text-red-400'" class="mt-0.5">
              {{ item.checked ? '✅' : '❌' }}
            </span>
            <div>
              <p :class="item.checked ? 'text-slate-200' : 'text-slate-400'">{{ item.label }}</p>
              <p v-if="item.comment" class="text-slate-500 text-xs mt-0.5 italic">{{ item.comment }}</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Post-flight notes -->
      <div v-if="flight.post_flight_notes" class="card text-sm">
        <p class="label">Post-Flight Notes</p>
        <p class="text-slate-200 whitespace-pre-wrap">{{ flight.post_flight_notes }}</p>
      </div>
    </template>

    <div v-else class="text-slate-400 text-center py-12">Flight not found.</div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import { useFlightsStore } from '../stores/flights';
import { useFleetStore } from '../stores/fleet';

const route = useRoute();
const flights = useFlightsStore();
const fleet = useFleetStore();

const loading = ref(true);
const flight = ref(null);

const flightId = computed(() => route.params.id);

const checklist = computed(() => flight.value?.checklist ?? []);

const droneName = computed(() => {
  const id = flight.value?.drone_id ?? flight.value?.drone?.id;
  return fleet.drones.find((d) => d.id === id)?.name ?? flight.value?.drone?.name ?? '—';
});

const batteryName = computed(() => {
  const id = flight.value?.battery_id ?? flight.value?.battery?.id;
  return fleet.batteries.find((b) => b.id === id)?.name ?? flight.value?.battery?.name ?? '—';
});

const accessoryNames = computed(() => {
  const ids = flight.value?.accessories ?? [];
  if (!ids.length) return '';
  return ids.map((id) => fleet.accessories.find((a) => a.id === id || a.server_id === id)?.name ?? id).join(', ');
});

const duration = computed(() => {
  if (!flight.value?.ended_at) return 'In progress';
  const m = flight.value.duration_minutes
    ?? Math.floor((new Date(flight.value.ended_at) - new Date(flight.value.started_at)) / 60000);
  return m < 60 ? `${m} min` : `${Math.floor(m / 60)}h ${m % 60}m`;
});

const laancLabel = computed(() => ({
  received:   'Authorization received',
  not_needed: 'Not needed',
  na:         'Not applicable',
}[flight.value?.laanc_status] ?? '—'));

function formatDate(iso) {
  return new Date(iso).toLocaleString(undefined, {
    weekday: 'short', month: 'short', day: 'numeric',
    year: 'numeric', hour: '2-digit', minute: '2-digit',
  });
}

onMounted(async () => {
  await flights.loadRecent();
  flight.value = await flights.getFlightById(flightId.value);
  loading.value = false;
});
</script>
