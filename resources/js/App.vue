<template>
  <div class="min-h-screen flex flex-col">
    <OfflineBanner />

    <NavBar v-if="auth.isAuthenticated" />

    <main class="flex-1 overflow-y-auto pb-safe">
      <RouterView />
    </main>
  </div>
</template>

<script setup>
import { onMounted } from 'vue';
import { useAuthStore } from './stores/auth';
import { useSyncStore } from './stores/sync';
import { useFleetStore } from './stores/fleet';
import NavBar from './components/NavBar.vue';
import OfflineBanner from './components/OfflineBanner.vue';

const auth = useAuthStore();
const sync = useSyncStore();
const fleet = useFleetStore();

onMounted(async () => {
  if (auth.isAuthenticated) {
    await sync.init();
    // Try to load fleet from IndexedDB immediately for offline support
    if (!fleet.loaded) {
      try {
        await fleet.fetchAll();
      } catch {
        await fleet.loadFromIndexedDB();
      }
    }
  }
});
</script>
