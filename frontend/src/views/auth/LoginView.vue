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
    if (user.must_change_password) {
      await router.push('/change-password')
      return
    }

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
  <v-main class="login-surface">
    <v-container class="d-flex align-center justify-center min-h-screen py-12">
      <v-card width="480" rounded="lg" elevation="0" class="login-card">
        <v-card-text class="pa-8">
          <div class="mb-8">
            <div class="d-flex align-center ga-3 mb-5">
              <div class="brand-mark">
                <v-icon icon="mdi-hospital-building" />
              </div>
              <div>
                <div class="font-weight-bold">Clinic Booking</div>
                <div class="text-caption text-medium-emphasis">Secure staff access</div>
              </div>
            </div>
            <div class="page-title">Staff Sign In</div>
            <div class="page-subtitle mt-2">
              Admin staff and doctors use this secure portal to access their workspace.
            </div>
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
