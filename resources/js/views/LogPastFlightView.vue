<template>
  <div class="max-w-2xl mx-auto px-4 py-6">
    <div class="flex items-center gap-3 mb-6">
      <RouterLink to="/" class="text-slate-400 hover:text-slate-200">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
        </svg>
      </RouterLink>
      <h1 class="text-xl font-bold">Log a Past Flight</h1>
    </div>

    <form class="space-y-5" @submit.prevent="save">

      <!-- Date & Time -->
      <div class="grid grid-cols-2 gap-3">
        <div>
          <label class="label">Flight Date <span class="text-red-400">*</span></label>
          <input v-model="form.flight_date" type="date" class="input-field" required :max="today" />
        </div>
        <div>
          <label class="label">Start Time <span class="text-red-400">*</span></label>
          <input v-model="form.flight_time" type="time" class="input-field" required />
        </div>
      </div>

      <!-- Duration -->
      <div>
        <label class="label">Duration (minutes)</label>
        <input v-model.number="form.duration_minutes" type="number" class="input-field"
          placeholder="e.g. 22" min="1" max="1440" />
        <p v-if="endedAt" class="text-xs text-slate-500 mt-1">Landed at {{ endedAt }}</p>
      </div>

      <!-- Drone -->
      <div>
        <label class="label">Drone</label>
        <select v-model="form.drone_id" class="input-field">
          <option value="">— Unknown / Not recorded —</option>
          <option v-for="d in fleet.activeDrones" :key="d.id" :value="d.id">{{ d.name }} ({{ d.model }})</option>
        </select>
        <RouterLink to="/drones" class="text-xs text-blue-400 mt-1 inline-block">+ Add a drone</RouterLink>
      </div>

      <!-- Battery -->
      <div>
        <label class="label">Battery</label>
        <select v-model="form.battery_id" class="input-field">
          <option value="">— None / Unknown —</option>
          <option v-for="b in fleet.activeBatteries" :key="b.id" :value="b.id">{{ b.name }}</option>
        </select>
      </div>

      <!-- Battery % -->
      <div class="grid grid-cols-2 gap-3">
        <div>
          <label class="label">Battery % at Start</label>
          <input v-model.number="form.battery_pct_start" type="number" class="input-field"
            placeholder="e.g. 100" min="0" max="100" />
        </div>
        <div>
          <label class="label">Battery % at End</label>
          <input v-model.number="form.battery_pct_end" type="number" class="input-field"
            placeholder="e.g. 45" min="0" max="100" />
        </div>
      </div>

      <!-- Accessories -->
      <div>
        <label class="label">Accessories</label>
        <AccessoryPicker v-model="form.accessories" />
      </div>

      <!-- Location coordinates -->
      <div>
        <label class="label">Launch Coordinates (GPS)</label>
        <div class="flex gap-2 items-center mb-2">
          <input v-model.number="form.lat" type="number" step="any" class="input-field"
            placeholder="Latitude (e.g. 36.17453)" />
          <input v-model.number="form.lng" type="number" step="any" class="input-field"
            placeholder="Longitude (e.g. -86.76826)" />
        </div>
        <button type="button" class="text-blue-400 hover:text-blue-300 text-xs flex items-center gap-1"
          :disabled="gpsLoading" @click="captureGps">
          <svg v-if="gpsLoading" class="w-3 h-3 animate-spin" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
          </svg>
          <span>{{ gpsLoading ? 'Getting GPS…' : '📍 Use current location' }}</span>
        </button>
        <p v-if="gpsError" class="text-xs text-red-400 mt-1">{{ gpsError }}</p>
      </div>

      <!-- Location description -->
      <div>
        <label class="label">Location Description</label>
        <input v-model="form.location_description" type="text" class="input-field"
          placeholder="e.g. Park at 5th and Main, northwest corner" />
      </div>

      <!-- Flight plan -->
      <div>
        <label class="label">Flight Plan / Intent</label>
        <textarea v-model="form.flight_plan" rows="3" class="input-field"
          placeholder="Describe the flight path and mission…" />
      </div>

      <!-- Purpose -->
      <div>
        <label class="label">Flight Purpose <span class="text-red-400">*</span></label>
        <select v-model="form.purpose" class="input-field" required>
          <option value="recreational">Recreational / Hobby</option>
          <option value="commercial">Commercial / Business</option>
        </select>
        <textarea v-if="form.purpose === 'commercial'" v-model="form.purpose_notes" rows="2"
          class="input-field mt-2" placeholder="Brief description of commercial use…" />
      </div>

      <!-- LAANC -->
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

      <!-- Post-flight notes -->
      <div>
        <label class="label">Notes</label>
        <textarea v-model="form.post_flight_notes" rows="3" class="input-field"
          placeholder="Observations, incidents, or anything worth recording…" />
      </div>

      <div class="flex gap-3 pt-2">
        <RouterLink to="/" class="btn-secondary" style="width:auto;flex:0 0 auto;padding-left:1.5rem;padding-right:1.5rem">
          Cancel
        </RouterLink>
        <button type="submit" class="btn-primary" :disabled="saving">
          {{ saving ? 'Saving…' : 'Save Flight Record' }}
        </button>
      </div>
    </form>
  </div>
