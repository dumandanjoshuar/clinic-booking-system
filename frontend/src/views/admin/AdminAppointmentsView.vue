<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api, { getErrorMessage } from '../../services/api'

const route = useRoute()
const router = useRouter()
const appointments = ref([])
const doctors = ref([])
const services = ref([])
const slots = ref([])
const loading = ref(false)
const loadingSlots = ref(false)
const creating = ref(false)
const createDialog = ref(false)
const selectedSlot = ref(null)
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
const createForm = reactive({
  service_id: null,
  doctor_id: null,
  appointment_date: new Date().toISOString().slice(0, 10),
  patient: {
    full_name: '',
    email: '',
    phone: '',
  },
  reason: '',
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
const bookingServiceItems = computed(() => services.value
  .filter((service) => service.is_active)
  .map((service) => ({
    title: `${service.name} (${service.duration_minutes} min)`,
    value: service.id,
  })))
const bookingDoctorItems = computed(() => [
  { title: 'Any available doctor', value: null },
  ...doctors.value
    .filter((doctor) => doctor.is_active)
    .map((doctor) => ({
      title: doctor.full_name,
      value: doctor.id,
      subtitle: doctor.specialization || 'General',
    })),
])
const canLoadSlots = computed(() => Boolean(createForm.service_id && createForm.appointment_date))
const canCreate = computed(() => Boolean(
  selectedSlot.value
    && createForm.service_id
    && createForm.patient.full_name
    && createForm.patient.email
    && createForm.patient.phone,
))

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

function resetCreateForm() {
  createForm.service_id = null
  createForm.doctor_id = null
  createForm.appointment_date = new Date().toISOString().slice(0, 10)
  createForm.patient.full_name = ''
  createForm.patient.email = ''
  createForm.patient.phone = ''
  createForm.reason = ''
  selectedSlot.value = null
  slots.value = []
}

function openCreateDialog() {
  resetCreateForm()
  createDialog.value = true
}

async function loadSlots() {
  selectedSlot.value = null
  slots.value = []

  if (!canLoadSlots.value) {
    return
  }

  loadingSlots.value = true

  try {
    const { data } = await api.get('/public/available-slots', {
      params: {
        service_id: createForm.service_id,
        doctor_id: createForm.doctor_id || undefined,
        date: createForm.appointment_date,
      },
    })
    slots.value = data.data
  } catch (err) {
    notify(getErrorMessage(err), 'error')
  } finally {
    loadingSlots.value = false
  }
}

async function createAppointment() {
  if (!selectedSlot.value) {
    notify('Choose an available time slot first.', 'warning')
    return
  }

  creating.value = true

  try {
    await api.post('/public/appointments', {
      service_id: createForm.service_id,
      doctor_id: selectedSlot.value.doctor_id,
      appointment_date: createForm.appointment_date,
      start_time: selectedSlot.value.start_time,
      patient: createForm.patient,
      reason: createForm.reason || null,
    })

    notify('Appointment request created for the patient.')
    createDialog.value = false
    await loadAppointments()
  } catch (err) {
    notify(getErrorMessage(err), 'error')
    await loadSlots()
  } finally {
    creating.value = false
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
    if (route.query.new === '1') {
      openCreateDialog()
      await router.replace({ name: 'admin.appointments' })
    }
  } catch (err) {
    notify(getErrorMessage(err), 'error')
  }
})

watch(
  () => [createForm.service_id, createForm.doctor_id, createForm.appointment_date],
  loadSlots,
)

watch(
  () => route.query.new,
  async (value) => {
    if (value === '1') {
      openCreateDialog()
      await router.replace({ name: 'admin.appointments' })
    }
  },
)
</script>

<template>
  <div>
    <div class="d-flex flex-wrap align-center justify-space-between ga-4 mb-6">
      <div>
        <div class="page-title">Appointments</div>
        <div class="page-subtitle mt-1">Review requests and manage clinic appointment status.</div>
      </div>
      <div class="d-flex flex-wrap ga-2">
        <v-btn color="primary" prepend-icon="mdi-calendar-plus" @click="openCreateDialog">
          New appointment
        </v-btn>
        <v-btn color="primary" variant="tonal" prepend-icon="mdi-refresh" :loading="loading" @click="loadAppointments">
          Refresh
        </v-btn>
      </div>
    </div>

    <v-card rounded="lg" elevation="0" class="panel-card mb-5">
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

    <v-card rounded="lg" elevation="0" class="panel-card">
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

    <v-dialog v-model="createDialog" max-width="980">
      <v-card rounded="lg">
        <v-card-title class="d-flex align-center justify-space-between px-6 pt-6">
          <div>
            <div class="text-h6 font-weight-bold">Book appointment for patient</div>
            <div class="text-body-2 text-medium-emphasis">Create a pending request from the staff workspace.</div>
          </div>
          <v-btn icon="mdi-close" variant="text" @click="createDialog = false" />
        </v-card-title>

        <v-card-text class="px-6">
          <v-row>
            <v-col cols="12" md="7">
              <v-select
                v-model="createForm.service_id"
                :items="bookingServiceItems"
                item-title="title"
                item-value="value"
                label="Service"
                variant="outlined"
                density="comfortable"
              />

              <v-select
                v-model="createForm.doctor_id"
                :items="bookingDoctorItems"
                item-title="title"
                item-value="value"
                label="Doctor"
                variant="outlined"
                density="comfortable"
              />

              <v-text-field
                v-model="createForm.appointment_date"
                label="Date"
                type="date"
                variant="outlined"
                density="comfortable"
              />

              <div class="text-subtitle-2 mb-3">Available time slots</div>
              <div v-if="loadingSlots" class="py-6 text-center">
                <v-progress-circular indeterminate size="28" width="3" />
              </div>
              <div v-else-if="!canLoadSlots" class="table-empty">
                Select a service and date to see available slots.
              </div>
              <div v-else-if="slots.length === 0" class="table-empty">
                No available slots for the selected date.
              </div>
              <div v-else class="d-flex flex-wrap ga-2">
                <v-btn
                  v-for="slot in slots"
                  :key="`${slot.doctor_id}-${slot.start_time}`"
                  :color="selectedSlot === slot ? 'primary' : undefined"
                  :variant="selectedSlot === slot ? 'flat' : 'tonal'"
                  rounded="lg"
                  @click="selectedSlot = slot"
                >
                  {{ slot.start_time }}
                  <span class="ml-2 text-caption">{{ slot.doctor.full_name }}</span>
                </v-btn>
              </div>
            </v-col>

            <v-col cols="12" md="5">
              <v-text-field
                v-model="createForm.patient.full_name"
                label="Patient full name"
                variant="outlined"
                density="comfortable"
              />
              <v-text-field
                v-model="createForm.patient.email"
                label="Patient email"
                type="email"
                variant="outlined"
                density="comfortable"
              />
              <v-text-field
                v-model="createForm.patient.phone"
                label="Patient phone"
                variant="outlined"
                density="comfortable"
              />
              <v-textarea
                v-model="createForm.reason"
                label="Reason for visit"
                variant="outlined"
                density="comfortable"
                rows="4"
              />

              <v-sheet v-if="selectedSlot" color="primary" rounded="lg" class="pa-4 mb-5">
                <div class="text-caption">Selected slot</div>
                <div class="font-weight-bold">
                  {{ createForm.appointment_date }} at {{ selectedSlot.start_time }}
                </div>
                <div class="text-body-2">{{ selectedSlot.doctor.full_name }}</div>
              </v-sheet>
            </v-col>
          </v-row>
        </v-card-text>

        <v-card-actions class="px-6 pb-6">
          <v-spacer />
          <v-btn variant="text" @click="createDialog = false">Cancel</v-btn>
          <v-btn color="primary" :disabled="!canCreate" :loading="creating" @click="createAppointment">
            Create request
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <v-snackbar v-model="snackbar.show" :color="snackbar.color" timeout="3000">
      {{ snackbar.text }}
    </v-snackbar>
  </div>
</template>
