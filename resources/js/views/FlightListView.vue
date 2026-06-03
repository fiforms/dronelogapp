<template>
  <div class="max-w-2xl mx-auto px-4 py-6 space-y-4">
    <h1 class="text-xl font-bold">Flight Log</h1>

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
import { useSyncStore } from '../stores/sync';
import FlightCard from '../components/FlightCard.vue';

const flights = useFlightsStore();
const sync = useSyncStore();

const recentFlights = computed(() =>
  flights.recentFlights.filter((f) => f.ended_at).sort((a, b) =>
    new Date(b.started_at) - new Date(a.started_at)
  )
);

function loadMore() {
  flights.loadOlderFlights(flights.pagination.page + 1);
}

onMounted(async () => {
  await flights.loadRecent();
  if (sync.online) {
    await flights.loadOlderFlights(1);
  }
});
</script>
