<template>
  <div class="space-y-3">
    <div
      v-for="(item, i) in items"
      :key="item.checklist_item_id"
      class="card"
    >
      <label class="flex items-start gap-3 cursor-pointer">
        <input
          type="checkbox"
          :checked="item.checked"
          class="mt-0.5 h-5 w-5 rounded border-slate-500 bg-slate-700 text-blue-500
                 focus:ring-blue-500 focus:ring-offset-slate-800 cursor-pointer"
          @change="setChecked(i, $event.target.checked)"
        />
        <span :class="item.checked ? 'line-through text-slate-500' : 'text-slate-200'" class="text-sm leading-snug">
          {{ item.label }}
        </span>
      </label>

      <textarea
        v-if="item.has_comment_box || item.checked"
        v-model="item.comment"
        rows="2"
        placeholder="Optional comment…"
        class="input-field mt-2 text-sm"
        @input="emitUpdate"
      />
    </div>

    <p v-if="!items.length" class="text-slate-500 text-sm text-center py-4">
      No checklist items found. Add items in the Checklists section.
    </p>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue';

const props = defineProps({
  modelValue: { type: Array, default: () => [] },
});
const emit = defineEmits(['update:modelValue']);

const items = ref(props.modelValue.map((i) => ({ ...i })));

watch(() => props.modelValue, (val) => {
  items.value = val.map((i) => ({ ...i }));
}, { deep: true });

function setChecked(idx, checked) {
  items.value[idx].checked = checked;
  emitUpdate();
}

function emitUpdate() {
  emit('update:modelValue', items.value.map((i) => ({
    checklist_item_id: i.checklist_item_id,
    label:             i.label,
    has_comment_box:   i.has_comment_box,
    checked:           i.checked,
    comment:           i.comment ?? null,
  })));
}
</script>