</template>

<script setup>
import { ref, reactive, computed } from 'vue';
import { useRouter } from 'vue-router';
import { useFleetStore } from '../stores/fleet';
import { useFlightsStore } from '../stores/flights';
import AccessoryPicker from '../components/AccessoryPicker.vue';

const fleet = useFleetStore();
const flights = useFlightsStore();
const router = useRouter();

const saving = ref(false);
const gpsLoading = ref(false);
const gpsError = ref('');

const todayDate = new Date();
const today = todayDate.toISOString().slice(0, 10);
const defaultTime = `${String(todayDate.getHours()).padStart(2, '0')}:${String(todayDate.getMinutes()).padStart(2, '0')}`;

const form = reactive({
  flight_date:                today,
  flight_time:                defaultTime,
  duration_minutes:           null,
  drone_id:                   '',
  battery_id:                 '',
  battery_pct_start:          null,
  battery_pct_end:            null,
  accessories:                [],
  lat:                        null,
  lng:                        null,
  location_description:       '',
  flight_plan:                '',
  purpose:                    'recreational',
  purpose_notes:              '',
  laanc_status:               'na',
  laanc_authorization_number: '',
  post_flight_notes:          '',
});

const endedAt = computed(() => {
  if (!form.flight_date || !form.flight_time || !form.duration_minutes) return null;
  const start = new Date(`${form.flight_date}T${form.flight_time}`);
  const end = new Date(start.getTime() + form.duration_minutes * 60000);
  return end.toLocaleTimeString(undefined, { hour: '2-digit', minute: '2-digit' });
});

function captureGps() {
  if (!navigator.geolocation) {
    gpsError.value = 'GPS not available on this device';
    return;
  }
  gpsLoading.value = true;
  gpsError.value = '';
  navigator.geolocation.getCurrentPosition(
    (pos) => {
      form.lat = pos.coords.latitude;
      form.lng = pos.coords.longitude;
      gpsLoading.value = false;
    },
    (err) => {
      gpsError.value = err.message;
      gpsLoading.value = false;
    },
    { enableHighAccuracy: true, timeout: 10000 }
  );
}

async function save() {
  saving.value = true;
  try {
    const flight = await flights.logPastFlight({
      ...form,
      drone_id:   form.drone_id   ? Number(form.drone_id)   : null,
      battery_id: form.battery_id ? Number(form.battery_id) : null,
      lat:        form.lat  !== null && form.lat  !== '' ? Number(form.lat)  : null,
      lng:        form.lng  !== null && form.lng  !== '' ? Number(form.lng)  : null,
    });
    router.push(`/flights/${flight.id}`);
  } finally {
    saving.value = false;
  }
}
</script>
