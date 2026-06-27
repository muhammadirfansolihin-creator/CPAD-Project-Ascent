<template>
  <div>
    <nav class="navbar">
      <div class="navbar-brand"><span class="navbar-brand-icon">🍴</span> CampusEats</div>
      <div class="navbar-actions">
        
        <div style="position:relative">
          <button class="navbar-icon-btn" @click="toggleNotif" title="Notifications">
            🔔
            <span v-if="notif.unreadCount" class="notif-badge">{{ notif.unreadCount }}</span>
          </button>

          <div v-if="showNotif" class="notif-dropdown">
            <div class="notif-dropdown-header">Notifications</div>
            <div v-if="!notif.notifications.length" class="notif-empty">No notifications yet</div>
            <div v-for="n in notif.notifications" :key="n.id" @click="handleNotifClick(n)"
              :class="['notif-item', { unread: !n.isRead }]">
              <div>{{ n.message }}</div>
              <div class="notif-item-time">{{ n.createdAt }}</div>
            </div>
          </div>
        </div> 

        <router-link to="/admin/profile" class="navbar-icon-btn">👤</router-link>
      </div>
    </nav>

    <div class="admin-tabs">
      <router-link to="/admin"          class="admin-tab">Sales Summary</router-link>
      <router-link to="/admin/vendors"  class="admin-tab">Vendors</router-link>
      <router-link to="/admin/disputes" class="admin-tab">Disputes</router-link>
    </div>

    <div class="page" style="padding-bottom:2rem">
      <div v-if="loading" class="loading"><div class="spinner"></div></div>
      <template v-else-if="store.analytics">
        <!-- Stats -->
        <div class="grid-2" style="margin-bottom:1.25rem">
          <div class="stat-card">
            <div class="stat-label">ORDERS TODAY</div>
            <div class="stat-value">{{ store.analytics.totalOrders }}</div>
            <div class="stat-change up">↑ +10% vs yesterday</div>
          </div>
          <div class="stat-card">
            <div class="stat-label">REVENUE TODAY</div>
            <div class="stat-value" style="color:var(--color-primary)">RM {{ store.analytics.totalRevenue.toFixed(0) }}</div>
            <div class="stat-change up">↑ +12% vs yesterday</div>
          </div>
          <div class="stat-card">
            <div class="stat-label">ACTIVE VENDORS</div>
            <div class="stat-value" style="color:var(--color-success)">{{ store.analytics.activeVendors }}</div>
            <div class="stat-sub">1 pending approval</div>
          </div>
          <div class="stat-card">
            <div class="stat-label">OPEN DISPUTES</div>
            <div class="stat-value" :style="store.analytics.openDisputes?'color:var(--color-danger)':''">{{ store.analytics.openDisputes }}</div>
            <div class="stat-sub" :style="store.analytics.openDisputes?'color:var(--color-danger)':''">{{ store.analytics.openDisputes ? '1 urgent' : 'All clear' }}</div>
          </div>
        </div>

        <!-- Sales by vendor -->
        <div class="card">
          <div class="card-header" style="display:flex;justify-content:space-between;align-items:center">
            <span>Sales by Vendor</span>
            <router-link to="/admin/vendor-sales" class="section-row-link" style="font-weight:600;font-size:0.8rem">See all →</router-link>
          </div>
          <div class="card-body">
            <div v-if="!store.analytics.vendorRevenue.length" class="empty" style="padding:1rem"><p>No revenue data yet</p></div>
            <div v-for="v in store.analytics.vendorRevenue" :key="v.vendorId" style="margin-bottom:1.25rem">
              <div style="display:flex;justify-content:space-between;margin-bottom:0.4rem">
                <span style="font-weight:700;font-size:0.9rem">{{ v.vendorName }}</span>
                <span style="font-weight:700;color:var(--color-primary)">RM {{ v.revenue.toFixed(0) }}</span>
              </div>
              <div class="progress-bar" style="height:8px">
                <div class="progress-fill" :style="`width:${barWidth(v.revenue)}%`"></div>
              </div>
              <div style="font-size:0.72rem;color:var(--color-muted);margin-top:0.2rem">{{ v.orderCount }} orders</div>
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
import { useNotificationStore } from '@/stores/notifications'

const auth    = useAuthStore()
const store   = useAdminDashboardStore()
const loading = ref(true)
const notif = useNotificationStore()
const showNotif = ref(false)

const maxRevenue = computed(() => Math.max(...(store.analytics?.vendorRevenue.map(v => v.revenue) || [1]), 1))
function barWidth(rev) { return Math.round((rev / maxRevenue.value) * 100) }

function toggleNotif() {
  showNotif.value = !showNotif.value
}

function handleNotifClick(n) {
  notif.markAsRead(n.id)
  showNotif.value = false
}

onMounted(async () => { 
  try { await store.fetchAnalytics() } finally { loading.value = false } 
  notif.fetchNotifications()
})
</script>
