<template>
  <div class="max-w-2xl mx-auto px-4 py-6 space-y-6">
    <div class="text-center">
      <h1 class="text-2xl font-bold">Welcome back, {{ auth.user?.name }}.</h1>
      <p class="text-slate-400 text-sm mt-1">{{ today }}</p>
    </div>

    <!-- Active flight callout -->
    <div v-if="flights.currentFlight" class="card bg-emerald-900 border border-emerald-700">
      <p class="text-emerald-300 font-semibold text-sm mb-1">✈️ Flight in progress</p>
      <p class="text-white text-xs">Started {{ formatTime(flights.currentFlight.started_at) }}</p>
      <RouterLink :to="`/flights/${flights.currentFlight.id}/active`" class="btn-primary mt-3 block text-center">
        Return to Active Flight
      </RouterLink>
    </div>

    <!-- Fly now CTA -->
    <RouterLink v-else to="/flights/start" class="btn-primary block text-center text-lg py-5">
      ✈️ Start a Flight
    </RouterLink>

    <!-- Recent flights -->
    <div>
      <div class="flex items-center justify-between mb-3">
        <h2 class="section-title mb-0">Recent Flights</h2>
        <RouterLink to="/flights" class="text-sm text-blue-400">View all</RouterLink>
      </div>

      <div v-if="recentFlights.length" class="space-y-2">
        <FlightCard v-for="f in recentFlights.slice(0, 5)" :key="f.id ?? f.client_uuid" :flight="f" />
      </div>
      <p v-else class="text-slate-500 text-sm text-center py-8">
        No flights logged yet. Tap "Start a Flight" to begin.
      </p>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted } from 'vue';
import { useAuthStore } from '../stores/auth';
import { useFlightsStore } from '../stores/flights';
import FlightCard from '../components/FlightCard.vue';

const auth = useAuthStore();
const flights = useFlightsStore();

const today = new Date().toLocaleDateString(undefined, { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
const recentFlights = computed(() => flights.recentFlights.filter((f) => f.ended_at));

function formatTime(iso) {
  return new Date(iso).toLocaleTimeString(undefined, { hour: '2-digit', minute: '2-digit' });
}

onMounted(() => flights.loadRecent());
</script>
