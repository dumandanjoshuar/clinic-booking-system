<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import api, { getErrorMessage } from '../../services/api'

const availability = ref([])
const doctors = ref([])
const loading = ref(false)
const saving = ref(false)
const dialog = ref(false)
const confirmDialog = ref(false)
const selectedAvailability = ref(null)
const snackbar = reactive({
  show: false,
  text: '',
  color: 'success',
})

const days = [
  { title: 'Sunday', value: 0 },
  { title: 'Monday', value: 1 },
  { title: 'Tuesday', value: 2 },
  { title: 'Wednesday', value: 3 },
  { title: 'Thursday', value: 4 },
  { title: 'Friday', value: 5 },
  { title: 'Saturday', value: 6 },
]

const emptyForm = {
  doctor_id: null,
  day_of_week: 1,
  start_time: '09:00',
  end_time: '17:00',
  break_start: '',
  break_end: '',
  slot_duration_minutes: 30,
  is_active: true,
}

const form = reactive({ ...emptyForm })
const isEditing = computed(() => Boolean(selectedAvailability.value))
const doctorItems = computed(() => doctors.value.map((doctor) => ({
  title: doctor.full_name,
  value: doctor.id,
})))

function dayName(day) {
  return days.find((item) => item.value === day)?.title || 'Unknown'
}

function notify(text, color = 'success') {
  snackbar.text = text
  snackbar.color = color
  snackbar.show = true
}

function resetForm() {
  Object.assign(form, emptyForm)
  selectedAvailability.value = null
}

async function loadAll() {
  loading.value = true

  try {
    const [availabilityResponse, doctorsResponse] = await Promise.all([
      api.get('/admin/availability'),
      api.get('/admin/doctors?is_active=true'),
    ])

    availability.value = availabilityResponse.data.data
    doctors.value = doctorsResponse.data.data
  } catch (err) {
    notify(getErrorMessage(err), 'error')
  } finally {
    loading.value = false
  }
}

function openCreateDialog() {
  resetForm()
  dialog.value = true
}

function openEditDialog(item) {
  selectedAvailability.value = item
  Object.assign(form, {
    doctor_id: item.doctor_id,
    day_of_week: item.day_of_week,
    start_time: item.start_time,
    end_time: item.end_time,
    break_start: item.break_start || '',
    break_end: item.break_end || '',
    slot_duration_minutes: item.slot_duration_minutes,
    is_active: item.is_active,
  })
  dialog.value = true
}

async function saveAvailability() {
  saving.value = true

  const payload = {
    doctor_id: form.doctor_id,
    day_of_week: Number(form.day_of_week),
    start_time: form.start_time,
    end_time: form.end_time,
    break_start: form.break_start || null,
    break_end: form.break_end || null,
    slot_duration_minutes: Number(form.slot_duration_minutes),
    is_active: form.is_active,
  }

  try {
    if (isEditing.value) {
      await api.put(`/admin/availability/${selectedAvailability.value.id}`, payload)
      notify('Availability updated.')
    } else {
      await api.post('/admin/availability', payload)
      notify('Availability created.')
    }

    dialog.value = false
    resetForm()
    await loadAll()
  } catch (err) {
    notify(getErrorMessage(err), 'error')
  } finally {
    saving.value = false
  }
}

function askDeactivate(item) {
  selectedAvailability.value = item
  confirmDialog.value = true
}

async function deactivateAvailability() {
  saving.value = true

  try {
    await api.delete(`/admin/availability/${selectedAvailability.value.id}`)
    notify('Availability deactivated.')
    confirmDialog.value = false
    selectedAvailability.value = null
    await loadAll()
  } catch (err) {
    notify(getErrorMessage(err), 'error')
  } finally {
    saving.value = false
  }
}

onMounted(loadAll)
</script>

