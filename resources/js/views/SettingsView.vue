<template>
  <div class="max-w-2xl mx-auto px-4 py-6 space-y-6">
    <h1 class="text-xl font-bold">Settings</h1>

    <!-- Risk Assessment threshold -->
    <div class="card space-y-3">
      <h2 class="section-title">Flight Risk Assessment</h2>
      <p class="text-sm text-slate-400">
        Set the total risk score threshold above which you will be prompted to reconsider a flight.
        Scores range from 0–3 per factor. A score of 3 on any single factor always shows a
        "Do Not Fly" warning regardless of this setting.
      </p>
      <div class="flex items-center gap-4">
        <label class="label mb-0 shrink-0">Caution Threshold</label>
        <input
          v-model.number="riskThreshold"
          type="number"
          min="1"
          max="24"
          class="input-field w-24 text-center"
        />
        <button class="btn-primary" style="width:auto;padding-left:1.25rem;padding-right:1.25rem"
          :disabled="thresholdSaving" @click="saveThreshold">
          {{ thresholdSaving ? 'Saving…' : thresholdSaved ? 'Saved ✓' : 'Save' }}
        </button>
      </div>
      <p class="text-xs text-slate-500">
        Default is 6. With 8 risk factors, a score of 6 averages 0.75 per item.
      </p>
    </div>

    <!-- Risk item management -->
    <div class="card space-y-3">
      <div class="flex items-center justify-between">
        <h2 class="section-title">Risk Factors</h2>
        <button class="btn-secondary text-sm px-3 py-1.5" style="width:auto" @click="openRiskForm()">
          + Add Factor
        </button>
      </div>
      <p class="text-sm text-slate-400">
        These factors appear in the risk assessment step of every new flight. Disable a factor to
        hide it without losing its label.
      </p>

      <div v-if="riskStore.items.length" class="space-y-2">
        <div v-for="item in riskStore.items" :key="item.id"
          class="flex items-start gap-3 py-2 border-b border-slate-700 last:border-0">
          <div class="flex-1 min-w-0">
            <p :class="item.is_active === false ? 'text-slate-500 line-through' : 'text-slate-200'" class="text-sm font-medium">
              {{ item.label }}
            </p>
            <p v-if="item.description" class="text-xs text-slate-500 mt-0.5 leading-snug">
              {{ item.description }}
            </p>
          </div>
          <div class="flex items-center gap-2 shrink-0">
            <button class="text-xs text-blue-400 hover:text-blue-300" @click="openRiskForm(item)">Edit</button>
            <button v-if="item.is_active !== false" class="text-xs text-slate-500 hover:text-amber-400"
              @click="riskStore.remove(item.id)">Disable</button>
            <button v-else class="text-xs text-slate-500 hover:text-emerald-400"
              @click="riskStore.save({ id: item.id, is_active: true })">Enable</button>
          </div>
        </div>
      </div>
      <p v-else class="text-slate-500 text-sm text-center py-4">No risk factors yet.</p>
    </div>

    <!-- Risk factor form modal -->
    <Teleport to="body">
      <div v-if="showRiskForm" class="fixed inset-0 bg-black/70 flex items-center justify-center z-50 px-4">
        <div class="card max-w-md w-full space-y-4">
          <h3 class="section-title">{{ riskForm.id ? 'Edit Risk Factor' : 'Add Risk Factor' }}</h3>
          <div>
            <label class="label">Label</label>
            <input v-model="riskForm.label" type="text" class="input-field" placeholder="e.g. Weather" />
          </div>
          <div>
            <label class="label">Description <span class="text-slate-500 font-normal">(shown as helper text)</span></label>
            <textarea v-model="riskForm.description" rows="3" class="input-field text-sm"
              placeholder="Brief guidance on how to score this factor…" />
          </div>
          <div class="flex gap-3 pt-1">
            <button class="btn-secondary" style="flex:1" @click="showRiskForm = false">Cancel</button>
            <button class="btn-primary" style="flex:1" :disabled="riskSaving || !riskForm.label"
              @click="saveRiskItem">
              {{ riskSaving ? 'Saving…' : 'Save' }}
            </button>
          </div>
        </div>
      </div>
    </Teleport>

    <!-- Backup -->
    <div class="card space-y-3">
      <h2 class="section-title">Backup</h2>
      <p class="text-sm text-slate-400">
        Download a complete copy of all your flights, drones, batteries, accessories, and
        checklist templates as a JSON file.
      </p>
      <button class="btn-primary" :disabled="backupLoading" @click="downloadBackup">
        {{ backupLoading ? 'Preparing…' : 'Download Backup' }}
      </button>
      <p v-if="backupError" class="text-sm text-red-400">{{ backupError }}</p>
    </div>

    <!-- Restore -->
    <div class="card space-y-3">
      <h2 class="section-title">Restore</h2>
      <p class="text-sm text-slate-400">
        Load a previously downloaded backup file. All drones, batteries, accessories,
        checklist templates, and flights in the backup will be added to this account.
        Best used on a fresh account with no existing data.
      </p>

      <div class="bg-amber-900/40 border border-amber-700 rounded-lg px-3 py-2 text-sm text-amber-300">
        <strong>Warning:</strong> Restoring does not delete existing data — it adds on top of it.
        If you restore to an account that already has flights or fleet items, you may end up with
        duplicates.
      </div>

      <div v-if="!restoreFile">
        <label class="block">
          <span class="label">Select backup file (.json)</span>
          <input
            type="file"
            accept=".json,application/json"
            class="block w-full text-sm text-slate-300 mt-1
                   file:mr-3 file:py-1.5 file:px-3
                   file:rounded file:border-0
                   file:text-sm file:font-medium
                   file:bg-slate-700 file:text-slate-200
                   hover:file:bg-slate-600 cursor-pointer"
            @change="onFileSelected"
          />
        </label>
      </div>

      <div v-else class="space-y-3">
        <div class="flex items-center justify-between bg-slate-700 rounded-lg px-3 py-2">
          <span class="text-sm text-slate-200 truncate">{{ restoreFile.name }}</span>
          <button class="text-xs text-slate-400 hover:text-slate-100 ml-3 shrink-0" @click="clearFile">
            Remove
          </button>
        </div>

        <div v-if="restoreSummary" class="text-sm text-slate-300 space-y-0.5">
          <p>Found in backup:</p>
          <ul class="list-disc list-inside text-slate-400 text-xs space-y-0.5">
            <li>{{ restoreSummary.drones }} drone(s)</li>
            <li>{{ restoreSummary.batteries }} batter{{ restoreSummary.batteries === 1 ? 'y' : 'ies' }}</li>
            <li>{{ restoreSummary.accessories }} accessor{{ restoreSummary.accessories === 1 ? 'y' : 'ies' }}</li>
            <li>{{ restoreSummary.templates }} checklist template(s)</li>
            <li>{{ restoreSummary.flights }} flight(s)</li>
          </ul>
          <p class="text-slate-500 text-xs pt-1">Exported {{ restoreSummary.exported_at }}</p>
        </div>

        <button
          class="btn-danger"
          :disabled="restoreLoading"
          @click="runRestore"
        >
          {{ restoreLoading ? 'Restoring…' : 'Restore from Backup' }}
        </button>
      </div>

      <div v-if="restoreResult" class="bg-emerald-900/40 border border-emerald-700 rounded-lg px-3 py-2 text-sm text-emerald-300 space-y-0.5">
        <p class="font-semibold">Restore complete.</p>
        <ul class="list-disc list-inside text-xs space-y-0.5">
          <li>{{ restoreResult.drones_created }} drone(s) created</li>
          <li>{{ restoreResult.batteries_created }} batter{{ restoreResult.batteries_created === 1 ? 'y' : 'ies' }} created</li>
          <li>{{ restoreResult.accessories_created }} accessor{{ restoreResult.accessories_created === 1 ? 'y' : 'ies' }} created</li>
          <li>{{ restoreResult.templates_created }} checklist template(s) created</li>
          <li>{{ restoreResult.flights_synced }} flight(s) synced</li>
        </ul>
        <p v-if="restoreResult.errors?.length" class="text-amber-400 text-xs pt-1">
          {{ restoreResult.errors.length }} flight(s) failed to restore.
        </p>
      </div>

      <p v-if="restoreError" class="text-sm text-red-400">{{ restoreError }}</p>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';
