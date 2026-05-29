<script setup>
import { reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuth } from '../../composables/useAuth'
import { getErrorMessage } from '../../services/api'

const router = useRouter()
const { state, changePassword } = useAuth()
const error = ref('')

const form = reactive({
  current_password: '',
  password: '',
  password_confirmation: '',
})

async function submit() {
  error.value = ''

  try {
    const user = await changePassword(form)
    await router.push(user.role === 'doctor' ? '/doctor/dashboard' : '/admin/dashboard')
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
          <div class="mb-7">
            <div class="d-flex align-center ga-3 mb-5">
              <div class="brand-mark">
                <v-icon icon="mdi-lock-reset" />
              </div>
              <div>
                <div class="font-weight-bold">Secure your account</div>
                <div class="text-caption text-medium-emphasis">{{ state.user?.email }}</div>
              </div>
            </div>
            <div class="page-title">Change Temporary Password</div>
            <div class="page-subtitle mt-2">
              Create a private password before continuing to your workspace.
            </div>
          </div>

          <v-alert v-if="error" type="error" variant="tonal" class="mb-5">
            {{ error }}
          </v-alert>

          <v-form @submit.prevent="submit">
            <v-text-field
              v-model="form.current_password"
              label="Temporary password"
              type="password"
              autocomplete="current-password"
              prepend-inner-icon="mdi-lock-outline"
              variant="outlined"
              density="comfortable"
            />
            <v-text-field
              v-model="form.password"
              label="New password"
              type="password"
              autocomplete="new-password"
              prepend-inner-icon="mdi-lock-plus-outline"
              variant="outlined"
              density="comfortable"
            />
            <v-text-field
              v-model="form.password_confirmation"
              label="Confirm new password"
              type="password"
              autocomplete="new-password"
              prepend-inner-icon="mdi-lock-check-outline"
              variant="outlined"
              density="comfortable"
            />
            <v-btn type="submit" color="primary" size="large" block :loading="state.loading">
              Update password
            </v-btn>
          </v-form>
        </v-card-text>
      </v-card>
    </v-container>
  </v-main>
</template>
