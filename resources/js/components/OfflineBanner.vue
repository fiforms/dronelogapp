<template>
  <Transition name="slide-down">
    <div v-if="!sync.online"
         class="bg-amber-600 text-white text-sm text-center py-2 px-4 font-medium">
      You're offline — flights you log will sync when you reconnect.
    </div>
  </Transition>
</template>

<script setup>
import { useSyncStore } from '../stores/sync';
const sync = useSyncStore();
</script>

<style scoped>
.slide-down-enter-active,
.slide-down-leave-active { transition: all 0.25s ease; }
.slide-down-enter-from,
.slide-down-leave-to { opacity: 0; max-height: 0; }
.slide-down-enter-to,
.slide-down-leave-from { max-height: 4rem; }
</style>
