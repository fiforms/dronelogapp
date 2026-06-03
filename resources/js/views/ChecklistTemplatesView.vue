<template>
  <div class="max-w-2xl mx-auto px-4 py-6 space-y-4">
    <div class="flex items-center justify-between">
      <h1 class="text-xl font-bold">Checklists</h1>
    </div>

    <p class="text-slate-400 text-sm">
      Customize your pre-flight checklist. The default template is used when starting a flight.
    </p>

    <div v-for="template in fleet.checklistTemplates" :key="template.id" class="card space-y-3">
      <div class="flex items-center justify-between">
        <h2 class="font-semibold">
          {{ template.name }}
          <span v-if="template.is_default" class="ml-2 text-xs font-normal text-blue-400 bg-blue-900/40 px-2 py-0.5 rounded-full">
            Default
          </span>
        </h2>
        <button class="text-blue-400 text-xs" @click="openItemForm(template)">+ Add item</button>
      </div>

      <div class="space-y-2">
        <div v-for="item in template.items" :key="item.id"
             class="flex items-start gap-2 text-sm py-1 border-b border-slate-700 last:border-0">
          <span class="text-slate-500 w-5 text-right shrink-0">{{ item.sort_order }}.</span>
          <span class="flex-1 text-slate-200">{{ item.label }}</span>
          <button class="text-slate-500 hover:text-red-400 text-xs shrink-0" @click="deleteItem(item.id)">✕</button>
        </div>
        <p v-if="!template.items?.length" class="text-slate-500 text-sm italic">No items yet.</p>
      </div>
    </div>

    <!-- Add item modal -->
    <div v-if="addingToTemplate" class="fixed inset-0 bg-black/70 flex items-end sm:items-center justify-center z-50 px-4"
         @click.self="addingToTemplate = null">
      <div class="bg-slate-800 rounded-2xl w-full max-w-md p-6 space-y-4">
        <h2 class="font-bold text-lg">Add Checklist Item</h2>

        <div>
          <label class="label">Item Label <span class="text-red-400">*</span></label>
          <input v-model="newItemLabel" type="text" class="input-field"
            placeholder="e.g. Confirm memory card inserted" autofocus />
        </div>

        <p v-if="error" class="text-red-400 text-sm">{{ error }}</p>

        <div class="flex gap-3 pt-2">
          <button class="btn-secondary" style="width:auto;padding:0.75rem 1.5rem"
            @click="addingToTemplate = null">Cancel</button>
          <button class="btn-primary" @click="addItem" :disabled="saving">
            {{ saving ? 'Adding…' : 'Add Item' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';
import { useFleetStore } from '../stores/fleet';

const fleet = useFleetStore();
const addingToTemplate = ref(null);
const newItemLabel = ref('');
const saving = ref(false);
const error = ref('');

function openItemForm(template) {
  addingToTemplate.value = template;
  newItemLabel.value = '';
  error.value = '';
}

async function addItem() {
  if (!newItemLabel.value.trim()) return;
  saving.value = true;
  try {
    const sortOrder = (addingToTemplate.value.items?.length ?? 0) + 1;
    const { data } = await axios.post(
      `/api/v1/checklist-templates/${addingToTemplate.value.id}/items`,
      { label: newItemLabel.value.trim(), sort_order: sortOrder }
    );
    addingToTemplate.value.items = [...(addingToTemplate.value.items ?? []), data.data];
    addingToTemplate.value = null;
  } catch (e) {
    error.value = e.response?.data?.message ?? 'Failed to add item';
  } finally {
    saving.value = false;
  }
}

async function deleteItem(itemId) {
  if (!confirm('Remove this checklist item?')) return;
  await axios.delete(`/api/v1/items/${itemId}`);
  for (const t of fleet.checklistTemplates) {
    t.items = t.items?.filter((i) => i.id !== itemId) ?? [];
  }
}

onMounted(() => {
  if (!fleet.loaded) fleet.fetchAll();
});
</script>
