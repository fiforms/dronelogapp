<template>
  <nav class="bg-slate-800 border-b border-slate-700 safe-top">
    <div class="max-w-2xl mx-auto px-4 py-3 flex items-center justify-between">
      <RouterLink to="/" class="flex items-center gap-2 text-lg font-bold text-blue-400">
        <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
          <path d="M12 2C6.5 2 2 6.5 2 12s4.5 10 10 10 10-4.5 10-10S17.5 2 12 2zm0 18c-4.4 0-8-3.6-8-8s3.6-8 8-8 8 3.6 8 8-3.6 8-8 8zm-1-11h2v3h3v2h-3v3h-2v-3H8v-2h3V9z"/>
        </svg>
        DroneLog
      </RouterLink>

      <div class="flex items-center gap-3">
        <SyncStatusBadge />

        <button
          class="text-slate-400 hover:text-slate-100 transition-colors"
          @click="menuOpen = !menuOpen"
          aria-label="Menu"
        >
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
          </svg>
        </button>
      </div>
    </div>

    <!-- Dropdown menu -->
    <Transition name="slide-down">
      <div v-if="menuOpen" class="bg-slate-800 border-t border-slate-700 px-4 pb-4">
        <nav class="grid grid-cols-2 gap-2 pt-3">
          <RouterLink v-for="item in navItems" :key="item.to" :to="item.to"
            class="flex items-center gap-2 px-3 py-2 rounded-lg text-sm text-slate-300
                   hover:bg-slate-700 hover:text-white transition-colors"
            @click="menuOpen = false">
            {{ item.label }}
          </RouterLink>
        </nav>
        <div class="mt-3 pt-3 border-t border-slate-700">
          <button class="text-sm text-slate-400 hover:text-red-400 transition-colors"
            @click="handleLogout">Sign out</button>
        </div>
      </div>
    </Transition>
  </nav>
</template>

<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../stores/auth';
import SyncStatusBadge from './SyncStatusBadge.vue';

const auth = useAuthStore();
const router = useRouter();
const menuOpen = ref(false);

const navItems = [
  { to: '/', label: '🏠 Dashboard' },
  { to: '/flights', label: '📋 Flight Log' },
  { to: '/drones', label: '🚁 Drones' },
  { to: '/batteries', label: '🔋 Batteries' },
  { to: '/accessories', label: '🔧 Accessories' },
  { to: '/checklists', label: '✅ Checklists' },
];

async function handleLogout() {
  await auth.logout();
  router.push('/login');
}
</script>

<style scoped>
.slide-down-enter-active,
.slide-down-leave-active { transition: all 0.2s ease; }
.slide-down-enter-from,
.slide-down-leave-to { opacity: 0; transform: translateY(-8px); }
</style>
