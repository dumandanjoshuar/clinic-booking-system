<script setup>
import { onMounted, onUnmounted, reactive, ref, watch } from 'vue'
import api, { getErrorMessage } from '../../services/api'

const loading = ref(false)
const loadingSchedule = ref(false)
const error = ref('')
const doctor = ref(null)
const appointments = ref([])
const lastUpdated = ref('')
let refreshTimer = null

const counts = ref({
  today_confirmed: 0,
  upcoming_confirmed: 0,
  completed_total: 0,
})
const snackbar = reactive({
  show: false,
  text: '',
  color: 'success',
})
const filters = reactive({
  date: '',
  status: 'confirmed',
})

const cards = [
  { key: 'today_confirmed', label: 'Today confirmed', icon: 'mdi-calendar-today-outline', color: 'primary' },
  { key: 'upcoming_confirmed', label: 'Upcoming confirmed', icon: 'mdi-calendar-clock', color: 'secondary' },
  { key: 'completed_total', label: 'Completed visits', icon: 'mdi-check-circle-outline', color: 'success' },
]
const statuses = [
  { title: 'Confirmed', value: 'confirmed' },
  { title: 'Completed', value: 'completed' },
  { title: 'Pending', value: 'pending' },
  { title: 'Cancelled', value: 'cancelled' },
  { title: 'Rejected', value: 'rejected' },
  { title: 'No-show', value: 'no-show' },
]

function notify(text, color = 'success') {
  snackbar.text = text
  snackbar.color = color
  snackbar.show = true
}

function statusColor(status) {
  return {
    pending: 'warning',
    confirmed: 'primary',
    completed: 'success',
    cancelled: 'default',
    rejected: 'error',
    'no-show': 'error',
  }[status] || 'default'
}

function clearFilters() {
  filters.date = ''
  filters.status = 'confirmed'
}

function updateTimestamp() {
  lastUpdated.value = new Intl.DateTimeFormat('en-US', {
    hour: 'numeric',
    minute: '2-digit',
  }).format(new Date())
}

async function loadDashboard({ silent = false } = {}) {
  if (!silent) {
    loading.value = true
  }
  error.value = ''

  try {
    const { data } = await api.get('/doctor/dashboard')
    doctor.value = data.doctor
    counts.value = data.counts
  } catch (err) {
    error.value = getErrorMessage(err)
  } finally {
    if (!silent) {
      loading.value = false
    }
  }
}

async function loadSchedule({ silent = false } = {}) {
  if (!silent) {
    loadingSchedule.value = true
  }

  try {
    const { data } = await api.get('/doctor/appointments', {
      params: {
        date: filters.date || undefined,
        status: filters.status || undefined,
      },
    })
    appointments.value = data.data
    updateTimestamp()
  } catch (err) {
    notify(getErrorMessage(err), 'error')
  } finally {
    if (!silent) {
      loadingSchedule.value = false
    }
  }
}

async function refreshWorkspace(options = {}) {
  await Promise.all([loadDashboard(options), loadSchedule(options)])
}

watch(
  () => [filters.date, filters.status],
  loadSchedule,
)

onMounted(async () => {
  await refreshWorkspace()
  refreshTimer = window.setInterval(() => refreshWorkspace({ silent: true }), 60000)
})

onUnmounted(() => {
  if (refreshTimer) {
    window.clearInterval(refreshTimer)
  }
})
</script>

<template>
  <div>
    <div class="d-flex flex-wrap align-center justify-space-between ga-4 mb-6">
      <div>
        <div class="page-title">Doctor Dashboard</div>
        <div class="page-subtitle mt-1">{{ doctor?.full_name || 'Your assigned appointment summary' }}</div>
      </div>
      <div class="text-caption text-medium-emphasis">
        <span v-if="lastUpdated">Updated {{ lastUpdated }}</span>
        <span v-else>Auto-refresh enabled</span>
      </div>
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

    <v-card rounded="lg" elevation="0" class="panel-card mt-6 mb-5">
      <v-card-text>
        <div class="d-flex flex-wrap align-center justify-space-between ga-4 mb-4">
          <div>
            <div class="text-h6 font-weight-bold">My Schedule</div>
            <div class="text-body-2 text-medium-emphasis">Assigned appointments update when filters change and every minute.</div>
          </div>
          <v-btn variant="text" color="primary" prepend-icon="mdi-filter-remove-outline" @click="clearFilters">
            Clear filters
          </v-btn>
        </div>

        <v-row align="center">
          <v-col cols="12" md="6">
            <v-text-field
              v-model="filters.date"
              label="Date"
              type="date"
              variant="outlined"
              density="comfortable"
              hide-details
            />
          </v-col>
          <v-col cols="12" md="6">
            <v-select
              v-model="filters.status"
              :items="statuses"
              label="Status"
              variant="outlined"
              density="comfortable"
              clearable
              hide-details
            />
          </v-col>
        </v-row>
      </v-card-text>
    </v-card>

    <v-card rounded="lg" elevation="0" class="panel-card">
      <v-table>
        <thead>
          <tr>
            <th>Patient</th>
            <th>Schedule</th>
            <th>Service</th>
            <th>Status</th>
            <th class="text-right">Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="loadingSchedule">
            <td colspan="5" class="table-empty">
              <v-progress-circular indeterminate size="28" width="3" />
            </td>
          </tr>
          <tr v-else-if="appointments.length === 0">
            <td colspan="5" class="table-empty">No appointments found.</td>
          </tr>
          <tr v-for="appointment in appointments" v-else :key="appointment.id">
            <td>
              <div class="font-weight-medium">{{ appointment.patient?.full_name }}</div>
              <div class="text-caption text-medium-emphasis">{{ appointment.patient?.phone }}</div>
            </td>
            <td>
              <div>{{ appointment.appointment_date }}</div>
              <div class="text-caption text-medium-emphasis">{{ appointment.start_time }} to {{ appointment.end_time }}</div>
            </td>
            <td>{{ appointment.service?.name }}</td>
            <td>
              <v-chip :color="statusColor(appointment.status)" size="small" variant="tonal">
                {{ appointment.status }}
              </v-chip>
            </td>
            <td class="text-right">
              <v-btn :to="`/doctor/appointments/${appointment.id}`" icon="mdi-eye-outline" variant="text" size="small" />
            </td>
          </tr>
        </tbody>
      </v-table>
    </v-card>

    <v-snackbar v-model="snackbar.show" :color="snackbar.color" timeout="3000">
      {{ snackbar.text }}
    </v-snackbar>
  </div>
</template>
