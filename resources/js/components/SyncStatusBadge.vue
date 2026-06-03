<template>
  <button class="flex items-center gap-1.5 text-xs transition-colors" @click="sync.syncNow()" :disabled="sync.syncing">
    <span :class="sync.online ? 'bg-emerald-400' : 'bg-amber-400'" class="w-2 h-2 rounded-full"></span>
    <span :class="sync.online ? 'text-emerald-400' : 'text-amber-400'">
      <template v-if="sync.syncing">Syncing…</template>
      <template v-else-if="!sync.online">Offline</template>
      <template v-else-if="sync.pendingCount > 0">{{ sync.pendingCount }} pending</template>
      <template v-else>Synced</template>
    </span>
  </button>
</template>

<script setup>
import { useSyncStore } from '../stores/sync';
const sync = useSyncStore();
</script>
