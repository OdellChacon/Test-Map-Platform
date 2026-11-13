<script setup>
import { reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const authStore = useAuthStore()

const form = reactive({ name: '', email: '', password: '', password_confirmation: '' })
const errors = ref({})
const isSubmitting = ref(false)

async function handleSubmit() {
  errors.value = {}
  isSubmitting.value = true

  try {
    await authStore.register(form)
    router.push('/reports')
  } catch (error) {
    errors.value = error.response?.data?.errors ?? { name: ['Unable to register.'] }
  } finally {
    isSubmitting.value = false
  }
}
</script>

<template>
  <div class="page">
    <div class="card" style="max-width: 420px; margin: 0 auto">
      <h2>Create an account</h2>
      <form class="stack" style="margin-top: 1rem" @submit.prevent="handleSubmit">
        <div class="field">
          <label for="name">Name</label>
          <input id="name" v-model="form.name" type="text" required autocomplete="name" />
          <span v-if="errors.name" class="field-error">{{ errors.name[0] }}</span>
        </div>

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
            autocomplete="new-password"
          />
          <span v-if="errors.password" class="field-error">{{ errors.password[0] }}</span>
        </div>

        <div class="field">
          <label for="password_confirmation">Confirm password</label>
          <input
            id="password_confirmation"
            v-model="form.password_confirmation"
            type="password"
            required
            autocomplete="new-password"
          />
        </div>

        <button type="submit" class="btn btn-primary" :disabled="isSubmitting">
          {{ isSubmitting ? 'Creating account...' : 'Create account' }}
        </button>
      </form>
    </div>
  </div>
</template>
