<template>
  <div class="max-w-2xl mx-auto px-4 py-6">
    <!-- Step indicator -->
    <div class="flex items-center gap-2 mb-6">
      <button class="flex items-center gap-2 text-sm font-medium"
        :class="step === 1 ? 'text-blue-400' : 'text-slate-500'"
        @click="step = 1">
        <span class="w-6 h-6 rounded-full text-xs flex items-center justify-center font-bold"
          :class="step === 1 ? 'bg-blue-600 text-white' : 'bg-slate-600 text-slate-400'">1</span>
        Flight Info
      </button>
      <div class="flex-1 h-px bg-slate-700"></div>
      <button class="flex items-center gap-2 text-sm font-medium"
        :class="step === 2 ? 'text-blue-400' : 'text-slate-500'"
        :disabled="!form.drone_id">
        <span class="w-6 h-6 rounded-full text-xs flex items-center justify-center font-bold"
          :class="step === 2 ? 'bg-blue-600 text-white' : 'bg-slate-600 text-slate-400'">2</span>
        Pre-Flight Check
      </button>
      <div class="flex-1 h-px bg-slate-700"></div>
      <button class="flex items-center gap-2 text-sm font-medium"
        :class="step === 3 ? 'text-blue-400' : 'text-slate-500'"
        :disabled="!form.drone_id">
        <span class="w-6 h-6 rounded-full text-xs flex items-center justify-center font-bold"
          :class="step === 3 ? 'bg-blue-600 text-white' : 'bg-slate-600 text-slate-400'">3</span>
        Risk Assessment
      </button>
    </div>

    <!-- Step 1: Flight details -->
    <form v-if="step === 1" class="space-y-5" @submit.prevent="goToChecklist">
      <div>
        <label class="label">Drone <span class="text-red-400">*</span></label>
        <select v-model="form.drone_id" class="input-field" required>
          <option value="">— Select drone —</option>
          <option v-for="d in fleet.activeDrones" :key="d.id" :value="d.id">{{ d.name }} ({{ d.model }})</option>
        </select>
        <RouterLink to="/drones" class="text-xs text-blue-400 mt-1 inline-block">+ Add a drone</RouterLink>
      </div>

      <div>
        <label class="label">Battery</label>
        <select v-model="form.battery_id" class="input-field">
          <option value="">— None / Unknown —</option>
          <option v-for="b in fleet.activeBatteries" :key="b.id" :value="b.id">{{ b.name }}</option>
        </select>
      </div>

      <div>
        <label class="label">Battery % at Start</label>
        <input v-model.number="form.battery_pct_start" type="number" class="input-field"
          placeholder="e.g. 100" min="0" max="100" />
      </div>

      <div>
        <label class="label">Accessories</label>
        <AccessoryPicker v-model="form.accessories" />
      </div>

      <div>
        <label class="label">Launch Location (GPS)</label>
        <GpsCapture v-model="gps" />
      </div>

      <div>
        <label class="label">Location Description</label>
        <input v-model="form.location_description" type="text" class="input-field"
          placeholder="e.g. Park at 5th and Main, northwest corner" />
      </div>

      <div>
        <label class="label">Flight Plan / Intent</label>
        <textarea v-model="form.flight_plan" rows="3" class="input-field"
          placeholder="Describe your intended flight path and mission…" />
      </div>

      <div>
        <label class="label">Flight Purpose <span class="text-red-400">*</span></label>
        <select v-model="form.purpose" class="input-field" required>
          <option value="recreational">Recreational / Hobby</option>
          <option value="commercial">Commercial / Business</option>
        </select>
        <textarea v-if="form.purpose === 'commercial'" v-model="form.purpose_notes" rows="2"
          class="input-field mt-2" placeholder="Brief description of commercial use…" />
      </div>

      <div>
        <label class="label">LAANC / Airspace Authorization</label>
        <select v-model="form.laanc_status" class="input-field">
          <option value="na">Not Applicable (uncontrolled airspace)</option>
          <option value="not_needed">Not Needed (waived or exempt)</option>
          <option value="received">LAANC Authorization Received</option>
        </select>
        <div v-if="form.laanc_status === 'received'" class="flex gap-2 mt-2">
          <input v-model="form.laanc_authorization_number"
            type="text" class="input-field flex-1" placeholder="Authorization number" />
          <button type="button" @click="pasteLaanc" class="btn-secondary px-3 text-sm shrink-0">Paste</button>
        </div>
      </div>

      <button type="submit" class="btn-primary" :disabled="!form.drone_id">
        Next: Pre-Flight Checklist →
      </button>
    </form>

    <!-- Step 2: Pre-flight checklist -->
    <div v-else-if="step === 2" class="space-y-4">
      <h2 class="section-title">Pre-Flight Checklist</h2>
      <p class="text-slate-400 text-sm">Complete each item before launching.</p>

      <ChecklistForm v-model="checklist" />

      <div class="flex gap-3 pt-2 flex-wrap">
        <button class="btn-secondary" style="width:auto;flex:0 0 auto;padding-left:1.5rem;padding-right:1.5rem"
          @click="step = 1">← Back</button>
        <button class="btn-primary" style="flex:1" @click="step = 3">
          Next: Risk Assessment →
        </button>
      </div>

      <!-- Abort button -->
      <div class="pt-2 border-t border-slate-700">
        <button class="btn-danger" :disabled="aborting" @click="confirmAbort">
          {{ aborting ? 'Logging…' : 'Log and Abort Flight' }}
        </button>
        <p class="text-xs text-slate-500 mt-2">
          Records this flight attempt in the log as not flown, with the current checklist data.
        </p>
      </div>
    </div>

    <!-- Step 3: Risk assessment -->
    <div v-else-if="step === 3" class="space-y-4">
      <h2 class="section-title">Flight Risk Assessment</h2>
      <p class="text-slate-400 text-sm">
        Rate each factor from 0 (no risk) to 3 (serious / unmanageable). A mitigation note is
        required for any item scored above 0.
      </p>

      <RiskAssessmentForm v-model="riskScores" :threshold="riskThreshold" />

      <div class="flex gap-3 pt-2 flex-wrap">
        <button class="btn-secondary" style="width:auto;flex:0 0 auto;padding-left:1.5rem;padding-right:1.5rem"
          @click="step = 2">← Back</button>
        <button class="btn-primary" style="flex:1" :disabled="launching" @click="launchFlight">
          {{ launching ? 'Starting…' : '🚀 Launch Flight' }}
        </button>
      </div>

      <!-- Abort button -->
      <div class="pt-2 border-t border-slate-700">
        <button class="btn-danger" :disabled="aborting" @click="confirmAbort">
          {{ aborting ? 'Logging…' : 'Log and Abort Flight' }}
        </button>
        <p class="text-xs text-slate-500 mt-2">
          Records this flight attempt with the checklist and risk assessment data, noted as not flown.
        </p>
      </div>
    </div>

    <!-- Abort confirmation modal -->
    <Teleport to="body">
      <div v-if="showAbortConfirm" class="fixed inset-0 bg-black/70 flex items-center justify-center z-50 px-4">
        <div class="card max-w-sm w-full space-y-4">
          <h3 class="section-title text-amber-400">Log and Abort Flight?</h3>
          <p class="text-sm text-slate-300">
            This will record a flight attempt in your log marked as "Not Flown", preserving your
            pre-flight data and any risk assessment notes. It will not count toward your flight hours.
          </p>
          <div class="flex gap-3">
            <button class="btn-secondary" style="width:auto;flex:1" @click="showAbortConfirm = false">
              Cancel
            </button>
            <button class="btn-danger" style="flex:1" :disabled="aborting" @click="doAbort">
              {{ aborting ? 'Logging…' : 'Confirm Abort' }}
            </button>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useFleetStore } from '../stores/fleet';
