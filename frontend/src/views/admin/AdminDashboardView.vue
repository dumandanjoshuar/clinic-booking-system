<script setup>
import { onMounted, ref } from 'vue'
import api, { getErrorMessage } from '../../services/api'

const loading = ref(false)
const error = ref('')
const counts = ref({
  active_services: 0,
  active_doctors: 0,
  active_availability_rules: 0,
  pending_appointments: 0,
  today_appointments: 0,
})

const cards = [
  {
    key: 'pending_appointments',
    label: 'Pending appointments',
    icon: 'mdi-calendar-alert-outline',
    color: 'warning',
  },
  {
    key: 'today_appointments',
    label: 'Today appointments',
    icon: 'mdi-calendar-today-outline',
    color: 'primary',
  },
  {
    key: 'active_services',
    label: 'Active services',
    icon: 'mdi-medical-bag',
    color: 'primary',
  },
  {
    key: 'active_doctors',
    label: 'Active doctors',
    icon: 'mdi-doctor',
    color: 'secondary',
  },
  {
    key: 'active_availability_rules',
    label: 'Availability rules',
    icon: 'mdi-calendar-clock',
    color: 'success',
  },
]

async function loadDashboard() {
  loading.value = true
  error.value = ''

  try {
    const { data } = await api.get('/admin/dashboard')
    counts.value = data.counts
  } catch (err) {
    error.value = getErrorMessage(err)
  } finally {
    loading.value = false
  }
}

onMounted(loadDashboard)
</script>

<template>
  <div>
    <div class="mb-6">
      <div class="page-title">Dashboard</div>
      <div class="page-subtitle mt-1">Clinic setup and appointment activity at a glance.</div>
    </div>

    <v-alert
      v-if="error"
      type="error"
      variant="tonal"
      class="mb-5"
    >
      {{ error }}
    </v-alert>

    <v-row>
      <v-col
        v-for="card in cards"
        :key="card.key"
        cols="12"
        md="4"
        lg="3"
      >
        <v-card rounded="lg" elevation="1">
          <v-card-text class="d-flex align-center justify-space-between pa-6">
            <div>
              <div class="text-medium-emphasis text-body-2">{{ card.label }}</div>
              <div class="text-h4 font-weight-bold mt-2">
                <v-progress-circular
                  v-if="loading"
                  indeterminate
                  size="24"
                  width="3"
                />
                <span v-else>{{ counts[card.key] }}</span>
              </div>
            </div>
            <v-avatar :color="card.color" variant="tonal" rounded="lg" size="48">
              <v-icon :icon="card.icon" />
            </v-avatar>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>
  </div>
</template>
