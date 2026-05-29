<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useAuth } from '../../composables/useAuth'
import api, { getErrorMessage } from '../../services/api'

const router = useRouter()
const { state, fetchMe } = useAuth()
const services = ref([])
const doctors = ref([])
const slots = ref([])
const loadingCatalog = ref(false)
const loadingSlots = ref(false)
const submitting = ref(false)
const selectedSlot = ref(null)
const snackbar = reactive({
  show: false,
  text: '',
  color: 'success',
})

const form = reactive({
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

const serviceItems = computed(() => services.value.map((service) => ({
  title: `${service.name} (${service.duration_minutes} min)`,
  value: service.id,
  subtitle: service.price ? `PHP ${service.price}` : 'Price not set',
})))

const doctorItems = computed(() => [
  { title: 'Any available doctor', value: null },
  ...doctors.value.map((doctor) => ({
    title: doctor.full_name,
    value: doctor.id,
    subtitle: doctor.specialization || 'General',
  })),
])

const canLoadSlots = computed(() => Boolean(form.service_id && form.appointment_date))
const canSubmit = computed(() => Boolean(
  selectedSlot.value
    && form.service_id
    && form.patient.full_name
    && form.patient.email
    && form.patient.phone,
))
const staffDestination = computed(() => {
  if (state.user?.role === 'doctor') {
    return '/doctor/dashboard'
  }

  return '/admin/dashboard'
})
const staffButtonText = computed(() => (state.user ? 'Back to workspace' : 'Staff login'))

function notify(text, color = 'success') {
  snackbar.text = text
  snackbar.color = color
  snackbar.show = true
}

async function loadCatalog() {
  loadingCatalog.value = true

  try {
    const [servicesResponse, doctorsResponse] = await Promise.all([
      api.get('/public/services'),
      api.get('/public/doctors'),
    ])

    services.value = servicesResponse.data.data
    doctors.value = doctorsResponse.data.data
  } catch (err) {
    notify(getErrorMessage(err), 'error')
  } finally {
    loadingCatalog.value = false
  }
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
        service_id: form.service_id,
        doctor_id: form.doctor_id || undefined,
        date: form.appointment_date,
      },
    })
    slots.value = data.data
  } catch (err) {
    notify(getErrorMessage(err), 'error')
  } finally {
    loadingSlots.value = false
  }
}

async function submitBooking() {
  if (!selectedSlot.value) {
    notify('Choose an available time slot first.', 'warning')
    return
  }

  submitting.value = true

  try {
    const { data } = await api.post('/public/appointments', {
      service_id: form.service_id,
      doctor_id: selectedSlot.value.doctor_id,
      appointment_date: form.appointment_date,
      start_time: selectedSlot.value.start_time,
      patient: form.patient,
      reason: form.reason || null,
    })

    await router.push({
      name: 'booking.success',
      query: {
        id: data.data.id,
        date: data.data.appointment_date,
        time: data.data.start_time,
      },
    })
  } catch (err) {
    notify(getErrorMessage(err), 'error')
    await loadSlots()
  } finally {
    submitting.value = false
  }
}

watch(
  () => [form.service_id, form.doctor_id, form.appointment_date],
  loadSlots,
)

onMounted(async () => {
  if (state.token && !state.user) {
    try {
      await fetchMe()
    } catch {
      localStorage.removeItem('clinic_auth_token')
      state.token = null
      state.user = null
    }
  }

  await loadCatalog()
  await loadSlots()
})
</script>

<template>
  <v-main class="bg-background">
    <v-container class="py-8 py-md-12" style="max-width: 1120px;">
      <div class="d-flex flex-wrap align-center justify-space-between ga-4 mb-6">
        <div>
          <div class="page-title">Book an Appointment</div>
          <div class="page-subtitle mt-1">
            Submit a request and clinic staff will confirm your schedule.
          </div>
        </div>
        <v-btn :to="state.user ? staffDestination : '/login'" variant="tonal" color="primary" prepend-icon="mdi-shield-account">
          {{ staffButtonText }}
        </v-btn>
      </div>

      <v-row>
        <v-col cols="12" md="7">
          <v-card rounded="lg" elevation="1">
            <v-card-title class="px-6 pt-6">Appointment details</v-card-title>
            <v-card-text class="px-6">
              <v-alert
                type="info"
                variant="tonal"
                class="mb-5"
              >
                Appointment requests are pending until approved by clinic staff.
              </v-alert>

              <v-select
                v-model="form.service_id"
                :items="serviceItems"
                :loading="loadingCatalog"
                item-title="title"
                item-value="value"
                label="Service"
                variant="outlined"
                density="comfortable"
              />

              <v-select
                v-model="form.doctor_id"
                :items="doctorItems"
                item-title="title"
                item-value="value"
                label="Doctor"
                variant="outlined"
                density="comfortable"
              />

              <v-text-field
                v-model="form.appointment_date"
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
            </v-card-text>
          </v-card>
        </v-col>

        <v-col cols="12" md="5">
          <v-card rounded="lg" elevation="1">
            <v-card-title class="px-6 pt-6">Patient information</v-card-title>
            <v-card-text class="px-6">
              <v-text-field
                v-model="form.patient.full_name"
                label="Full name"
                variant="outlined"
                density="comfortable"
              />
              <v-text-field
                v-model="form.patient.email"
                label="Email"
                type="email"
                variant="outlined"
                density="comfortable"
              />
              <v-text-field
                v-model="form.patient.phone"
                label="Phone number"
                variant="outlined"
                density="comfortable"
              />
              <v-textarea
                v-model="form.reason"
                label="Reason for visit"
                variant="outlined"
                density="comfortable"
                rows="4"
              />

              <v-sheet v-if="selectedSlot" color="primary" rounded="lg" class="pa-4 mb-5">
                  <div class="text-caption">Selected slot</div>
                  <div class="font-weight-bold">
                    {{ selectedSlot.date }} at {{ selectedSlot.start_time }}
                  </div>
                  <div class="text-body-2">{{ selectedSlot.doctor.full_name }}</div>
              </v-sheet>

              <v-btn
                color="primary"
                size="large"
                block
                :disabled="!canSubmit"
                :loading="submitting"
                @click="submitBooking"
              >
                Submit request
              </v-btn>
            </v-card-text>
          </v-card>
        </v-col>
      </v-row>
    </v-container>

    <v-snackbar v-model="snackbar.show" :color="snackbar.color" timeout="3500">
      {{ snackbar.text }}
    </v-snackbar>
  </v-main>
</template>
