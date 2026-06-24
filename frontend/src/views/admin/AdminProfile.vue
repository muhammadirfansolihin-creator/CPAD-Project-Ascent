<template>
  <div>
    <nav class="navbar">
      <div class="navbar-brand"><span class="navbar-brand-icon">🍴</span> CampusEats</div>
      <div class="navbar-actions">
        <button class="navbar-icon-btn" @click="$router.push('/admin')" title="Back">←</button>
      </div>
    </nav>

    <div class="admin-tabs">
      <router-link to="/admin"          class="admin-tab">Sales Summary</router-link>
      <router-link to="/admin/vendors"  class="admin-tab">Vendors</router-link>
      <router-link to="/admin/disputes" class="admin-tab">Disputes</router-link>
    </div>

    <div class="page">
      <!-- Identity -->
      <div class="card profile-identity-card">
        <div class="card-body profile-identity-body">
          <div class="profile-avatar">{{ initials }}</div>
          <div class="profile-identity-text">
            <div class="font-bold profile-name">{{ auth.user?.name }}</div>
            <div class="text-muted profile-email">{{ auth.user?.email }}</div>
            <div class="text-muted" style="font-size:0.75rem;margin-top:0.2rem">Administrator</div>
          </div>
        </div>
      </div>

      <button class="btn btn-ghost btn-block profile-logout-btn" @click="handleLogout">
        Log Out
      </button>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const auth   = useAuthStore()

const initials = computed(() => (auth.user?.name || '?').charAt(0).toUpperCase())

function handleLogout() {
  auth.logout()
  router.push('/login')
}
</script>