<template>
  <div class="flex items-center gap-2 text-sm">
    <template v-if="loading">
      <svg class="w-4 h-4 animate-spin text-blue-400" fill="none" viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
      </svg>
      <span class="text-slate-400">Getting GPS…</span>
    </template>
    <template v-else-if="modelValue.lat && modelValue.lng">
      <span class="text-emerald-400">📍</span>
      <span class="text-slate-300 font-mono text-xs">
        {{ modelValue.lat.toFixed(5) }}, {{ modelValue.lng.toFixed(5) }}
      </span>
      <button class="text-slate-500 hover:text-slate-300 text-xs" @click="capture">Refresh</button>
    </template>
    <template v-else>
      <span class="text-amber-400">📍</span>
      <span class="text-slate-400">{{ error || 'No GPS yet' }}</span>
      <button class="text-blue-400 hover:text-blue-300 text-xs" @click="capture">Get location</button>
    </template>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';

const props = defineProps({
  modelValue: { type: Object, default: () => ({ lat: null, lng: null }) },
});
const emit = defineEmits(['update:modelValue']);

const loading = ref(false);
const error = ref('');

function capture() {
  if (!navigator.geolocation) {
    error.value = 'GPS not available';
    return;
  }
  loading.value = true;
  error.value = '';
  navigator.geolocation.getCurrentPosition(
    (pos) => {
      emit('update:modelValue', { lat: pos.coords.latitude, lng: pos.coords.longitude });
      loading.value = false;
    },
    (err) => {
      error.value = err.message;
      loading.value = false;
    },
    { enableHighAccuracy: true, timeout: 10000 }
  );
}

onMounted(() => {
  if (!props.modelValue.lat) capture();
});
</script>