import { useFlightsStore } from '../stores/flights';
import { useRiskItemsStore } from '../stores/riskItems';
import AccessoryPicker from '../components/AccessoryPicker.vue';
import GpsCapture from '../components/GpsCapture.vue';
import ChecklistForm from '../components/ChecklistForm.vue';
import RiskAssessmentForm from '../components/RiskAssessmentForm.vue';

const fleet     = useFleetStore();
const flights   = useFlightsStore();
const riskStore = useRiskItemsStore();
const router    = useRouter();

const step            = ref(1);
const launching       = ref(false);
const aborting        = ref(false);
const showAbortConfirm = ref(false);
const gps             = ref({ lat: null, lng: null });
const riskThreshold   = ref(6);

const form = reactive({
  drone_id:                   '',
  battery_id:                 '',
  battery_pct_start:          null,
  accessories:                [],
  location_description:       '',
  flight_plan:                '',
  purpose:                    'recreational',
  purpose_notes:              '',
  laanc_status:               'na',
  laanc_authorization_number: '',
});

const checklist  = ref([]);
const riskScores = ref([]);

onMounted(async () => {
  // Build checklist from the default template
  const template = fleet.defaultTemplate;
  if (template?.items) {
    checklist.value = template.items.map((item) => ({
      checklist_item_id: item.id,
      label:             item.label,
      has_comment_box:   item.has_comment_box,
      checked:           false,
      comment:           null,
    }));
  }

  // Load risk items and threshold
  await riskStore.fetchAll();
  riskThreshold.value = await riskStore.fetchThreshold();
  riskScores.value = riskStore.activeItems.map((item) => ({
    risk_item_id:     item.server_id ?? item.id,
    label:            item.label,
    description:      item.description ?? null,
    score:            0,
    mitigation_notes: null,
  }));
});

async function pasteLaanc() {
  const text = await navigator.clipboard.readText();
  const match = text.match(/\bAuthorization\s+([A-Z0-9]{10,})\b/) || text.match(/^LAANC\s+([A-Z0-9]{10,})/m);
  if (match) form.laanc_authorization_number = match[1];
}

function goToChecklist() {
  if (!form.drone_id) return;
  step.value = 2;
}

async function launchFlight() {
  launching.value = true;
  try {
    const flight = await flights.startFlight({
      ...form,
      ...gps.value,
      battery_id:  form.battery_id || null,
      drone_id:    Number(form.drone_id),
      checklist:   checklist.value.map(({ checklist_item_id, checked, comment, label }) => ({
        checklist_item_id, checked, comment, label,
      })),
      risk_scores: riskScores.value,
    });
    router.push(`/flights/${flight.id}/active`);
  } finally {
    launching.value = false;
  }
}

function confirmAbort() {
  showAbortConfirm.value = true;
}

async function doAbort() {
  aborting.value = true;
  showAbortConfirm.value = false;
  try {
    // Create the flight record first, then immediately abort it
    const flight = await flights.startFlight({
      ...form,
      ...gps.value,
      battery_id:  form.battery_id || null,
      drone_id:    Number(form.drone_id),
      checklist:   checklist.value.map(({ checklist_item_id, checked, comment, label }) => ({
        checklist_item_id, checked, comment, label,
      })),
      risk_scores: riskScores.value,
    });
    await flights.abortFlight(flight.id, { risk_scores: riskScores.value });
    router.push('/flights');
  } finally {
    aborting.value = false;
  }
}
</script>
