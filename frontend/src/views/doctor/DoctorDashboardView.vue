<script setup>
import { onMounted, ref } from 'vue'
import api, { getErrorMessage } from '../../services/api'

const loading = ref(false)
const error = ref('')
const doctor = ref(null)
const counts = ref({
  today_confirmed: 0,
  upcoming_confirmed: 0,
  completed_total: 0,
})

const cards = [
  { key: 'today_confirmed', label: 'Today confirmed', icon: 'mdi-calendar-today-outline', color: 'primary' },
  { key: 'upcoming_confirmed', label: 'Upcoming confirmed', icon: 'mdi-calendar-clock', color: 'secondary' },
  { key: 'completed_total', label: 'Completed visits', icon: 'mdi-check-circle-outline', color: 'success' },
]

async function loadDashboard() {
  loading.value = true
  error.value = ''

  try {
    const { data } = await api.get('/doctor/dashboard')
    doctor.value = data.doctor
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
      <div class="page-title">Doctor Dashboard</div>
      <div class="page-subtitle mt-1">{{ doctor?.full_name || 'Your assigned appointment summary' }}</div>
    </div>

    <v-alert v-if="error" type="error" variant="tonal" class="mb-5">{{ error }}</v-alert>

    <v-row>
      <v-col v-for="card in cards" :key="card.key" cols="12" md="4">
        <v-card rounded="lg" elevation="0" class="metric-card">
          <v-card-text class="d-flex align-center justify-space-between pa-6">
            <div>
              <div class="text-medium-emphasis text-body-2">{{ card.label }}</div>
              <div class="text-h4 font-weight-bold mt-2">
                <v-progress-circular v-if="loading" indeterminate size="24" width="3" />
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
