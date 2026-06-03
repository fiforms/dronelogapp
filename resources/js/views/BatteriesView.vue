<template>
  <div class="max-w-2xl mx-auto px-4 py-6 space-y-4">
    <div class="flex items-center justify-between">
      <h1 class="text-xl font-bold">Batteries</h1>
      <button class="text-blue-400 text-sm font-medium" @click="openForm()">+ Add</button>
    </div>

    <div v-if="fleet.batteries.length" class="space-y-2">
      <div v-for="bat in fleet.batteries" :key="bat.id" class="card flex items-start justify-between gap-3">
        <div class="flex-1 min-w-0">
          <p class="font-semibold text-sm">{{ bat.name }}</p>
          <p class="text-xs text-slate-400">
            <template v-if="bat.capacity_mah">{{ bat.capacity_mah }} mAh · </template>
            {{ bat.cycle_count }} cycles
          </p>
          <p v-if="bat.purchase_date" class="text-xs text-slate-500">Purchased: {{ bat.purchase_date }}</p>
        </div>
        <div class="flex gap-2">
          <button class="text-slate-400 hover:text-white text-xs" @click="openForm(bat)">Edit</button>
          <button class="text-red-500 hover:text-red-400 text-xs" @click="remove(bat.id)">Delete</button>
        </div>
      </div>
    </div>
    <p v-else class="text-slate-500 text-sm text-center py-8">No batteries yet.</p>

    <div v-if="showForm" class="fixed inset-0 bg-black/70 flex items-end sm:items-center justify-center z-50 px-4"
         @click.self="showForm = false">
      <div class="bg-slate-800 rounded-2xl w-full max-w-md p-6 space-y-4">
        <h2 class="font-bold text-lg">{{ editing ? 'Edit Battery' : 'Add Battery' }}</h2>

        <div>
          <label class="label">Name / Number <span class="text-red-400">*</span></label>
          <input v-model="form.name" type="text" class="input-field" placeholder="e.g. Battery #1" required />
        </div>
        <div>
          <label class="label">Capacity (mAh)</label>
          <input v-model.number="form.capacity_mah" type="number" class="input-field" placeholder="e.g. 2453" />
        </div>
        <div>
          <label class="label">Purchase Date</label>
          <input v-model="form.purchase_date" type="date" class="input-field" />
        </div>
        <div>
          <label class="label">Cycle Count</label>
          <input v-model.number="form.cycle_count" type="number" class="input-field" placeholder="0" min="0" />
        </div>
        <div>
          <label class="label">Notes</label>
          <textarea v-model="form.notes" rows="2" class="input-field" />
        </div>

        <p v-if="error" class="text-red-400 text-sm">{{ error }}</p>

        <div class="flex gap-3 pt-2">
          <button class="btn-secondary" style="width:auto;padding:0.75rem 1.5rem" @click="showForm = false">Cancel</button>
          <button class="btn-primary" @click="save" :disabled="saving">{{ saving ? 'Saving…' : 'Save' }}</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue';
import { useFleetStore } from '../stores/fleet';

const fleet = useFleetStore();
const showForm = ref(false);
const editing = ref(null);
const saving = ref(false);
const error = ref('');
const form = reactive({ name: '', capacity_mah: '', purchase_date: '', cycle_count: 0, notes: '' });

function openForm(bat = null) {
  editing.value = bat;
  Object.assign(form, bat ?? { name: '', capacity_mah: '', purchase_date: '', cycle_count: 0, notes: '' });
  error.value = '';
  showForm.value = true;
}

async function save() {
  if (!form.name) return;
  saving.value = true;
  error.value = '';
  try {
    await fleet.saveBattery({ ...form, id: editing.value?.id });
    showForm.value = false;
  } catch (e) {
    error.value = e.response?.data?.message ?? 'Failed to save';
  } finally {
    saving.value = false;
  }
}

async function remove(id) {
  if (!confirm('Delete this battery?')) return;
  await fleet.deleteBattery(id);
}
</script>
