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
    .toUpperCase() || 'DR'
})

const navItems = [
  { title: 'Dashboard', icon: 'mdi-view-dashboard-outline', to: '/doctor/dashboard' },
]

async function signOut() {
  await logout()
  await router.push('/login')
}
</script>

<template>
  <v-navigation-drawer width="284" color="surface" class="app-sidebar" permanent>
    <div class="pa-5 pb-4">
      <div class="d-flex align-center ga-3">
        <div class="brand-mark">
          <v-icon icon="mdi-stethoscope" />
        </div>
        <div>
          <div class="font-weight-bold">Doctor Portal</div>
          <div class="text-caption text-medium-emphasis">Assigned schedule</div>
        </div>
      </div>
    </div>

    <v-divider />

    <div class="sidebar-section-label px-5 pt-5 pb-2">Workspace</div>
    <v-list nav density="comfortable" class="pa-3">
      <v-list-item
        v-for="item in navItems"
        :key="item.to"
        :to="item.to"
        :prepend-icon="item.icon"
        :title="item.title"
        rounded="lg"
        color="primary"
      />
    </v-list>

    <template #append>
      <div class="pa-4 ma-3 panel-card rounded-lg">
        <div class="d-flex align-center ga-3 mb-4">
          <v-avatar color="primary" variant="tonal" size="38">{{ initials }}</v-avatar>
          <div class="overflow-hidden">
            <div class="text-body-2 font-weight-medium text-truncate">{{ state.user?.name }}</div>
            <div class="text-caption text-medium-emphasis text-truncate">{{ state.user?.email }}</div>
          </div>
        </div>
        <div class="role-badge d-inline-flex mb-3">Doctor</div>
        <v-btn variant="tonal" color="primary" block prepend-icon="mdi-logout" @click="signOut">
          Sign out
        </v-btn>
      </div>
    </template>
  </v-navigation-drawer>

  <v-app-bar flat border color="surface" height="68">
    <v-app-bar-title>
      <div class="topbar-title">Clinic appointment system</div>
      <div class="text-caption text-medium-emphasis">Doctor workspace</div>
    </v-app-bar-title>
  </v-app-bar>

  <v-main class="app-shell">
    <v-container fluid class="pa-6 app-container">
      <router-view />
    </v-container>
  </v-main>
</template>
