<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api, { getErrorMessage } from '../../services/api'

const route = useRoute()
const router = useRouter()
const appointment = ref(null)
const doctors = ref([])
const slots = ref([])
const loading = ref(false)
const saving = ref(false)
const actionDialog = ref(false)
const rescheduleDialog = ref(false)
const currentAction = ref(null)
const actionNotes = ref('')
const snackbar = reactive({
  show: false,
  text: '',
  color: 'success',
})

const reschedule = reactive({
  doctor_id: null,
  appointment_date: '',
  start_time: '',
  notes: '',
})

const doctorItems = computed(() => doctors.value.map((doctor) => ({
  title: doctor.full_name,
  value: doctor.id,
})))

const actions = computed(() => [
  { key: 'approve', label: 'Approve', color: 'primary', show: appointment.value?.status === 'pending' },
  { key: 'reject', label: 'Reject', color: 'error', show: appointment.value?.status === 'pending' },
  { key: 'cancel', label: 'Cancel', color: 'error', show: ['pending', 'confirmed'].includes(appointment.value?.status) },
  { key: 'complete', label: 'Complete', color: 'success', show: appointment.value?.status === 'confirmed' },
  { key: 'no-show', label: 'No-show', color: 'warning', show: appointment.value?.status === 'confirmed' },
])

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

async function loadAppointment() {
  loading.value = true

  try {
    const { data } = await api.get(`/admin/appointments/${route.params.id}`)
    appointment.value = data.data
  } catch (err) {
    notify(getErrorMessage(err), 'error')
  } finally {
    loading.value = false
  }
}

async function loadDoctors() {
  const { data } = await api.get('/admin/doctors?is_active=true')
  doctors.value = data.data
}

async function saveNotes() {
  saving.value = true

  try {
    const { data } = await api.patch(`/admin/appointments/${appointment.value.id}/notes`, {
      admin_notes: appointment.value.admin_notes || null,
    })
    appointment.value = data.data
    notify('Notes saved.')
  } catch (err) {
    notify(getErrorMessage(err), 'error')
  } finally {
    saving.value = false
  }
}

function openAction(action) {
  currentAction.value = action
  actionNotes.value = ''
  actionDialog.value = true
}

async function submitAction() {
  saving.value = true

  try {
    const { data } = await api.post(`/admin/appointments/${appointment.value.id}/${currentAction.value.key}`, {
      notes: actionNotes.value || null,
    })
    appointment.value = data.data
    notify(`Appointment marked as ${appointment.value.status}.`)
    actionDialog.value = false
    await loadAppointment()
  } catch (err) {
    notify(getErrorMessage(err), 'error')
  } finally {
    saving.value = false
  }
}

function openReschedule() {
  reschedule.doctor_id = appointment.value.doctor_id
  reschedule.appointment_date = appointment.value.appointment_date
  reschedule.start_time = ''
  reschedule.notes = ''
  slots.value = []
  rescheduleDialog.value = true
  loadSlots()
}

async function loadSlots() {
  slots.value = []

  if (!reschedule.doctor_id || !reschedule.appointment_date || !appointment.value?.service_id) {
    return
  }

  try {
    const { data } = await api.get('/public/available-slots', {
      params: {
        service_id: appointment.value.service_id,
        doctor_id: reschedule.doctor_id,
        date: reschedule.appointment_date,
      },
    })
    slots.value = data.data
  } catch (err) {
    notify(getErrorMessage(err), 'error')
  }
}

async function submitReschedule() {
  saving.value = true

  try {
    const { data } = await api.post(`/admin/appointments/${appointment.value.id}/reschedule`, {
      doctor_id: reschedule.doctor_id,
      appointment_date: reschedule.appointment_date,
      start_time: reschedule.start_time,
      notes: reschedule.notes || null,
    })
    appointment.value = data.data
    notify('Appointment rescheduled.')
    rescheduleDialog.value = false
    await loadAppointment()
  } catch (err) {
    notify(getErrorMessage(err), 'error')
  } finally {
    saving.value = false
  }
}

onMounted(async () => {
  await Promise.all([loadAppointment(), loadDoctors()])
})
</script>

