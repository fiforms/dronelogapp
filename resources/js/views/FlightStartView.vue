<template>
  <div class="max-w-2xl mx-auto px-4 py-6">
    <!-- Step indicator -->
    <div class="flex items-center gap-3 mb-6">
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
        <input v-if="form.laanc_status === 'received'" v-model="form.laanc_authorization_number"
          type="text" class="input-field mt-2" placeholder="Authorization number" />
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

      <div class="flex gap-3 pt-2">
        <button class="btn-secondary" style="width:auto;flex:0 0 auto;padding-left:1.5rem;padding-right:1.5rem"
          @click="step = 1">← Back</button>
        <button class="btn-primary" @click="launchFlight" :disabled="launching">
          {{ launching ? 'Starting…' : '🚀 Launch Flight' }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useFleetStore } from '../stores/fleet';
import { useFlightsStore } from '../stores/flights';
import AccessoryPicker from '../components/AccessoryPicker.vue';
import GpsCapture from '../components/GpsCapture.vue';
import ChecklistForm from '../components/ChecklistForm.vue';

const fleet = useFleetStore();
const flights = useFlightsStore();
const router = useRouter();

const step = ref(1);
const launching = ref(false);
const gps = ref({ lat: null, lng: null });

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

// Build checklist from the default template
const checklist = ref([]);

onMounted(() => {
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
});

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
      battery_id: form.battery_id || null,
      drone_id:   Number(form.drone_id),
      checklist:  checklist.value.map(({ checklist_item_id, checked, comment }) => ({
        checklist_item_id, checked, comment,
      })),
    });
    router.push(`/flights/${flight.id}/active`);
  } finally {
    launching.value = false;
  }
}
</script>
