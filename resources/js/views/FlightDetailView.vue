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
      <!-- Aborted banner -->
      <div v-if="flight.status === 'aborted'"
        class="flex items-start gap-3 bg-amber-900/40 border border-amber-700 rounded-lg px-4 py-3">
        <span class="text-amber-400 text-lg leading-none mt-0.5">&#9888;</span>
        <div>
          <p class="font-semibold text-amber-300">Flight Not Flown</p>
          <p class="text-sm text-amber-400 mt-0.5">
            This flight was logged as aborted before launch. It does not count toward flight hours.
          </p>
        </div>
      </div>

      <!-- Header info -->
      <div class="card space-y-2 text-sm">
        <div class="flex justify-between">
          <span class="text-slate-400">Date</span>
          <span>{{ formatDate(flight.started_at) }}</span>
        </div>
        <div v-if="flight.status !== 'aborted'" class="flex justify-between">
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

      <!-- Risk assessment -->
      <div v-if="riskScores.length" class="space-y-2">
        <div class="flex items-baseline justify-between">
          <h2 class="section-title">Risk Assessment</h2>
          <span class="text-sm" :class="hasSerious ? 'text-red-400 font-semibold' : totalRisk > 0 ? 'text-slate-400' : 'text-slate-600'">
            Score: {{ totalRisk }} / {{ riskScores.length * 3 }}
          </span>
        </div>
        <div v-for="item in riskScores" :key="item.risk_item_id ?? item.label" class="card text-sm">
          <div class="flex items-start justify-between gap-3">
            <p class="text-slate-300">{{ item.label }}</p>
            <span :class="riskChipClass(item.score)" class="shrink-0 text-xs font-bold px-2 py-0.5 rounded-full">
              {{ ['None', 'Minimal', 'Significant', 'Serious'][item.score] ?? 'None' }}
            </span>
          </div>
          <p v-if="item.mitigation_notes" class="text-xs text-slate-500 mt-1.5 italic">
            Mitigation: {{ item.mitigation_notes }}
          </p>
        </div>
      </div>

      <!-- Post-flight notes -->
      <div v-if="flight.post_flight_notes" class="card text-sm">
        <p class="label">Post-Flight Notes</p>
        <p class="text-slate-200 whitespace-pre-wrap">{{ flight.post_flight_notes }}</p>
      </div>

      <!-- Delete -->
      <div class="pt-2 border-t border-slate-700">
        <button class="btn-danger" :disabled="deleting" @click="confirmDelete = true">
          {{ deleting ? 'Deleting…' : 'Delete Flight Record' }}
        </button>
        <p class="text-xs text-slate-500 mt-2">
          The record is kept for audit purposes but hidden from your flight log and excluded from all counts.
        </p>
      </div>
    </template>

    <div v-else class="text-slate-400 text-center py-12">Flight not found.</div>

    <!-- Delete confirmation modal -->
    <Teleport to="body">
      <div v-if="confirmDelete" class="fixed inset-0 bg-black/70 flex items-center justify-center z-50 px-4">
        <div class="card max-w-sm w-full space-y-4">
          <h3 class="section-title text-red-400">Delete Flight Record?</h3>
          <p class="text-sm text-slate-300">
            This flight will be hidden from your log and excluded from all flight statistics.
            The record is retained for audit purposes and can be found via the "Show deleted" toggle
            in the flight log.
          </p>
          <div class="flex gap-3">
            <button class="btn-secondary" style="flex:1" @click="confirmDelete = false">Cancel</button>
            <button class="btn-danger" style="flex:1" :disabled="deleting" @click="doDelete">
              {{ deleting ? 'Deleting…' : 'Delete' }}
            </button>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useFlightsStore } from '../stores/flights';
import { useFleetStore } from '../stores/fleet';

const route   = useRoute();
const router  = useRouter();
const flights = useFlightsStore();
const fleet   = useFleetStore();

const loading       = ref(true);
const deleting      = ref(false);
const confirmDelete = ref(false);
const flight        = ref(null);

const flightId  = computed(() => route.params.id);
const checklist = computed(() => flight.value?.checklist ?? []);
const riskScores = computed(() => (flight.value?.risk_scores ?? []).filter((r) => r.score > 0));

const totalRisk  = computed(() => riskScores.value.reduce((s, r) => s + r.score, 0));
const hasSerious = computed(() => riskScores.value.some((r) => r.score === 3));

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

function riskChipClass(score) {
  if (score === 0) return 'bg-slate-700 text-slate-400';
  if (score === 1) return 'bg-blue-900/60 text-blue-300';
  if (score === 2) return 'bg-amber-900/60 text-amber-300';
  return 'bg-red-900/60 text-red-300';
}

function formatDate(iso) {
  return new Date(iso).toLocaleString(undefined, {
    weekday: 'short', month: 'short', day: 'numeric',
    year: 'numeric', hour: '2-digit', minute: '2-digit',
  });
}

async function doDelete() {
  deleting.value    = true;
  confirmDelete.value = false;
  try {
    await flights.deleteFlight(flight.value.id);
    router.push('/flights');
  } finally {
    deleting.value = false;
  }
}

onMounted(async () => {
  await flights.loadRecent();
  flight.value = await flights.getFlightById(flightId.value);
  loading.value = false;
});
</script>
