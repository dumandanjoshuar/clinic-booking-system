<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import api, { getErrorMessage } from '../../services/api'

const doctors = ref([])
const loading = ref(false)
const saving = ref(false)
const dialog = ref(false)
const confirmDialog = ref(false)
const selectedDoctor = ref(null)
const snackbar = reactive({
  show: false,
  text: '',
  color: 'success',
})

const emptyForm = {
  full_name: '',
  email: '',
  phone: '',
  specialization: '',
  is_active: true,
}

const form = reactive({ ...emptyForm })
const isEditing = computed(() => Boolean(selectedDoctor.value))

function notify(text, color = 'success') {
  snackbar.text = text
  snackbar.color = color
  snackbar.show = true
}

function resetForm() {
  Object.assign(form, emptyForm)
  selectedDoctor.value = null
}

async function loadDoctors() {
  loading.value = true

  try {
    const { data } = await api.get('/admin/doctors')
    doctors.value = data.data
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

function openEditDialog(doctor) {
  selectedDoctor.value = doctor
  Object.assign(form, {
    full_name: doctor.full_name,
    email: doctor.email || '',
    phone: doctor.phone || '',
    specialization: doctor.specialization || '',
    is_active: doctor.is_active,
  })
  dialog.value = true
}

async function saveDoctor() {
  saving.value = true

  const payload = {
    ...form,
    email: form.email || null,
    phone: form.phone || null,
    specialization: form.specialization || null,
  }

  try {
    if (isEditing.value) {
      await api.put(`/admin/doctors/${selectedDoctor.value.id}`, payload)
      notify('Doctor updated.')
    } else {
      await api.post('/admin/doctors', payload)
      notify('Doctor created.')
    }

    dialog.value = false
    resetForm()
    await loadDoctors()
  } catch (err) {
    notify(getErrorMessage(err), 'error')
  } finally {
    saving.value = false
  }
}

function askDeactivate(doctor) {
  selectedDoctor.value = doctor
  confirmDialog.value = true
}

async function deactivateDoctor() {
  saving.value = true

  try {
    await api.delete(`/admin/doctors/${selectedDoctor.value.id}`)
    notify('Doctor deactivated.')
    confirmDialog.value = false
    selectedDoctor.value = null
    await loadDoctors()
  } catch (err) {
    notify(getErrorMessage(err), 'error')
  } finally {
    saving.value = false
  }
}

onMounted(loadDoctors)
</script>

<template>
  <div>
    <div class="d-flex flex-wrap align-center justify-space-between ga-4 mb-6">
      <div>
        <div class="page-title">Doctors</div>
        <div class="page-subtitle mt-1">Manage doctors who can receive appointments.</div>
      </div>
      <v-btn color="primary" prepend-icon="mdi-plus" @click="openCreateDialog">
        New doctor
      </v-btn>
    </div>

    <v-card rounded="lg" elevation="1">
      <v-table>
        <thead>
          <tr>
            <th>Name</th>
            <th>Specialization</th>
            <th>Contact</th>
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
          <tr v-else-if="doctors.length === 0">
            <td colspan="5" class="table-empty">No doctors yet.</td>
          </tr>
          <tr v-for="doctor in doctors" v-else :key="doctor.id">
            <td>
              <div class="font-weight-medium">{{ doctor.full_name }}</div>
              <div v-if="doctor.user" class="text-caption text-medium-emphasis">
                Linked to {{ doctor.user.email }}
              </div>
            </td>
            <td>{{ doctor.specialization || 'Not set' }}</td>
            <td>
              <div>{{ doctor.email || 'No email' }}</div>
              <div class="text-caption text-medium-emphasis">{{ doctor.phone || 'No phone' }}</div>
            </td>
            <td>
              <v-chip
                :color="doctor.is_active ? 'success' : 'default'"
                size="small"
                variant="tonal"
              >
                {{ doctor.is_active ? 'Active' : 'Inactive' }}
              </v-chip>
            </td>
            <td class="text-right">
              <v-btn icon="mdi-pencil" variant="text" size="small" @click="openEditDialog(doctor)" />
              <v-btn
                icon="mdi-close-circle-outline"
                variant="text"
                size="small"
                color="error"
                :disabled="!doctor.is_active"
                @click="askDeactivate(doctor)"
              />
            </td>
          </tr>
        </tbody>
      </v-table>
    </v-card>

    <v-dialog v-model="dialog" max-width="620">
      <v-card rounded="lg">
        <v-card-title class="px-6 pt-6">
          {{ isEditing ? 'Edit doctor' : 'New doctor' }}
        </v-card-title>
        <v-card-text class="px-6">
          <v-text-field v-model="form.full_name" label="Full name" variant="outlined" density="comfortable" />
          <v-text-field v-model="form.specialization" label="Specialization" variant="outlined" density="comfortable" />
          <v-row>
            <v-col cols="12" sm="6">
              <v-text-field v-model="form.email" label="Email" type="email" variant="outlined" density="comfortable" />
            </v-col>
            <v-col cols="12" sm="6">
              <v-text-field v-model="form.phone" label="Phone" variant="outlined" density="comfortable" />
            </v-col>
          </v-row>
          <v-switch v-model="form.is_active" color="primary" label="Active" hide-details />
        </v-card-text>
        <v-card-actions class="px-6 pb-6">
          <v-spacer />
          <v-btn variant="text" @click="dialog = false">Cancel</v-btn>
          <v-btn color="primary" :loading="saving" @click="saveDoctor">Save</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <v-dialog v-model="confirmDialog" max-width="420">
      <v-card rounded="lg">
        <v-card-title>Deactivate doctor?</v-card-title>
        <v-card-text>
          This doctor will no longer be available for new scheduling rules or bookings.
        </v-card-text>
        <v-card-actions>
          <v-spacer />
          <v-btn variant="text" @click="confirmDialog = false">Cancel</v-btn>
          <v-btn color="error" :loading="saving" @click="deactivateDoctor">Deactivate</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <v-snackbar v-model="snackbar.show" :color="snackbar.color" timeout="3000">
      {{ snackbar.text }}
    </v-snackbar>
  </div>
</template>
