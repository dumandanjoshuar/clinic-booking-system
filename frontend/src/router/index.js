import { createRouter, createWebHistory } from 'vue-router'
import { useAuth } from '../composables/useAuth'
import LoginView from '../views/auth/LoginView.vue'
import AdminLayout from '../layouts/AdminLayout.vue'
import AdminDashboardView from '../views/admin/AdminDashboardView.vue'
import AdminServicesView from '../views/admin/AdminServicesView.vue'
import AdminDoctorsView from '../views/admin/AdminDoctorsView.vue'
import AdminAvailabilityView from '../views/admin/AdminAvailabilityView.vue'
import AdminAppointmentsView from '../views/admin/AdminAppointmentsView.vue'
import AdminAppointmentDetailView from '../views/admin/AdminAppointmentDetailView.vue'
import DoctorLayout from '../layouts/DoctorLayout.vue'
import DoctorDashboardView from '../views/doctor/DoctorDashboardView.vue'
import DoctorScheduleView from '../views/doctor/DoctorScheduleView.vue'
import DoctorAppointmentDetailView from '../views/doctor/DoctorAppointmentDetailView.vue'
import BookingView from '../views/public/BookingView.vue'
import BookingSuccessView from '../views/public/BookingSuccessView.vue'

const routes = [
  {
    path: '/',
    redirect: '/booking',
  },
  {
    path: '/booking',
    name: 'booking',
    component: BookingView,
  },
  {
    path: '/booking/success',
    name: 'booking.success',
    component: BookingSuccessView,
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
        path: 'appointments',
        name: 'admin.appointments',
        component: AdminAppointmentsView,
      },
      {
        path: 'appointments/:id',
        name: 'admin.appointments.show',
        component: AdminAppointmentDetailView,
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
  {
    path: '/doctor',
    component: DoctorLayout,
    meta: { requiresAuth: true, role: 'doctor' },
    children: [
      {
        path: '',
        redirect: '/doctor/dashboard',
      },
      {
        path: 'dashboard',
        name: 'doctor.dashboard',
        component: DoctorDashboardView,
      },
      {
        path: 'schedule',
        name: 'doctor.schedule',
        component: DoctorScheduleView,
      },
      {
        path: 'appointments/:id',
        name: 'doctor.appointments.show',
        component: DoctorAppointmentDetailView,
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
