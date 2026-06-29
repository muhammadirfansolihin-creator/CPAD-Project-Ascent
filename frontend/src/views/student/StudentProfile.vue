<template>
  <div>
    <nav class="navbar">
      <div>
        <div class="navbar-brand"><img src="/favicon.png" alt="CampusEats Logo" class="navbar-brand-icon" /> CampusEats</div>
      </div>
      <div class="navbar-actions">
        <router-link to="/cart" class="navbar-icon-btn">
          🛒
          <span v-if="cart.itemCount" class="badge-dot"></span>
        </router-link>
        <router-link to="/profile" class="navbar-icon-btn">👤</router-link>
      </div>
    </nav>

    <div class="page">
      <!-- Identity -->
      <div class="card profile-identity-card">
        <div class="card-body profile-identity-body">
          <div class="profile-avatar">{{ initials }}</div>
          <div class="profile-identity-text">
            <div class="font-bold profile-name">{{ auth.user?.name }}</div>
            <div class="text-muted profile-email">{{ auth.user?.email }}</div>
          </div>
        </div>
      </div>

      <!-- Stats -->
      <div v-if="loading" class="loading"><div class="spinner"></div></div>
      <div v-else class="grid-3 profile-stats-grid">
        <div class="stat-card profile-stat-card">
          <div class="stat-value">{{ stats.orderCount }}</div>
          <div class="stat-label profile-stat-label">Orders</div>
        </div>
        <div class="stat-card profile-stat-card">
          <div class="stat-value">RM {{ stats.totalSpent.toFixed(2) }}</div>
          <div class="stat-label profile-stat-label">Spent</div>
        </div>
        <div class="stat-card profile-stat-card">
          <div class="stat-value">{{ stats.points }}</div>
          <div class="stat-label profile-stat-label">Points</div>
        </div>
      </div>

      <!-- Menu -->
      <div class="card profile-menu-card">
        <router-link to="/profile/edit" class="profile-menu-item">
          <span class="profile-menu-label">👤 Edit Profile</span>
          <span class="text-muted">›</span>
        </router-link>
        <router-link to="/profile/reviews" class="profile-menu-item profile-menu-item-last">
          <span class="profile-menu-label">⭐ My Reviews</span>
          <span class="text-muted">›</span>
        </router-link>
      </div>

      <button class="btn btn-ghost btn-block profile-logout-btn" @click="handleLogout">
        Log Out
      </button>
    </div>

    <!-- Bottom nav -->
    <nav class="bottom-nav">
      <router-link to="/" class="bottom-nav-item">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
        Home
      </router-link>
      <router-link to="/orders" class="bottom-nav-item">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2"/><rect width="6" height="4" x="9" y="3" rx="2"/></svg>
        Orders
      </router-link>
    </nav>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'
import { useAuthStore } from '@/stores/auth'
import { useCartStore } from '@/stores/cart'

const router = useRouter()
const auth   = useAuthStore()
const cart   = useCartStore()

const loading = ref(true)
const copied  = ref(false)

const stats = ref({
  orderCount: 0,
  totalSpent: 0,
  points: 0
})

const initials = computed(() => (auth.user?.name || '?').charAt(0).toUpperCase())

function copyReferralCode() {
  navigator.clipboard.writeText(referral.value.code).then(() => {
    copied.value = true
    setTimeout(() => (copied.value = false), 2000)
  })
}

function handleLogout() {
  auth.logout()
  router.push('/login')
}

onMounted(async () => {
  try {
    const [statsRes] = await Promise.allSettled([
      axios.get('/api/profile/stats'),
    ])

    if (statsRes.status === 'fulfilled') {
      stats.value = statsRes.value.data
    }
  } finally {
    loading.value = false
  }
})
</script>