import { useRiskItemsStore } from '../stores/riskItems';

const riskStore       = useRiskItemsStore();
const riskThreshold   = ref(6);
const thresholdSaving = ref(false);
const thresholdSaved  = ref(false);

onMounted(async () => {
  await riskStore.fetchAll();
  riskThreshold.value = await riskStore.fetchThreshold();
});

async function saveThreshold() {
  thresholdSaving.value = true;
  thresholdSaved.value  = false;
  try {
    riskThreshold.value = await riskStore.saveThreshold(riskThreshold.value);
    thresholdSaved.value = true;
    setTimeout(() => { thresholdSaved.value = false; }, 2000);
  } finally {
    thresholdSaving.value = false;
  }
}

// --- Risk item form ---
const showRiskForm = ref(false);
const riskSaving   = ref(false);
const riskForm     = ref({ id: null, label: '', description: '' });

function openRiskForm(item = null) {
  riskForm.value    = item
    ? { id: item.id, label: item.label, description: item.description ?? '' }
    : { id: null, label: '', description: '' };
  showRiskForm.value = true;
}

async function saveRiskItem() {
  if (!riskForm.value.label.trim()) return;
  riskSaving.value = true;
  try {
    await riskStore.save(riskForm.value);
    showRiskForm.value = false;
  } finally {
    riskSaving.value = false;
  }
}

