<script setup>
import { onMounted, reactive, ref } from 'vue'
import api, { getErrorMessage } from '../../services/api'

const appointments = ref([])
const loading = ref(false)
const snackbar = reactive({
  show: false,
  text: '',
  color: 'success',
})

const filters = reactive({
  date: '',
  status: 'confirmed',
})

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

async function loadAppointments() {
  loading.value = true

  try {
    const { data } = await api.get('/doctor/appointments', {
      params: {
        date: filters.date || undefined,
        status: filters.status || undefined,
      },
    })
    appointments.value = data.data
  } catch (err) {
    notify(getErrorMessage(err), 'error')
  } finally {
    loading.value = false
  }
}

function clearFilters() {
  filters.date = ''
  filters.status = 'confirmed'
  loadAppointments()
}

onMounted(loadAppointments)
</script>

<template>
  <div>
    <div class="d-flex flex-wrap align-center justify-space-between ga-4 mb-6">
      <div>
        <div class="page-title">My Schedule</div>
        <div class="page-subtitle mt-1">View appointments assigned to you.</div>
      </div>
      <v-btn color="primary" prepend-icon="mdi-refresh" :loading="loading" @click="loadAppointments">
        Refresh
      </v-btn>
    </div>

    <v-card rounded="lg" elevation="0" class="panel-card mb-5">
      <v-card-text>
        <v-row align="center">
          <v-col cols="12" md="5">
            <v-text-field v-model="filters.date" label="Date" type="date" variant="outlined" density="comfortable" hide-details />
          </v-col>
          <v-col cols="12" md="5">
            <v-select v-model="filters.status" :items="statuses" label="Status" variant="outlined" density="comfortable" clearable hide-details />
          </v-col>
          <v-col cols="12" md="2" class="d-flex ga-2 justify-end">
            <v-btn variant="text" @click="clearFilters">Clear</v-btn>
            <v-btn color="primary" @click="loadAppointments">Apply</v-btn>
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
          <tr v-if="loading">
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
