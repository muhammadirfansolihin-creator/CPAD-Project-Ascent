<template>
  <div>
    <nav class="navbar">
      <span class="navbar-brand">CampusEats</span>
      <div class="navbar-actions">
        <button
          v-if="store.myVendor"
          :class="['btn btn-sm', store.myVendor.isOpen ? 'btn-success' : 'btn-ghost']"
          @click="toggleOpen"
        >
          {{ store.myVendor.isOpen ? 'Open' : 'Closed' }}
        </button>
        <button class="btn btn-ghost btn-sm" @click="auth.logout()">Sign Out</button>
      </div>
    </nav>

    <div class="page" style="padding-bottom:80px">
      <div v-if="loading" class="loading"><div class="spinner"></div></div>

      <template v-else>
        <h2 class="page-title" style="margin-bottom:1rem">Dashboard</h2>

        <!-- Stat cards -->
        <div class="grid-4" style="margin-bottom:1.5rem">
          <div class="stat-card">
            <div class="stat-value">RM {{ revenue }}</div>
            <div class="stat-label">Daily Revenue</div>
          </div>
          <div class="stat-card">
            <div class="stat-value">{{ store.dashboard?.totalOrders ?? 0 }}</div>
            <div class="stat-label">Total Orders</div>
          </div>
          <div class="stat-card">
            <div class="stat-value">{{ store.dashboard?.activeOrders ?? 0 }}</div>
            <div class="stat-label">Active Orders</div>
          </div>
          <div class="stat-card">
            <div class="stat-value">{{ avgRating }}</div>
            <div class="stat-label">Avg Rating</div>
          </div>
        </div>

        <!-- Live order queue -->
        <div class="card">
          <div class="card-header">Live Order Queue</div>
          <div class="card-body" style="padding:0.75rem">
            <div v-if="!liveQueue.length" class="empty" style="padding:1.5rem">
              <div class="empty-icon">🍽️</div>
              <p>No active orders right now</p>
            </div>
            <div v-for="order in liveQueue" :key="order.id" class="kanban-card" style="margin-bottom:0.5rem">
              <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:0.5rem">
                <div>
                  <div style="font-weight:700;font-size:0.88rem">Order #{{ order.id }}</div>
                  <div class="text-muted" style="font-size:0.78rem;margin-top:0.1rem">{{ order.studentName }}</div>
                  <div class="text-muted" style="font-size:0.78rem">Pickup: {{ order.pickupTime }}</div>
                </div>
                <span :class="['badge', `badge-${order.status}`]">{{ order.status }}</span>
              </div>
              <div style="margin-top:0.5rem;font-size:0.8rem;color:var(--color-muted)">
                {{ formatItems(order.items) }}
              </div>
              <div style="display:flex;gap:0.4rem;margin-top:0.6rem;flex-wrap:wrap">
                <button
                  v-if="order.status === 'placed'"
                  class="btn btn-primary btn-sm"
                  @click="advance(order.id, 'preparing')"
                >Start Preparing</button>
                <button
                  v-if="order.status === 'preparing'"
                  class="btn btn-success btn-sm"
                  @click="advance(order.id, 'ready')"
                >Mark Ready</button>
                <button
                  v-if="order.status === 'ready'"
                  class="btn btn-ghost btn-sm"
                  @click="advance(order.id, 'collected')"
                >Collected</button>
              </div>
            </div>
          </div>
        </div>
      </template>
    </div>

    <!-- Bottom nav -->
    <nav class="bottom-nav">
      <router-link to="/vendor" class="bottom-nav-item active">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>
        Dashboard
      </router-link>
      <router-link to="/vendor/orders" class="bottom-nav-item">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2"/><rect x="9" y="3" width="6" height="4" rx="1"/><path d="M9 12h6M9 16h4"/></svg>
        Orders
      </router-link>
      <router-link to="/vendor/menu" class="bottom-nav-item">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2C8 2 5 5.5 5 9c0 3 1.5 5.5 4 7v3a1 1 0 001 1h4a1 1 0 001-1v-3c2.5-1.5 4-4 4-7 0-3.5-3-7-7-7z"/></svg>
        Menu
      </router-link>
    </nav>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useVendorOrdersStore } from '@/stores/vendorOrders'

const auth    = useAuthStore()
const store   = useVendorOrdersStore()
const loading = ref(true)

const revenue  = computed(() => (store.dashboard?.dailyRevenue ?? 0).toFixed(2))
const avgRating = computed(() => store.dashboard?.avgRating ? Number(store.dashboard.avgRating).toFixed(1) : 'N/A')
const liveQueue = computed(() => (store.dashboard?.liveQueue ?? []).filter(o => o.status !== 'collected'))

function formatItems(items) {
  if (!items?.length) return ''
  return items.map(i => `${i.quantity}x ${i.name}`).join(', ')
}

async function toggleOpen() {
  if (store.myVendor) await store.toggleOpen(store.myVendor.id)
}

async function advance(orderId, status) {
  await store.updateStatus(orderId, status)
}

onMounted(async () => {
  try {
    await store.fetchMyVendor(auth.user?.id)
    store.startPolling(10000)
  } finally {
    loading.value = false
  }
})

onUnmounted(() => store.stopPolling())
</script>