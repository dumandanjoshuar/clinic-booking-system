import { createRouter, createWebHistory } from 'vue-router'
import { useAuth } from '../composables/useAuth'

const LoginView = () => import('../views/auth/LoginView.vue')
const ChangePasswordView = () => import('../views/auth/ChangePasswordView.vue')
const AdminLayout = () => import('../layouts/AdminLayout.vue')
const AdminDashboardView = () => import('../views/admin/AdminDashboardView.vue')
const AdminServicesView = () => import('../views/admin/AdminServicesView.vue')
const AdminDoctorsView = () => import('../views/admin/AdminDoctorsView.vue')
const AdminAvailabilityView = () => import('../views/admin/AdminAvailabilityView.vue')
const AdminAppointmentsView = () => import('../views/admin/AdminAppointmentsView.vue')
const AdminAppointmentDetailView = () => import('../views/admin/AdminAppointmentDetailView.vue')
const DoctorLayout = () => import('../layouts/DoctorLayout.vue')
const DoctorDashboardView = () => import('../views/doctor/DoctorDashboardView.vue')
const DoctorScheduleView = () => import('../views/doctor/DoctorScheduleView.vue')
const DoctorAppointmentDetailView = () => import('../views/doctor/DoctorAppointmentDetailView.vue')
const BookingView = () => import('../views/public/BookingView.vue')
const BookingSuccessView = () => import('../views/public/BookingSuccessView.vue')

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
    path: '/change-password',
    name: 'change-password',
    component: ChangePasswordView,
    meta: { requiresAuth: true },
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

  if (state.user?.must_change_password && to.name !== 'change-password') {
    return { name: 'change-password' }
  }

  if (!state.user?.must_change_password && to.name === 'change-password') {
    return state.user?.role === 'doctor' ? { name: 'doctor.dashboard' } : { name: 'admin.dashboard' }
  }

  return true
})

export default router
