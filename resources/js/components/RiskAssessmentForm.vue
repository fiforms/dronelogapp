<template>
  <div class="space-y-4">

    <!-- Score summary banner -->
    <div v-if="hasSerious"
      class="flex items-start gap-3 bg-red-900/60 border border-red-600 rounded-lg px-4 py-3">
      <span class="text-red-400 text-xl leading-none mt-0.5">&#9888;</span>
      <div>
        <p class="font-semibold text-red-300">WARNING: Do Not Fly</p>
        <p class="text-sm text-red-400 mt-0.5">
          One or more risk factors are rated as serious or unmanageable. Flight is not recommended.
        </p>
      </div>
    </div>

    <div v-else-if="totalScore > threshold && totalScore > 0"
      class="flex items-start gap-3 bg-amber-900/50 border border-amber-600 rounded-lg px-4 py-3">
      <span class="text-amber-400 text-xl leading-none mt-0.5">&#9888;</span>
      <div>
        <p class="font-semibold text-amber-300">CAUTION: Reconsider This Flight</p>
        <p class="text-sm text-amber-400 mt-0.5">
          Total risk score ({{ totalScore }}) exceeds your threshold of {{ threshold }}.
          Review your mitigation steps before proceeding.
        </p>
      </div>
    </div>

    <div v-else-if="totalScore > 0"
      class="flex items-center gap-3 bg-emerald-900/40 border border-emerald-700 rounded-lg px-4 py-3">
      <span class="text-emerald-400 text-lg">&#10003;</span>
      <p class="text-sm text-emerald-300">
        Risk score {{ totalScore }} / {{ maxScore }} — within acceptable limits.
      </p>
    </div>

    <!-- Risk item rows -->
    <div v-for="(item, i) in items" :key="item.risk_item_id" class="card space-y-3">
      <div class="flex items-start justify-between gap-4">
        <div class="flex-1 min-w-0">
          <p class="text-sm font-medium text-slate-200">{{ item.label }}</p>
          <p v-if="item.description" class="text-xs text-slate-500 mt-0.5 leading-snug">
            {{ item.description }}
          </p>
        </div>
        <span :class="scoreChipClass(item.score)" class="shrink-0 text-xs font-bold px-2 py-0.5 rounded-full">
          {{ scoreLabel(item.score) }}
        </span>
      </div>

      <!-- Slider -->
      <div class="flex items-center gap-3">
        <span class="text-xs text-slate-500 w-4 text-center">0</span>
        <input
          type="range"
          min="0" max="3" step="1"
          :value="item.score"
          class="flex-1 accent-blue-500"
          :class="{ 'accent-red-500': item.score === 3, 'accent-amber-500': item.score === 2 }"
          @input="setScore(i, Number($event.target.value))"
        />
        <span class="text-xs text-slate-500 w-4 text-center">3</span>
      </div>

      <!-- Mitigation notes — appears when score > 0 -->
      <textarea
        v-if="item.score > 0"
        :value="item.mitigation_notes"
        rows="2"
        placeholder="Mitigation steps…"
        class="input-field text-sm"
        @input="setMitigation(i, $event.target.value)"
      />
    </div>

    <p v-if="!items.length" class="text-slate-500 text-sm text-center py-4">
      No risk items configured. Add items in Settings.
    </p>

    <!-- Score total footer -->
    <div v-if="items.length" class="flex items-center justify-between text-sm text-slate-400 pt-1">
      <span>Total risk score</span>
      <span :class="hasSerious ? 'text-red-400 font-bold' : totalScore > threshold ? 'text-amber-400 font-semibold' : 'text-slate-300'">
        {{ totalScore }} / {{ maxScore }}
      </span>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue';

const props = defineProps({
  modelValue: { type: Array, default: () => [] },
  threshold:  { type: Number, default: 6 },
});
const emit = defineEmits(['update:modelValue']);

const items = ref(props.modelValue.map((i) => ({ ...i })));

watch(() => props.modelValue, (val) => {
  items.value = val.map((i) => ({ ...i }));
}, { deep: true });

const totalScore = computed(() => items.value.reduce((sum, i) => sum + (i.score ?? 0), 0));
const maxScore   = computed(() => items.value.length * 3);
const hasSerious = computed(() => items.value.some((i) => i.score === 3));

function setScore(idx, score) {
  items.value[idx].score = score;
  emitUpdate();
}

function setMitigation(idx, notes) {
  items.value[idx].mitigation_notes = notes;
  emitUpdate();
}

function scoreLabel(score) {
  return ['None', 'Minimal', 'Significant', 'Serious'][score] ?? 'None';
}

function scoreChipClass(score) {
  if (score === 0) return 'bg-slate-700 text-slate-400';
  if (score === 1) return 'bg-blue-900/60 text-blue-300';
  if (score === 2) return 'bg-amber-900/60 text-amber-300';
  return 'bg-red-900/60 text-red-300';
}

function emitUpdate() {
  emit('update:modelValue', items.value.map((i) => ({
    risk_item_id:     i.risk_item_id,
    label:            i.label,
    description:      i.description ?? null,
    score:            i.score ?? 0,
    mitigation_notes: i.mitigation_notes ?? null,
  })));
}
</script>
