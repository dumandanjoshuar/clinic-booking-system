<script setup>
import { onMounted, reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api, { getErrorMessage } from '../../services/api'

const route = useRoute()
const router = useRouter()
const appointment = ref(null)
const loading = ref(false)
const saving = ref(false)
const completeDialog = ref(false)
const notes = ref('')
const snackbar = reactive({
  show: false,
  text: '',
  color: 'success',
})

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
    const { data } = await api.get(`/doctor/appointments/${route.params.id}`)
    appointment.value = data.data
  } catch (err) {
    notify(getErrorMessage(err), 'error')
  } finally {
    loading.value = false
  }
}

async function markCompleted() {
  saving.value = true

  try {
    const { data } = await api.post(`/doctor/appointments/${appointment.value.id}/complete`, {
      notes: notes.value || null,
    })
    appointment.value = data.data
    notify('Appointment completed.')
    completeDialog.value = false
    await loadAppointment()
  } catch (err) {
    notify(getErrorMessage(err), 'error')
  } finally {
    saving.value = false
  }
}

onMounted(loadAppointment)
</script>

<template>
  <div>
    <div class="d-flex flex-wrap align-center justify-space-between ga-4 mb-6">
      <div>
        <v-btn variant="text" prepend-icon="mdi-arrow-left" class="mb-2" @click="router.push('/doctor/schedule')">
          Back
        </v-btn>
        <div class="page-title">Appointment Details</div>
        <div v-if="appointment" class="page-subtitle mt-1">Reference #{{ appointment.id }}</div>
      </div>
      <v-btn
        v-if="appointment?.status === 'confirmed'"
        color="success"
        prepend-icon="mdi-check-circle-outline"
        @click="completeDialog = true"
      >
        Mark completed
      </v-btn>
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
              <v-list-item title="Service" :subtitle="appointment.service?.name" />
              <v-list-item title="Schedule" :subtitle="`${appointment.appointment_date} ${appointment.start_time} to ${appointment.end_time}`" />
              <v-list-item title="Reason" :subtitle="appointment.reason || 'No reason provided'" />
            </v-list>
          </v-card-text>
        </v-card>
      </v-col>

      <v-col cols="12" md="5">
        <v-card rounded="lg" elevation="1">
          <v-card-title class="px-6 pt-6">Clinic Notes</v-card-title>
          <v-card-text>
            <div class="text-body-2">
              {{ appointment.admin_notes || 'No internal notes for this appointment.' }}
            </div>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>

    <v-dialog v-model="completeDialog" max-width="480">
      <v-card rounded="lg">
        <v-card-title>Mark appointment completed?</v-card-title>
        <v-card-text>
          <v-textarea v-model="notes" label="Completion notes" rows="3" variant="outlined" density="comfortable" />
        </v-card-text>
        <v-card-actions>
          <v-spacer />
          <v-btn variant="text" @click="completeDialog = false">Cancel</v-btn>
          <v-btn color="success" :loading="saving" @click="markCompleted">Complete</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <v-snackbar v-model="snackbar.show" :color="snackbar.color" timeout="3000">
      {{ snackbar.text }}
    </v-snackbar>
  </div>
</template>
