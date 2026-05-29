<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import api, { getErrorMessage } from '../../services/api'

const services = ref([])
const loading = ref(false)
const saving = ref(false)
const dialog = ref(false)
const confirmDialog = ref(false)
const selectedService = ref(null)
const snackbar = reactive({
  show: false,
  text: '',
  color: 'success',
})

const emptyForm = {
  name: '',
  description: '',
  duration_minutes: 30,
  price: null,
  is_active: true,
}

const form = reactive({ ...emptyForm })
const isEditing = computed(() => Boolean(selectedService.value))

function notify(text, color = 'success') {
  snackbar.text = text
  snackbar.color = color
  snackbar.show = true
}

function resetForm() {
  Object.assign(form, emptyForm)
  selectedService.value = null
}

async function loadServices() {
  loading.value = true

  try {
    const { data } = await api.get('/admin/services')
    services.value = data.data
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

function openEditDialog(service) {
  selectedService.value = service
  Object.assign(form, {
    name: service.name,
    description: service.description || '',
    duration_minutes: service.duration_minutes,
    price: service.price,
    is_active: service.is_active,
  })
  dialog.value = true
}

async function saveService() {
  saving.value = true

  const payload = {
    ...form,
    duration_minutes: Number(form.duration_minutes),
    price: form.price === '' || form.price === null ? null : Number(form.price),
  }

  try {
    if (isEditing.value) {
      await api.put(`/admin/services/${selectedService.value.id}`, payload)
      notify('Service updated.')
    } else {
      await api.post('/admin/services', payload)
      notify('Service created.')
    }

    dialog.value = false
    resetForm()
    await loadServices()
  } catch (err) {
    notify(getErrorMessage(err), 'error')
  } finally {
    saving.value = false
  }
}

function askDeactivate(service) {
  selectedService.value = service
  confirmDialog.value = true
}

async function deactivateService() {
  saving.value = true

  try {
    await api.delete(`/admin/services/${selectedService.value.id}`)
    notify('Service deactivated.')
    confirmDialog.value = false
    selectedService.value = null
    await loadServices()
  } catch (err) {
    notify(getErrorMessage(err), 'error')
  } finally {
    saving.value = false
  }
}

onMounted(loadServices)
</script>

<template>
  <div>
    <div class="d-flex flex-wrap align-center justify-space-between ga-4 mb-6">
      <div>
        <div class="page-title">Services</div>
        <div class="page-subtitle mt-1">Manage bookable clinic services and default durations.</div>
      </div>
      <v-btn color="primary" prepend-icon="mdi-plus" @click="openCreateDialog">
        New service
      </v-btn>
    </div>

    <v-card rounded="lg" elevation="1">
      <v-table>
        <thead>
          <tr>
            <th>Name</th>
            <th>Duration</th>
            <th>Price</th>
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
          <tr v-else-if="services.length === 0">
            <td colspan="5" class="table-empty">No services yet.</td>
          </tr>
          <tr v-for="service in services" v-else :key="service.id">
            <td>
              <div class="font-weight-medium">{{ service.name }}</div>
              <div v-if="service.description" class="text-caption text-medium-emphasis">
                {{ service.description }}
              </div>
            </td>
            <td>{{ service.duration_minutes }} min</td>
            <td>{{ service.price ? `PHP ${service.price}` : 'Not set' }}</td>
            <td>
              <v-chip
                :color="service.is_active ? 'success' : 'default'"
                size="small"
                variant="tonal"
              >
                {{ service.is_active ? 'Active' : 'Inactive' }}
              </v-chip>
            </td>
            <td class="text-right">
              <v-btn icon="mdi-pencil" variant="text" size="small" @click="openEditDialog(service)" />
              <v-btn
                icon="mdi-close-circle-outline"
                variant="text"
                size="small"
                color="error"
                :disabled="!service.is_active"
                @click="askDeactivate(service)"
              />
            </td>
          </tr>
        </tbody>
      </v-table>
    </v-card>

    <v-dialog v-model="dialog" max-width="620">
      <v-card rounded="lg">
        <v-card-title class="px-6 pt-6">
          {{ isEditing ? 'Edit service' : 'New service' }}
        </v-card-title>
        <v-card-text class="px-6">
          <v-text-field v-model="form.name" label="Name" variant="outlined" density="comfortable" />
          <v-textarea v-model="form.description" label="Description" variant="outlined" density="comfortable" rows="3" />
          <v-row>
            <v-col cols="12" sm="6">
              <v-text-field
                v-model="form.duration_minutes"
                label="Duration minutes"
                type="number"
                min="5"
                variant="outlined"
                density="comfortable"
              />
            </v-col>
            <v-col cols="12" sm="6">
              <v-text-field
                v-model="form.price"
                label="Price"
                type="number"
                min="0"
                prefix="PHP"
                variant="outlined"
                density="comfortable"
              />
            </v-col>
          </v-row>
          <v-switch v-model="form.is_active" color="primary" label="Active" hide-details />
        </v-card-text>
        <v-card-actions class="px-6 pb-6">
          <v-spacer />
          <v-btn variant="text" @click="dialog = false">Cancel</v-btn>
          <v-btn color="primary" :loading="saving" @click="saveService">Save</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <v-dialog v-model="confirmDialog" max-width="420">
      <v-card rounded="lg">
        <v-card-title>Deactivate service?</v-card-title>
        <v-card-text>
          This service will no longer be available for new bookings.
        </v-card-text>
        <v-card-actions>
          <v-spacer />
          <v-btn variant="text" @click="confirmDialog = false">Cancel</v-btn>
          <v-btn color="error" :loading="saving" @click="deactivateService">Deactivate</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <v-snackbar v-model="snackbar.show" :color="snackbar.color" timeout="3000">
      {{ snackbar.text }}
    </v-snackbar>
  </div>
</template>