// --- Backup ---
const backupLoading = ref(false);
const backupError   = ref(null);

async function downloadBackup() {
  backupLoading.value = true;
  backupError.value   = null;
  try {
    const { data } = await axios.get('/api/v1/backup');
    const blob     = new Blob([JSON.stringify(data, null, 2)], { type: 'application/json' });
    const url      = URL.createObjectURL(blob);
    const date     = new Date().toISOString().slice(0, 10);
    const a        = document.createElement('a');
    a.href         = url;
    a.download     = `dronelogapp-backup-${date}.json`;
    a.click();
    URL.revokeObjectURL(url);
  } catch {
    backupError.value = 'Failed to download backup. Please try again.';
  } finally {
    backupLoading.value = false;
  }
}

// --- Restore ---
const restoreFile    = ref(null);
const restoreSummary = ref(null);
const restoreLoading = ref(false);
const restoreResult  = ref(null);
const restoreError   = ref(null);
let   parsedBackup   = null;

function onFileSelected(event) {
  restoreResult.value  = null;
  restoreError.value   = null;
  restoreSummary.value = null;
  parsedBackup         = null;

  const file = event.target.files?.[0];
  if (!file) return;

  const reader = new FileReader();
  reader.onload = (e) => {
    try {
      const json = JSON.parse(e.target.result);
      if (!json.version || !json.flights) {
        restoreError.value = 'This does not look like a valid DroneLog backup file.';
        return;
      }
      parsedBackup         = json;
      restoreFile.value    = file;
      restoreSummary.value = {
        drones:      (json.drones ?? []).length,
        batteries:   (json.batteries ?? []).length,
        accessories: (json.accessories ?? []).length,
        templates:   (json.checklist_templates ?? []).length,
        flights:     (json.flights ?? []).length,
        exported_at: json.exported_at
          ? new Date(json.exported_at).toLocaleString()
          : 'unknown',
      };
    } catch {
      restoreError.value = 'Could not parse the file. Make sure it is a valid JSON backup.';
    }
  };
  reader.readAsText(file);
}

function clearFile() {
  restoreFile.value    = null;
  restoreSummary.value = null;
  restoreResult.value  = null;
  restoreError.value   = null;
  parsedBackup         = null;
}

async function runRestore() {
  if (!parsedBackup) return;
  restoreLoading.value = true;
  restoreError.value   = null;
  restoreResult.value  = null;
  try {
    const { data } = await axios.post('/api/v1/backup/restore', parsedBackup);
    restoreResult.value = data;
    clearFile();
  } catch (err) {
    restoreError.value = err.response?.data?.message ?? 'Restore failed. Please try again.';
  } finally {
    restoreLoading.value = false;
  }
}
</script>
