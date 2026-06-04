<template>
  <div class="flex flex-wrap gap-2">
    <button
      v-for="accessory in accessories"
      :key="accessory.id"
      type="button"
      :class="[
        'px-3 py-1.5 rounded-full text-sm font-medium border transition-colors',
        selected.includes(accessory.id)
          ? 'bg-blue-600 border-blue-500 text-white'
          : 'bg-slate-700 border-slate-600 text-slate-300 hover:border-blue-500',
      ]"
      @click="toggle(accessory.id)"
    >
      {{ accessory.name }}
    </button>
    <span v-if="!accessories.length" class="text-slate-500 text-sm">No accessories defined yet.</span>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { useFleetStore } from '../stores/fleet';

const props = defineProps({
  modelValue: { type: Array, default: () => [] },
});
const emit = defineEmits(['update:modelValue']);

const fleet = useFleetStore();
const accessories = computed(() => fleet.activeAccessories);
const selected = computed(() => props.modelValue);

function toggle(id) {
  const next = selected.value.includes(id)
    ? selected.value.filter((x) => x !== id)
    : [...selected.value, id];
  emit('update:modelValue', next);
}
</script>
