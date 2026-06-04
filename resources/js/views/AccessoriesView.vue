<template>
  <div class="max-w-2xl mx-auto px-4 py-6 space-y-4">
    <div class="flex items-center justify-between">
      <h1 class="text-xl font-bold">Accessories</h1>
      <button class="text-blue-400 text-sm font-medium" @click="openForm()">+ Add</button>
    </div>

    <div v-if="fleet.accessories.length" class="space-y-2">
      <div
        v-for="acc in fleet.accessories"
        :key="acc.id"
        class="card flex items-start justify-between gap-3"
        :class="{ 'opacity-50': !acc.is_active }"
      >
        <div class="flex-1 min-w-0">
          <div class="flex items-center gap-2 flex-wrap">
            <p class="font-semibold text-sm">{{ acc.name }}</p>
            <span v-if="!acc.is_active"
              class="text-xs font-medium text-amber-400 bg-amber-900/40 px-2 py-0.5 rounded-full">
              Inactive
            </span>
          </div>
          <p v-if="acc.type" class="text-xs text-slate-400 capitalize">{{ acc.type }}</p>
          <p v-if="acc.notes" class="text-xs text-slate-500 truncate">{{ acc.notes }}</p>
        </div>
        <div class="flex gap-2 shrink-0">
          <template v-if="acc.is_active">
            <button class="text-slate-400 hover:text-white text-xs" @click="openForm(acc)">Edit</button>
            <button v-if="!acc.has_flights" class="text-red-500 hover:text-red-400 text-xs" @click="remove(acc.id)">Delete</button>
            <button v-else class="text-amber-500 hover:text-amber-400 text-xs" @click="deactivate(acc.id)">Deactivate</button>
          </template>
          <button v-else class="text-emerald-500 hover:text-emerald-400 text-xs" @click="reactivate(acc.id)">Reactivate</button>
        </div>
      </div>
    </div>
    <p v-else class="text-slate-500 text-sm text-center py-8">
      No accessories yet. Add ND filters, lights, etc.
    </p>

    <div v-if="showForm" class="fixed inset-0 bg-black/70 flex items-end sm:items-center justify-center z-50 px-4"
         @click.self="showForm = false">
      <div class="bg-slate-800 rounded-2xl w-full max-w-md p-6 space-y-4">
        <h2 class="font-bold text-lg">{{ editing ? 'Edit Accessory' : 'Add Accessory' }}</h2>

        <div>
          <label class="label">Name <span class="text-red-400">*</span></label>
          <input v-model="form.name" type="text" class="input-field" placeholder="e.g. ND32 Filter" required />
        </div>
        <div>
          <label class="label">Type</label>
          <select v-model="form.type" class="input-field">
            <option value="">— None —</option>
            <option value="filter">Filter</option>
            <option value="light">Light</option>
            <option value="other">Other</option>
          </select>
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
const form = reactive({ name: '', type: '', notes: '' });

function openForm(acc = null) {
  editing.value = acc;
  Object.assign(form, acc ?? { name: '', type: '', notes: '' });
  error.value = '';
  showForm.value = true;
}

async function save() {
  if (!form.name) return;
  saving.value = true;
  error.value = '';
  try {
    await fleet.saveAccessory({ ...form, id: editing.value?.id });
    showForm.value = false;
  } catch (e) {
    error.value = e.response?.data?.message ?? 'Failed to save';
  } finally {
    saving.value = false;
  }
}

async function remove(id) {
  if (!confirm('Delete this accessory?')) return;
  try {
    await fleet.deleteAccessory(id);
  } catch (e) {
    alert(e.response?.data?.message ?? 'Failed to delete');
  }
}

async function deactivate(id) {
  if (!confirm('Deactivate this accessory? It will be hidden from new flight forms but its history is preserved.')) return;
  await fleet.setAccessoryActive(id, false);
}

async function reactivate(id) {
  await fleet.setAccessoryActive(id, true);
}
</script>
