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
      <div v-if="recentFlights.length" class="space-y-2">
        <FlightCard v-for="f in recentFlights" :key="f.id ?? f.client_uuid" :flight="f" />
      </div>
      <p v-else class="text-slate-500 text-sm text-center py-6">No flights in the last 30 days.</p>
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
import { computed, onMounted } from 'vue';
import { useFlightsStore } from '../stores/flights';
import { useFleetStore } from '../stores/fleet';
import { useSyncStore } from '../stores/sync';
import FlightCard from '../components/FlightCard.vue';
import { exportFlightsCsv } from '../utils/exportCsv';

const flights = useFlightsStore();
const fleet = useFleetStore();
const sync = useSyncStore();

const recentFlights = computed(() =>
  flights.recentFlights.filter((f) => f.ended_at).sort((a, b) =>
    new Date(b.started_at) - new Date(a.started_at)
  )
);

const allFlights = computed(() =>
  [...recentFlights.value, ...flights.olderFlights].sort((a, b) =>
    new Date(b.started_at) - new Date(a.started_at)
  )
);

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