<template>
  <div>
    <div class="d-flex flex-wrap align-center justify-space-between ga-4 mb-6">
      <div>
        <v-btn variant="text" prepend-icon="mdi-arrow-left" class="mb-2" @click="router.push('/admin/appointments')">
          Back
        </v-btn>
        <div class="page-title">Appointment Details</div>
        <div v-if="appointment" class="page-subtitle mt-1">Reference #{{ appointment.id }}</div>
      </div>
      <div v-if="appointment" class="d-flex flex-wrap ga-2">
        <v-btn variant="tonal" color="primary" prepend-icon="mdi-calendar-edit" @click="openReschedule">
          Reschedule
        </v-btn>
        <v-btn
          v-for="action in actions.filter((item) => item.show)"
          :key="action.key"
          :color="action.color"
          variant="tonal"
          @click="openAction(action)"
        >
          {{ action.label }}
        </v-btn>
      </div>
    </div>

    <div v-if="loading" class="text-center py-10">
      <v-progress-circular indeterminate />
    </div>

    <v-row v-else-if="appointment">
      <v-col cols="12" md="7">
        <v-card rounded="lg" elevation="1">
          <v-card-title class="px-6 pt-6">Appointment</v-card-title>
          <v-card-text class="px-6">
            <v-chip :color="statusColor(appointment.status)" variant="tonal" class="mb-5">
              {{ appointment.status }}
            </v-chip>
            <v-list lines="two">
              <v-list-item title="Patient" :subtitle="`${appointment.patient?.full_name} | ${appointment.patient?.phone}`" />
              <v-list-item title="Email" :subtitle="appointment.patient?.email" />
              <v-list-item title="Doctor" :subtitle="appointment.doctor?.full_name" />
              <v-list-item title="Service" :subtitle="appointment.service?.name" />
              <v-list-item title="Schedule" :subtitle="`${appointment.appointment_date} ${appointment.start_time} to ${appointment.end_time}`" />
              <v-list-item title="Reason" :subtitle="appointment.reason || 'No reason provided'" />
            </v-list>

            <v-textarea
              v-model="appointment.admin_notes"
              label="Internal admin notes"
              rows="4"
              variant="outlined"
              density="comfortable"
              class="mt-4"
            />
            <v-btn color="primary" :loading="saving" @click="saveNotes">Save notes</v-btn>
          </v-card-text>
        </v-card>
      </v-col>

      <v-col cols="12" md="5">
        <v-card rounded="lg" elevation="1">
          <v-card-title class="px-6 pt-6">Status History</v-card-title>
          <v-card-text>
            <div v-if="!appointment.status_logs?.length" class="table-empty">No status history yet.</div>
            <v-timeline v-else side="end" density="compact">
              <v-timeline-item
                v-for="log in appointment.status_logs"
                :key="log.id"
                :dot-color="statusColor(log.new_status)"
                size="small"
              >
                <div class="font-weight-medium">{{ log.new_status }}</div>
                <div class="text-caption text-medium-emphasis">{{ log.created_at }}</div>
                <div v-if="log.notes" class="text-body-2 mt-1">{{ log.notes }}</div>
                <div v-if="log.changed_by" class="text-caption text-medium-emphasis">
                  By {{ log.changed_by.name }}
                </div>
              </v-timeline-item>
            </v-timeline>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>

    <v-dialog v-model="actionDialog" max-width="480">
      <v-card rounded="lg">
        <v-card-title>{{ currentAction?.label }} appointment?</v-card-title>
        <v-card-text>
          <v-textarea v-model="actionNotes" label="Notes or reason" rows="3" variant="outlined" density="comfortable" />
        </v-card-text>
        <v-card-actions>
          <v-spacer />
          <v-btn variant="text" @click="actionDialog = false">Cancel</v-btn>
          <v-btn :color="currentAction?.color" :loading="saving" @click="submitAction">
            Confirm
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <v-dialog v-model="rescheduleDialog" max-width="680">
      <v-card rounded="lg">
        <v-card-title>Reschedule appointment</v-card-title>
        <v-card-text>
          <v-row>
            <v-col cols="12" sm="6">
              <v-select v-model="reschedule.doctor_id" :items="doctorItems" label="Doctor" variant="outlined" density="comfortable" @update:model-value="loadSlots" />
            </v-col>
            <v-col cols="12" sm="6">
              <v-text-field v-model="reschedule.appointment_date" label="Date" type="date" variant="outlined" density="comfortable" @update:model-value="loadSlots" />
            </v-col>
          </v-row>

          <div class="text-subtitle-2 mb-3">Available slots</div>
          <div v-if="slots.length === 0" class="table-empty">No slots found for this date.</div>
          <div v-else class="d-flex flex-wrap ga-2 mb-5">
            <v-btn
              v-for="slot in slots"
              :key="slot.start_time"
              :color="reschedule.start_time === slot.start_time ? 'primary' : undefined"
              :variant="reschedule.start_time === slot.start_time ? 'flat' : 'tonal'"
              @click="reschedule.start_time = slot.start_time"
            >
              {{ slot.start_time }}
            </v-btn>
          </div>

          <v-textarea v-model="reschedule.notes" label="Notes" rows="3" variant="outlined" density="comfortable" />
        </v-card-text>
        <v-card-actions>
          <v-spacer />
          <v-btn variant="text" @click="rescheduleDialog = false">Cancel</v-btn>
          <v-btn color="primary" :disabled="!reschedule.start_time" :loading="saving" @click="submitReschedule">
            Save
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <v-snackbar v-model="snackbar.show" :color="snackbar.color" timeout="3000">
      {{ snackbar.text }}
    </v-snackbar>
  </div>
</template>
