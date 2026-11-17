<script setup>
import { reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const authStore = useAuthStore()

const form = reactive({ email: '', password: '' })
const errors = ref({})
const generalError = ref('')
const isSubmitting = ref(false)

async function handleSubmit() {
  errors.value = {}
  generalError.value = ''
  isSubmitting.value = true

  try {
    await authStore.login(form)
    router.push('/reports')
  } catch (error) {
    if (error.response) {
      errors.value = error.response.data?.errors ?? {}
    } else {
      generalError.value = 'Unable to log in. Please check your connection and try again.'
    }
  } finally {
    isSubmitting.value = false
  }
}
</script>

<template>
  <div class="page">
    <div class="card" style="max-width: 420px; margin: 0 auto">
      <h2>Log in</h2>
      <p v-if="generalError" class="field-error">{{ generalError }}</p>
      <form class="stack" style="margin-top: 1rem" @submit.prevent="handleSubmit">
        <div class="field">
          <label for="email">Email</label>
          <input id="email" v-model="form.email" type="email" required autocomplete="email" />
          <span v-if="errors.email" class="field-error">{{ errors.email[0] }}</span>
        </div>

        <div class="field">
          <label for="password">Password</label>
          <input
            id="password"
            v-model="form.password"
            type="password"
            required
            autocomplete="current-password"
          />
          <span v-if="errors.password" class="field-error">{{ errors.password[0] }}</span>
        </div>

        <button type="submit" class="btn btn-primary" :disabled="isSubmitting">
          {{ isSubmitting ? 'Logging in...' : 'Log in' }}
        </button>
      </form>
    </div>
  </div>
</template>