<template>
  <div>
    <div class="d-flex flex-wrap align-center justify-space-between ga-4 mb-6">
      <div>
        <div class="page-title">Doctor Availability</div>
        <div class="page-subtitle mt-1">Define weekly scheduling windows and slot duration.</div>
      </div>
      <v-btn color="primary" prepend-icon="mdi-plus" @click="openCreateDialog">
        New rule
      </v-btn>
    </div>

    <v-card rounded="lg" elevation="1">
      <v-table>
        <thead>
          <tr>
            <th>Doctor</th>
            <th>Day</th>
            <th>Hours</th>
            <th>Break</th>
            <th>Slot</th>
            <th>Status</th>
            <th class="text-right">Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="loading">
            <td colspan="7" class="table-empty">
              <v-progress-circular indeterminate size="28" width="3" />
            </td>
          </tr>
          <tr v-else-if="availability.length === 0">
            <td colspan="7" class="table-empty">No availability rules yet.</td>
          </tr>
          <tr v-for="item in availability" v-else :key="item.id">
            <td>
              <div class="font-weight-medium">{{ item.doctor?.full_name || 'Unknown doctor' }}</div>
              <div class="text-caption text-medium-emphasis">{{ item.doctor?.specialization || 'General' }}</div>
            </td>
            <td>{{ dayName(item.day_of_week) }}</td>
            <td>{{ item.start_time }} to {{ item.end_time }}</td>
            <td>
              <span v-if="item.break_start && item.break_end">{{ item.break_start }} to {{ item.break_end }}</span>
              <span v-else>None</span>
            </td>
            <td>{{ item.slot_duration_minutes }} min</td>
            <td>
              <v-chip
                :color="item.is_active ? 'success' : 'default'"
                size="small"
                variant="tonal"
              >
                {{ item.is_active ? 'Active' : 'Inactive' }}
              </v-chip>
            </td>
            <td class="text-right">
              <v-btn icon="mdi-pencil" variant="text" size="small" @click="openEditDialog(item)" />
              <v-btn
                icon="mdi-close-circle-outline"
                variant="text"
                size="small"
                color="error"
                :disabled="!item.is_active"
                @click="askDeactivate(item)"
              />
            </td>
          </tr>
        </tbody>
      </v-table>
    </v-card>

    <v-dialog v-model="dialog" max-width="720">
      <v-card rounded="lg">
        <v-card-title class="px-6 pt-6">
          {{ isEditing ? 'Edit availability' : 'New availability' }}
        </v-card-title>
        <v-card-text class="px-6">
          <v-row>
            <v-col cols="12" sm="7">
              <v-select
                v-model="form.doctor_id"
                :items="doctorItems"
                label="Doctor"
                variant="outlined"
                density="comfortable"
              />
            </v-col>
            <v-col cols="12" sm="5">
              <v-select
                v-model="form.day_of_week"
                :items="days"
                label="Day"
                variant="outlined"
                density="comfortable"
              />
            </v-col>
          </v-row>

          <v-row>
            <v-col cols="12" sm="6">
              <v-text-field
                v-model="form.start_time"
                label="Start time"
                type="time"
                variant="outlined"
                density="comfortable"
              />
            </v-col>
            <v-col cols="12" sm="6">
              <v-text-field
                v-model="form.end_time"
                label="End time"
                type="time"
                variant="outlined"
                density="comfortable"
              />
            </v-col>
          </v-row>

          <v-row>
            <v-col cols="12" sm="6">
              <v-text-field
                v-model="form.break_start"
                label="Break start"
                type="time"
                variant="outlined"
                density="comfortable"
              />
            </v-col>
            <v-col cols="12" sm="6">
              <v-text-field
                v-model="form.break_end"
                label="Break end"
                type="time"
                variant="outlined"
                density="comfortable"
              />
            </v-col>
          </v-row>

          <v-row align="center">
            <v-col cols="12" sm="6">
              <v-text-field
                v-model="form.slot_duration_minutes"
                label="Slot duration minutes"
                type="number"
                min="5"
                variant="outlined"
                density="comfortable"
              />
            </v-col>
            <v-col cols="12" sm="6">
              <v-switch v-model="form.is_active" color="primary" label="Active" hide-details />
            </v-col>
          </v-row>
        </v-card-text>
        <v-card-actions class="px-6 pb-6">
          <v-spacer />
          <v-btn variant="text" @click="dialog = false">Cancel</v-btn>
          <v-btn color="primary" :loading="saving" @click="saveAvailability">Save</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <v-dialog v-model="confirmDialog" max-width="440">
      <v-card rounded="lg">
        <v-card-title>Deactivate availability?</v-card-title>
        <v-card-text>
          This weekly rule will stop generating future appointment slots.
        </v-card-text>
        <v-card-actions>
          <v-spacer />
          <v-btn variant="text" @click="confirmDialog = false">Cancel</v-btn>
          <v-btn color="error" :loading="saving" @click="deactivateAvailability">Deactivate</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <v-snackbar v-model="snackbar.show" :color="snackbar.color" timeout="3000">
      {{ snackbar.text }}
    </v-snackbar>
  </div>
</template>
