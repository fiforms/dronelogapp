<template>
  <div class="max-w-2xl mx-auto px-4 py-6 space-y-5">
    <h1 class="text-xl font-bold">End Flight</h1>

    <div v-if="flight" class="card text-sm space-y-1">
      <p class="text-slate-400">Started: <span class="text-white">{{ formatTime(flight.started_at) }}</span></p>
      <p class="text-slate-400">Duration so far: <span class="text-white">{{ elapsed }}</span></p>
    </div>

    <div>
      <label class="label">Post-Flight Notes</label>
      <textarea v-model="notes" rows="5" class="input-field"
        placeholder="Any observations, incidents, anomalies, or notes about the flight…" />
    </div>

    <div class="flex gap-3">
      <RouterLink :to="`/flights/${flightId}/active`"
        class="btn-secondary" style="width:auto;flex:0 0 auto;padding-left:1.5rem;padding-right:1.5rem">
        ← Back
      </RouterLink>
      <button class="btn-primary" @click="save" :disabled="saving">
        {{ saving ? 'Saving…' : '✅ Save & Complete Flight' }}
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useFlightsStore } from '../stores/flights';

const route = useRoute();
const router = useRouter();
const flights = useFlightsStore();

const flightId = computed(() => route.params.id);
const flight = computed(() => flights.recentFlights.find((f) => String(f.id) === String(flightId.value)));
const notes = ref('');
const saving = ref(false);

const elapsed = computed(() => {
  if (!flight.value?.started_at) return '';
  const ms = Date.now() - new Date(flight.value.started_at).getTime();
  const m = Math.floor(ms / 60000);
  return m < 60 ? `${m} min` : `${Math.floor(m / 60)}h ${m % 60}m`;
});

function formatTime(iso) {
  return new Date(iso).toLocaleTimeString(undefined, { hour: '2-digit', minute: '2-digit' });
}

async function save() {
  saving.value = true;
  try {
    await flights.endFlight(Number(flightId.value), {
      ended_at: new Date().toISOString(),
      post_flight_notes: notes.value || null,
    });
    router.push(`/flights/${flightId.value}`);
  } finally {
    saving.value = false;
  }
}

onMounted(() => flights.loadRecent());
</script>
