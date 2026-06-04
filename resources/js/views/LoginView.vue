<template>
  <div class="min-h-screen flex items-center justify-center px-4">
    <div class="w-full max-w-sm">
      <div class="text-center mb-8">
        <h1 class="text-3xl font-bold text-blue-400">DroneLog</h1>
        <p class="text-slate-400 mt-1 text-sm">Part 107 flight logging</p>
      </div>

      <form class="space-y-4" @submit.prevent="handleLogin">
        <div>
          <label class="label">Email</label>
          <input v-model="form.email" type="email" autocomplete="email"
            class="input-field" required />
        </div>

        <div>
          <label class="label">Password</label>
          <input v-model="form.password" type="password" autocomplete="current-password"
            class="input-field" required />
        </div>

        <p v-if="error" class="text-red-400 text-sm text-center">{{ error }}</p>

        <button type="submit" class="btn-primary" :disabled="loading">
          {{ loading ? 'Signing in…' : 'Sign In' }}
        </button>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../stores/auth';

const auth = useAuthStore();
const router = useRouter();

const form = ref({ email: '', password: '' });
const loading = ref(false);
const error = ref('');

async function handleLogin() {
  loading.value = true;
  error.value = '';
  try {
    await auth.login(form.value.email, form.value.password);
    router.push('/');
  } catch (e) {
    error.value = e.response?.data?.message ?? 'Login failed. Check your credentials.';
  } finally {
    loading.value = false;
  }
}
</script>
