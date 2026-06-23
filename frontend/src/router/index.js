import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

import LoginView from '@/views/LoginView.vue'
import HomeView from '@/views/student/HomeView.vue'
import VendorMenuView from '@/views/student/VendorMenuView.vue'
import CartView from '@/views/student/CartView.vue'
import OrderHistoryView from '@/views/student/OrderHistoryView.vue'
import StudentProfile from '@/views/student/StudentProfile.vue'
import VendorDashboardView from '@/views/vendor/VendorDashboardView.vue'
import VendorOrdersView from '@/views/vendor/VendorOrdersView.vue'
import VendorMenuMgmtView from '@/views/vendor/VendorMenuMgmtView.vue'
import VendorProfile from '@/views/vendor/VendorProfile.vue'
import SalesSummaryView from '@/views/admin/SalesSummaryView.vue'
import AdminVendorsView from '@/views/admin/AdminVendorsView.vue'
import AdminDisputesView from '@/views/admin/AdminDisputesView.vue'
import VendorSalesView from '@/views/admin/VendorSalesView.vue'
import AdminProfile from '@/views/admin/AdminProfile.vue'

const routes = [
  { path: '/login', component: LoginView, meta: { public: true } },
  // Student
  { path: '/',              component: HomeView,         meta: { role: 'student' } },
  { path: '/vendors/:id',   component: VendorMenuView,   meta: { role: 'student' } },
  { path: '/cart',          component: CartView,         meta: { role: 'student' } },
  { path: '/orders',        component: OrderHistoryView, meta: { role: 'student' } },
  { path: '/profile',       component: StudentProfile,   meta: { role: 'student' } },
  { path: '/profile/edit',  component: () => import('@/views/student/EditProfileView.vue'), meta: { role: 'student' } },
  { path: '/profile/reviews', component: () => import('@/views/student/MyReviewsView.vue'), meta: { role: 'student' } },
  // Vendor
  { path: '/vendor',        component: VendorDashboardView, meta: { role: 'vendor' } },
  { path: '/vendor/orders', component: VendorOrdersView,    meta: { role: 'vendor' } },
  { path: '/vendor/menu',   component: VendorMenuMgmtView,  meta: { role: 'vendor' } },
  { path: '/vendor/profile', component: VendorProfile, meta: { role: 'vendor' } },
  // Admin
  { path: '/admin',           component: SalesSummaryView, meta: { role: 'admin' } },
  { path: '/admin/vendors',   component: AdminVendorsView,  meta: { role: 'admin' } },
  { path: '/admin/disputes',  component: AdminDisputesView, meta: { role: 'admin' } },
  { path: '/admin/vendor-sales', component: VendorSalesView, meta: { role: 'admin' } },
  { path: '/admin/profile', component: AdminProfile, meta: { role: 'admin' } },
]

const router = createRouter({ history: createWebHistory(), routes })

router.beforeEach((to) => {
  const auth = useAuthStore()
  if (to.meta.public) return true
  if (!auth.isLoggedIn) return '/login'
  if (to.meta.role && auth.role !== to.meta.role) {
    if (auth.role === 'student') return '/'
    if (auth.role === 'vendor')  return '/vendor'
    if (auth.role === 'admin')   return '/admin'
  }
  return true
})

export default router
