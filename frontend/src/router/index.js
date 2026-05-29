import { createRouter, createWebHistory } from 'vue-router'
import { useAuth } from '../composables/useAuth'
import LoginView from '../views/auth/LoginView.vue'
import AdminLayout from '../layouts/AdminLayout.vue'
import AdminDashboardView from '../views/admin/AdminDashboardView.vue'
import AdminServicesView from '../views/admin/AdminServicesView.vue'
import AdminDoctorsView from '../views/admin/AdminDoctorsView.vue'
import AdminAvailabilityView from '../views/admin/AdminAvailabilityView.vue'

const routes = [
  {
    path: '/',
    redirect: '/admin/dashboard',
  },
  {
    path: '/login',
    name: 'login',
    component: LoginView,
  },
  {
    path: '/admin',
    component: AdminLayout,
    meta: { requiresAuth: true, role: 'admin' },
    children: [
      {
        path: '',
        redirect: '/admin/dashboard',
      },
      {
        path: 'dashboard',
        name: 'admin.dashboard',
        component: AdminDashboardView,
      },
      {
        path: 'services',
        name: 'admin.services',
        component: AdminServicesView,
      },
      {
        path: 'doctors',
        name: 'admin.doctors',
        component: AdminDoctorsView,
      },
      {
        path: 'availability',
        name: 'admin.availability',
        component: AdminAvailabilityView,
      },
    ],
  },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
})

router.beforeEach(async (to) => {
  const { state, fetchMe } = useAuth()

  if (!to.meta.requiresAuth) {
    return true
  }

  if (!state.token) {
    return { name: 'login', query: { redirect: to.fullPath } }
  }

  if (!state.user) {
    try {
      await fetchMe()
    } catch {
      localStorage.removeItem('clinic_auth_token')
      state.token = null
      state.user = null
      return { name: 'login', query: { redirect: to.fullPath } }
    }
  }

  if (to.meta.role && state.user?.role !== to.meta.role) {
    return { name: 'login' }
  }

  return true
})

export default router
