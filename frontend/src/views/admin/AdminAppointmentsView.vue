<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import api, { getErrorMessage } from '../../services/api'

const appointments = ref([])
const doctors = ref([])
const services = ref([])
const loading = ref(false)
const snackbar = reactive({
  show: false,
  text: '',
  color: 'success',
})

const filters = reactive({
  date: '',
  status: null,
  doctor_id: null,
  service_id: null,
})

const statuses = [
  { title: 'Pending', value: 'pending' },
  { title: 'Confirmed', value: 'confirmed' },
  { title: 'Completed', value: 'completed' },
  { title: 'Cancelled', value: 'cancelled' },
  { title: 'Rejected', value: 'rejected' },
  { title: 'No-show', value: 'no-show' },
]

const doctorItems = computed(() => doctors.value.map((doctor) => ({
  title: doctor.full_name,
  value: doctor.id,
})))

const serviceItems = computed(() => services.value.map((service) => ({
  title: service.name,
  value: service.id,
})))

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

async function loadCatalog() {
  const [doctorsResponse, servicesResponse] = await Promise.all([
    api.get('/admin/doctors'),
    api.get('/admin/services'),
  ])
  doctors.value = doctorsResponse.data.data
  services.value = servicesResponse.data.data
}

async function loadAppointments() {
  loading.value = true

  try {
    const { data } = await api.get('/admin/appointments', {
      params: {
        date: filters.date || undefined,
        status: filters.status || undefined,
        doctor_id: filters.doctor_id || undefined,
        service_id: filters.service_id || undefined,
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
  filters.status = null
  filters.doctor_id = null
  filters.service_id = null
  loadAppointments()
}

onMounted(async () => {
  try {
    await loadCatalog()
    await loadAppointments()
  } catch (err) {
    notify(getErrorMessage(err), 'error')
  }
})
</script>

<template>
  <div>
    <div class="d-flex flex-wrap align-center justify-space-between ga-4 mb-6">
      <div>
        <div class="page-title">Appointments</div>
        <div class="page-subtitle mt-1">Review requests and manage clinic appointment status.</div>
      </div>
      <v-btn color="primary" prepend-icon="mdi-refresh" :loading="loading" @click="loadAppointments">
        Refresh
      </v-btn>
    </div>

    <v-card rounded="lg" elevation="1" class="mb-5">
      <v-card-text>
        <v-row align="center">
          <v-col cols="12" md="3">
            <v-text-field v-model="filters.date" label="Date" type="date" variant="outlined" density="comfortable" hide-details />
          </v-col>
          <v-col cols="12" md="3">
            <v-select v-model="filters.status" :items="statuses" label="Status" variant="outlined" density="comfortable" clearable hide-details />
          </v-col>
          <v-col cols="12" md="3">
            <v-select v-model="filters.doctor_id" :items="doctorItems" label="Doctor" variant="outlined" density="comfortable" clearable hide-details />
          </v-col>
          <v-col cols="12" md="3">
            <v-select v-model="filters.service_id" :items="serviceItems" label="Service" variant="outlined" density="comfortable" clearable hide-details />
          </v-col>
        </v-row>
        <div class="d-flex justify-end ga-2 mt-4">
          <v-btn variant="text" @click="clearFilters">Clear</v-btn>
          <v-btn color="primary" @click="loadAppointments">Apply filters</v-btn>
        </div>
      </v-card-text>
    </v-card>

    <v-card rounded="lg" elevation="1">
      <v-table>
        <thead>
          <tr>
            <th>Patient</th>
            <th>Schedule</th>
            <th>Doctor</th>
            <th>Service</th>
            <th>Status</th>
            <th class="text-right">Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="loading">
            <td colspan="6" class="table-empty">
              <v-progress-circular indeterminate size="28" width="3" />
            </td>
          </tr>
          <tr v-else-if="appointments.length === 0">
            <td colspan="6" class="table-empty">No appointments found.</td>
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
            <td>{{ appointment.doctor?.full_name }}</td>
            <td>{{ appointment.service?.name }}</td>
            <td>
              <v-chip :color="statusColor(appointment.status)" size="small" variant="tonal">
                {{ appointment.status }}
              </v-chip>
            </td>
            <td class="text-right">
              <v-btn
                :to="`/admin/appointments/${appointment.id}`"
                icon="mdi-eye-outline"
                variant="text"
                size="small"
              />
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
