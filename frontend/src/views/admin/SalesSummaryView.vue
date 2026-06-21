<template>
  <div>
    <nav class="navbar">
      <span class="navbar-brand">CampusEats Admin</span>
      <button class="btn btn-ghost btn-sm" @click="auth.logout()">Sign Out</button>
    </nav>

    <div class="admin-tabs">
      <router-link to="/admin"           class="admin-tab active">Sales Summary</router-link>
      <router-link to="/admin/vendors"   class="admin-tab">Vendors</router-link>
      <router-link to="/admin/disputes"  class="admin-tab">Disputes</router-link>
    </div>

    <div class="page" style="padding-bottom:2rem">
      <div v-if="loading" class="loading"><div class="spinner"></div></div>
      <template v-else-if="store.analytics">
        <!-- Metric cards -->
        <div class="grid-4" style="margin-bottom:1.5rem">
          <div class="stat-card">
            <div class="stat-value">{{ store.analytics.totalOrders }}</div>
            <div class="stat-label">Total Orders</div>
          </div>
          <div class="stat-card">
            <div class="stat-value">RM {{ store.analytics.totalRevenue.toFixed(2) }}</div>
            <div class="stat-label">Total Revenue</div>
          </div>
          <div class="stat-card">
            <div class="stat-value">{{ store.analytics.activeVendors }}</div>
            <div class="stat-label">Active Vendors</div>
          </div>
          <div class="stat-card">
            <div class="stat-value" :style="store.analytics.openDisputes ? 'color:var(--color-danger)' : ''">
              {{ store.analytics.openDisputes }}
            </div>
            <div class="stat-label">Open Disputes</div>
          </div>
        </div>

        <!-- Sales by vendor -->
        <div class="card">
          <div class="card-header">Sales by Vendor</div>
          <div class="card-body">
            <div v-if="!store.analytics.vendorRevenue.length" class="empty" style="padding:1rem">
              <p>No revenue data yet</p>
            </div>
            <div v-for="v in store.analytics.vendorRevenue" :key="v.vendorId" style="margin-bottom:1rem">
              <div style="display:flex;justify-content:space-between;margin-bottom:0.35rem">
                <span style="font-weight:700;font-size:0.88rem">{{ v.vendorName }}</span>
                <span style="font-size:0.85rem;font-weight:700;color:var(--color-primary)">RM {{ v.revenue.toFixed(2) }}</span>
              </div>
              <div class="progress-bar">
                <div class="progress-fill" :style="`width:${barWidth(v.revenue)}%`"></div>
              </div>
              <div class="text-muted" style="font-size:0.75rem;margin-top:0.2rem">{{ v.orderCount }} orders</div>
            </div>
          </div>
        </div>
      </template>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useAdminDashboardStore } from '@/stores/adminDashboard'

const auth    = useAuthStore()
const store   = useAdminDashboardStore()
const loading = ref(true)

const maxRevenue = computed(() => Math.max(...(store.analytics?.vendorRevenue.map(v => v.revenue) || [1]), 1))
function barWidth(rev) { return Math.round((rev / maxRevenue.value) * 100) }

onMounted(async () => { try { await store.fetchAnalytics() } finally { loading.value = false } })
</script>
