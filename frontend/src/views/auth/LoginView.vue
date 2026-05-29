<script setup>
import { reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuth } from '../../composables/useAuth'
import { getErrorMessage } from '../../services/api'

const route = useRoute()
const router = useRouter()
const { state, login } = useAuth()

const form = reactive({
  email: '',
  password: '',
})

const error = ref('')

async function submit() {
  error.value = ''

  try {
    const user = await login(form)

    const fallback = user.role === 'doctor' ? '/doctor/dashboard' : '/admin/dashboard'
    const requested = typeof route.query.redirect === 'string' ? route.query.redirect : ''
    const canUseRedirect = user.role === 'doctor'
      ? requested.startsWith('/doctor')
      : requested.startsWith('/admin')

    await router.push(canUseRedirect ? requested : fallback)
  } catch (err) {
    error.value = getErrorMessage(err)
  }
}
</script>

<template>
  <v-main class="bg-background">
    <v-container class="d-flex align-center justify-center min-h-screen py-12">
      <v-card width="440" rounded="lg" elevation="2">
        <v-card-text class="pa-8">
          <div class="mb-8">
            <div class="page-title">Clinic Admin</div>
            <div class="page-subtitle mt-2">Sign in to manage services, doctors, and availability.</div>
          </div>

          <v-alert
            v-if="error"
            type="error"
            variant="tonal"
            class="mb-5"
          >
            {{ error }}
          </v-alert>

          <v-form @submit.prevent="submit">
            <v-text-field
              v-model="form.email"
              label="Email"
              type="email"
              autocomplete="email"
              prepend-inner-icon="mdi-email-outline"
              variant="outlined"
              density="comfortable"
              required
            />

            <v-text-field
              v-model="form.password"
              label="Password"
              type="password"
              autocomplete="current-password"
              prepend-inner-icon="mdi-lock-outline"
              variant="outlined"
              density="comfortable"
              required
            />

            <v-btn
              type="submit"
              color="primary"
              size="large"
              block
              :loading="state.loading"
            >
              Sign in
            </v-btn>
          </v-form>
        </v-card-text>
      </v-card>
    </v-container>
  </v-main>
</template>
