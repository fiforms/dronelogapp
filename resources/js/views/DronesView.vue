<template>
  <div class="max-w-2xl mx-auto px-4 py-6 space-y-4">
    <div class="flex items-center justify-between">
      <h1 class="text-xl font-bold">Drones</h1>
      <button class="text-blue-400 text-sm font-medium" @click="openForm()">+ Add</button>
    </div>

    <div v-if="fleet.drones.length" class="space-y-2">
      <div v-for="drone in fleet.drones" :key="drone.id" class="card flex items-start justify-between gap-3">
        <div class="flex-1 min-w-0">
          <p class="font-semibold text-sm">{{ drone.name }}</p>
          <p class="text-xs text-slate-400">{{ drone.model }} · Serial: {{ drone.serial }}</p>
          <p v-if="drone.registration_number" class="text-xs text-slate-500">
            FAA Reg: {{ drone.registration_number }}
          </p>
          <p v-if="drone.notes" class="text-xs text-slate-500 mt-0.5 truncate">{{ drone.notes }}</p>
        </div>
        <div class="flex gap-2">
          <button class="text-slate-400 hover:text-white text-xs" @click="openForm(drone)">Edit</button>
          <button class="text-red-500 hover:text-red-400 text-xs" @click="remove(drone.id)">Delete</button>
        </div>
      </div>
    </div>
    <p v-else class="text-slate-500 text-sm text-center py-8">No drones yet. Add your first drone above.</p>

    <!-- Modal form -->
    <div v-if="showForm" class="fixed inset-0 bg-black/70 flex items-end sm:items-center justify-center z-50 px-4"
         @click.self="showForm = false">
      <div class="bg-slate-800 rounded-2xl w-full max-w-md p-6 space-y-4">
        <h2 class="font-bold text-lg">{{ editing ? 'Edit Drone' : 'Add Drone' }}</h2>

        <div>
          <label class="label">Display Name <span class="text-red-400">*</span></label>
          <input v-model="form.name" type="text" class="input-field" placeholder="e.g. Mini 4 Pro" required />
        </div>
        <div>
          <label class="label">Model <span class="text-red-400">*</span></label>
          <input v-model="form.model" type="text" class="input-field" placeholder="e.g. DJI Mini 4 Pro" required />
        </div>
        <div>
          <label class="label">Serial Number <span class="text-red-400">*</span></label>
          <input v-model="form.serial" type="text" class="input-field" placeholder="e.g. MIN4P-001" required />
        </div>
        <div>
          <label class="label">FAA Registration #</label>
          <input v-model="form.registration_number" type="text" class="input-field" placeholder="Optional" />
        </div>
        <div>
          <label class="label">Notes</label>
          <textarea v-model="form.notes" rows="2" class="input-field" placeholder="Optional" />
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
const form = reactive({ name: '', model: '', serial: '', registration_number: '', notes: '' });

function openForm(drone = null) {
  editing.value = drone;
  Object.assign(form, drone ?? { name: '', model: '', serial: '', registration_number: '', notes: '' });
  error.value = '';
  showForm.value = true;
}

async function save() {
  if (!form.name || !form.model || !form.serial) return;
  saving.value = true;
  error.value = '';
  try {
    await fleet.saveDrone({ ...form, id: editing.value?.id });
    showForm.value = false;
  } catch (e) {
    error.value = e.response?.data?.message ?? 'Failed to save';
  } finally {
    saving.value = false;
  }
}

async function remove(id) {
  if (!confirm('Delete this drone?')) return;
  await fleet.deleteDrone(id);
}
</script>
