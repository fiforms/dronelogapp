<template>
  <div class="max-w-2xl mx-auto px-4 py-6 space-y-4">
    <div class="flex items-center justify-between">
      <h1 class="text-xl font-bold">Flight Log</h1>
      <button class="btn-secondary text-sm px-3 py-1.5" :disabled="allFlights.length === 0" @click="doExport">
        Export CSV
      </button>
    </div>

    <!-- Recent (30 days, from IndexedDB) -->
    <div>
      <h2 class="section-title text-base">Last 30 Days</h2>
      <div v-if="recentCompleted.length" class="space-y-2">
        <FlightCard v-for="f in recentCompleted" :key="f.id ?? f.client_uuid" :flight="f" />
      </div>
      <p v-else class="text-slate-500 text-sm text-center py-6">No flights in the last 30 days.</p>
    </div>

    <!-- Aborted flights -->
    <div v-if="abortedFlights.length">
      <h2 class="section-title text-base text-amber-400">Not Flown</h2>
      <p class="text-xs text-slate-500 mb-2">Logged flight attempts that were aborted before launch.</p>
      <div class="space-y-2">
        <FlightCard v-for="f in abortedFlights" :key="f.id ?? f.client_uuid" :flight="f" />
      </div>
    </div>

    <!-- Deleted flights toggle -->
    <div v-if="flights.deletedFlights.length" class="pt-1">
      <button class="text-sm text-slate-500 hover:text-slate-300 underline underline-offset-2"
        @click="showDeleted = !showDeleted">
        {{ showDeleted ? 'Hide' : 'Show' }} deleted flights ({{ flights.deletedFlights.length }})
      </button>
      <div v-if="showDeleted" class="space-y-2 mt-3">
        <div v-for="f in flights.deletedFlights" :key="f.id ?? f.client_uuid"
          class="card opacity-60 flex items-center justify-between gap-3">
          <div class="flex-1 min-w-0">
            <p class="text-sm text-slate-400 line-through truncate">
              {{ formatDate(f.started_at) }} — {{ droneName(f.drone_id) }}
            </p>
            <p class="text-xs text-slate-500">Deleted flight record</p>
          </div>
          <button class="text-xs text-blue-400 hover:text-blue-300 shrink-0"
            @click="flights.restoreFlight(f.id)">
            Restore
          </button>
        </div>
      </div>
    </div>

    <!-- Older (from API, online only) -->
    <div v-if="sync.online">
      <h2 class="section-title text-base">Older Flights</h2>
      <div v-if="flights.olderFlights.length" class="space-y-2">
        <FlightCard v-for="f in flights.olderFlights" :key="f.id" :flight="f" />
      </div>
      <p v-else-if="!flights.loadingOlder" class="text-slate-500 text-sm text-center py-4">No older flights.</p>

      <button v-if="flights.pagination.hasMore" class="btn-secondary mt-3"
        :disabled="flights.loadingOlder" @click="loadMore">
        {{ flights.loadingOlder ? 'Loading…' : 'Load more' }}
      </button>
    </div>
    <div v-else class="text-slate-500 text-sm text-center py-2">
      Go online to view flights older than 30 days.
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useFlightsStore } from '../stores/flights';
import { useFleetStore } from '../stores/fleet';
import { useSyncStore } from '../stores/sync';
import FlightCard from '../components/FlightCard.vue';
import { exportFlightsCsv } from '../utils/exportCsv';

const flights     = useFlightsStore();
const fleet       = useFleetStore();
const sync        = useSyncStore();
const showDeleted = ref(false);

const recentCompleted = computed(() =>
  flights.recentFlights
    .filter((f) => f.ended_at && f.status !== 'aborted')
    .sort((a, b) => new Date(b.started_at) - new Date(a.started_at))
);

const abortedFlights = computed(() =>
  flights.recentFlights
    .filter((f) => f.status === 'aborted')
    .sort((a, b) => new Date(b.started_at) - new Date(a.started_at))
);

const allFlights = computed(() =>
  [...recentCompleted.value, ...flights.olderFlights].sort((a, b) =>
    new Date(b.started_at) - new Date(a.started_at)
  )
);

function formatDate(iso) {
  if (!iso) return '—';
  return new Date(iso).toLocaleDateString(undefined, { month: 'short', day: 'numeric', year: 'numeric' });
}

function droneName(droneId) {
  if (!droneId) return 'Unknown drone';
  const drone = fleet.drones.find((d) => d.id === droneId || d.server_id === droneId);
  return drone?.name ?? 'Unknown drone';
}

function doExport() {
  exportFlightsCsv(allFlights.value, { drones: fleet.drones, batteries: fleet.batteries });
}

function loadMore() {
  flights.loadOlderFlights(flights.pagination.page + 1);
}

onMounted(async () => {
  await flights.loadRecent();
  if (sync.online) {
    await flights.loadOlderFlights(1);
    if (!fleet.loaded) await fleet.fetchAll();
  } else if (!fleet.loaded) {
    await fleet.loadFromIndexedDB();
  }
});
</script>
