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
import { watch } from 'vue';
import { useAuthStore } from './stores/auth';
import { useSyncStore } from './stores/sync';
import { useFleetStore } from './stores/fleet';
import NavBar from './components/NavBar.vue';
import OfflineBanner from './components/OfflineBanner.vue';

const auth = useAuthStore();
const sync = useSyncStore();
const fleet = useFleetStore();

watch(() => auth.user, async (user) => {
  if (user && !fleet.loaded) {
    await sync.init();
    try {
      await fleet.fetchAll();
    } catch {
      await fleet.loadFromIndexedDB();
    }
  }
}, { immediate: true });
</script>
