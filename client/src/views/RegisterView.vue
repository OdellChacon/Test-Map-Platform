<script setup>
import { computed, reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const authStore = useAuthStore()

const form = reactive({ name: '', email: '', password: '', password_confirmation: '' })
const errors = ref({})
const generalError = ref('')
const isSubmitting = ref(false)

// Laravel's `confirmed` rule reports a mismatched confirmation under the
// `password` key. Split that out so it renders next to `password_confirmation`
// instead of only under `password`, and avoid showing the exact same text
// under both fields when `password` has more than one error.
const confirmationError = computed(() => {
  const list = errors.value.password
  if (!list || list.length === 0) return null
  return list.find((message) => message.toLowerCase().includes('confirm')) ?? null
})

const passwordError = computed(() => {
  const list = errors.value.password
  if (!list || list.length === 0) return null
  return list.find((message) => message !== confirmationError.value) ?? null
})

async function handleSubmit() {
  errors.value = {}
  generalError.value = ''
  isSubmitting.value = true

  try {
    await authStore.register(form)
    router.push('/reports')
  } catch (error) {
    if (error.response) {
      errors.value = error.response.data?.errors ?? {}
    } else {
      generalError.value = 'Unable to register. Please check your connection and try again.'
    }
  } finally {
    isSubmitting.value = false
  }
}
</script>

<template>
  <div class="page">
    <div class="card" style="max-width: 420px; margin: 0 auto">
      <h2>Create an account</h2>
      <p v-if="generalError" class="field-error">{{ generalError }}</p>
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
            minlength="8"
            autocomplete="new-password"
          />
          <span v-if="passwordError" class="field-error">{{ passwordError }}</span>
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
          <span v-if="confirmationError" class="field-error">{{ confirmationError }}</span>
        </div>

        <button type="submit" class="btn btn-primary" :disabled="isSubmitting">
          {{ isSubmitting ? 'Creating account...' : 'Create account' }}
        </button>
      </form>
    </div>
  </div>
</template>
