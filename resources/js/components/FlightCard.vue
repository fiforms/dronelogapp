<template>
  <RouterLink :to="`/flights/${flight.id ?? flight.client_uuid}`" class="card block hover:bg-slate-700 transition-colors">
    <div class="flex items-start justify-between gap-3">
      <div class="flex-1 min-w-0">
        <div class="flex items-center gap-2 mb-1">
          <span class="text-xs font-medium px-2 py-0.5 rounded-full"
            :class="flight.purpose === 'commercial' ? 'bg-amber-900 text-amber-300' : 'bg-blue-900 text-blue-300'">
            {{ flight.purpose === 'commercial' ? 'Commercial' : 'Recreational' }}
          </span>
          <span v-if="!flight.ended_at" class="text-xs font-medium px-2 py-0.5 rounded-full bg-emerald-900 text-emerald-300 animate-pulse">
            ACTIVE
          </span>
        </div>

        <p class="text-sm font-semibold text-slate-100 truncate">
          {{ droneName }} • {{ batteryName }}
        </p>

        <p class="text-xs text-slate-400 mt-0.5">
          {{ formatDate(flight.started_at) }}
          <span v-if="flight.ended_at"> · {{ duration }}</span>
        </p>

        <p v-if="flight.location_description" class="text-xs text-slate-500 truncate mt-0.5">
          📍 {{ flight.location_description }}
        </p>
      </div>

      <div class="flex flex-col items-end gap-1">
        <span v-if="!flight.synced" class="text-xs text-amber-400">⏳</span>
        <span v-else class="text-xs text-emerald-500">✓</span>
        <svg class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
        </svg>
      </div>
    </div>
  </RouterLink>
</template>

<script setup>
import { computed } from 'vue';
import { useFleetStore } from '../stores/fleet';

const props = defineProps({
  flight: { type: Object, required: true },
});

const fleet = useFleetStore();

const droneName = computed(() => {
  const drone = fleet.drones.find((d) => d.id === props.flight.drone_id || d.server_id === props.flight.drone_id);
  return drone?.name ?? props.flight.drone?.name ?? '—';
});

const batteryName = computed(() => {
  const bat = fleet.batteries.find((b) => b.id === props.flight.battery_id || b.server_id === props.flight.battery_id);
  return bat?.name ?? props.flight.battery?.name ?? '—';
});

const duration = computed(() => {
  if (!props.flight.ended_at || !props.flight.started_at) return '';
  const ms = new Date(props.flight.ended_at) - new Date(props.flight.started_at);
  const min = Math.floor(ms / 60000);
  return min < 60 ? `${min}m` : `${Math.floor(min / 60)}h ${min % 60}m`;
});

function formatDate(iso) {
  if (!iso) return '';
  return new Date(iso).toLocaleString(undefined, {
    month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit',
  });
}
</script>
