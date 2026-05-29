<script setup>
import { computed } from 'vue'
import { useRouter } from 'vue-router'
import { useAuth } from '../composables/useAuth'

const router = useRouter()
const { state, logout } = useAuth()

const initials = computed(() => {
  return state.user?.name
    ?.split(' ')
    .map((part) => part[0])
    .join('')
    .slice(0, 2)
    .toUpperCase() || 'CA'
})

const navItems = [
  { title: 'Dashboard', icon: 'mdi-view-dashboard-outline', to: '/admin/dashboard' },
  { title: 'Services', icon: 'mdi-medical-bag', to: '/admin/services' },
  { title: 'Doctors', icon: 'mdi-doctor', to: '/admin/doctors' },
  { title: 'Availability', icon: 'mdi-calendar-clock', to: '/admin/availability' },
]

async function signOut() {
  await logout()
  await router.push('/login')
}
</script>

<template>
  <v-navigation-drawer width="268" color="surface" border permanent>
    <div class="pa-5">
      <div class="d-flex align-center ga-3">
        <v-avatar color="primary" rounded="lg">
          <v-icon icon="mdi-hospital-building" />
        </v-avatar>
        <div>
          <div class="font-weight-bold">Clinic Booking</div>
          <div class="text-caption text-medium-emphasis">Admin workspace</div>
        </div>
      </div>
    </div>

    <v-divider />

    <v-list nav density="comfortable" class="pa-3">
      <v-list-item
        v-for="item in navItems"
        :key="item.to"
        :to="item.to"
        :prepend-icon="item.icon"
        :title="item.title"
        rounded="lg"
      />
    </v-list>

    <template #append>
      <div class="pa-4">
        <div class="d-flex align-center ga-3 mb-3">
          <v-avatar color="secondary" size="36">{{ initials }}</v-avatar>
          <div class="overflow-hidden">
            <div class="text-body-2 font-weight-medium text-truncate">{{ state.user?.name }}</div>
            <div class="text-caption text-medium-emphasis text-truncate">{{ state.user?.email }}</div>
          </div>
        </div>
        <v-btn
          variant="tonal"
          color="primary"
          block
          prepend-icon="mdi-logout"
          @click="signOut"
        >
          Sign out
        </v-btn>
      </div>
    </template>
  </v-navigation-drawer>

  <v-app-bar flat border color="surface">
    <v-app-bar-title class="font-weight-semibold">Clinic appointment MVP</v-app-bar-title>
  </v-app-bar>

  <v-main class="bg-background">
    <v-container fluid class="pa-6">
      <router-view />
    </v-container>
  </v-main>
</template>
