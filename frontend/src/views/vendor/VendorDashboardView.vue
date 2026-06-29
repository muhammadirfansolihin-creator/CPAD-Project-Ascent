<template>
  <div>
    <nav class="navbar">
      <div>
        <div class="navbar-brand"><img src="/favicon.png" alt="CampusEats Logo" class="navbar-brand-icon" /> CampusEats</div>
      </div>
      <div class="navbar-actions">
        <button v-if="store.myVendor" :class="['open-toggle-btn', store.myVendor.isOpen?'is-open':'']" @click="toggleOpen">
          <span :class="store.myVendor.isOpen?'open-dot':'closed-dot'"></span>
          {{ store.myVendor.isOpen ? 'Open' : 'Closed' }}
        </button>
        
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

        <router-link to="/vendor/profile" class="navbar-icon-btn">👤</router-link>
      </div>
    </nav>

    <div class="page">
      <div v-if="loading" class="loading"><div class="spinner"></div></div>
      <template v-else-if="!store.myVendor">
        <!-- New vendor pending approval -->
        <div style="text-align:center;padding:3rem 1rem">
          <div style="font-size:3rem;margin-bottom:1rem">⏳</div>
          <div style="font-size:1.3rem;font-weight:800;margin-bottom:0.5rem">Account Pending Approval</div>
          <div style="color:var(--color-muted);font-size:0.9rem;max-width:320px;margin:0 auto">
            Your vendor account has been created and is awaiting admin approval. You will be able to manage your stall once approved.
          </div>
        </div>
      </template>
      <template v-else>
        <div style="display:flex;justify-content:space-between;align-items:baseline;margin-bottom:1.25rem">
          <div>
            <div class="page-title" style="margin-bottom:0.1rem">Dashboard</div>
            <div style="font-size:0.8rem;color:var(--color-muted)">{{ today }}</div>
          </div>
        </div>

        <!-- Stat grid -->
        <div class="grid-2" style="margin-bottom:1.5rem;gap:0.75rem">
          <div class="stat-card">
            <div class="stat-label">TODAY'S REVENUE</div>
            <div class="stat-value" style="color:var(--color-primary)">RM {{ revenue }}</div>
            <div class="stat-change up">↑ +18% vs yesterday</div>
          </div>
          <div class="stat-card">
            <div class="stat-label">ORDERS TODAY</div>
            <div class="stat-value" style="color:var(--color-success)">{{ totalOrders }}</div>
            <div class="stat-change up">↑ 5 more than usual</div>
          </div>
          <div class="stat-card">
            <div class="stat-label">ACTIVE ORDERS</div>
            <div class="stat-value" style="color:#1d4ed8">{{ activeOrders }}</div>
            <div class="stat-sub">In queue now</div>
          </div>
          <div class="stat-card">
            <div class="stat-label">AVG RATING</div>
            <div class="stat-value" style="color:#d97706">{{ avgRating }} ★</div>
            <div class="stat-sub">{{ store.dashboard?.reviewCount ?? 0 }} reviews total</div>
          </div>
        </div>

        <!-- Live Order Queue -->
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:0.75rem">
          <div style="font-weight:800;font-size:1rem">Live Order Queue</div>
          <router-link to="/vendor/orders" style="font-size:0.8rem;font-weight:600;color:var(--color-primary);text-decoration:none">Full board →</router-link>
        </div>

        <div class="kanban">
          <div v-for="col in columns" :key="col.status" class="kanban-col">
            <div class="kanban-col-header">
              <div style="display:flex;align-items:center;gap:0.4rem">
                <span :style="`width:8px;height:8px;border-radius:50%;background:${col.color};display:inline-block`"></span>
                {{ col.label }}
              </div>
              <span class="kanban-col-count">{{ effectiveLiveQueue.filter(o=>o.status===col.status).length }}</span>
            </div>
            <div class="kanban-col-body">
              <div v-if="!effectiveLiveQueue.filter(o=>o.status===col.status).length" style="text-align:center;padding:1rem;font-size:0.78rem;color:var(--color-muted)">Empty</div>
              <div v-for="order in effectiveLiveQueue.filter(o=>o.status===col.status)" :key="order.id" class="kanban-card">
                <div style="display:flex;justify-content:space-between;align-items:flex-start">
                  <div>
                    <div class="kanban-card-id">#CE-{{ String(order.id).padStart(4,'0') }}</div>
                    <div class="kanban-card-customer">{{ order.customerName }}</div>
                  </div>
                  <span v-if="isUrgent(order)" class="urgent-badge">URGENT</span>
                </div>
                <div class="kanban-card-items">{{ formatItems(order.items) }}</div>
                <div class="kanban-card-price">RM {{ Number(order.total||0).toFixed(2) }}</div>
                <div class="kanban-card-pickup">🕐 {{ order.pickupAt }}</div>
                <button v-if="order.status==='placed'" class="kanban-action-btn btn-start-prep" @click="advance(order.id,'preparing')">Start Preparing →</button>
                <button v-if="order.status==='preparing'" class="kanban-action-btn btn-mark-ready" @click="advance(order.id,'ready')">Mark Ready ✓</button>
                <button v-if="order.status==='ready'" class="kanban-action-btn btn-mark-collected" @click="advance(order.id,'collected')">Mark Collected ✓</button>
              </div>
            </div>
          </div>
        </div>
      </template>
    </div>

    <nav class="bottom-nav">
      <router-link to="/vendor" class="bottom-nav-item">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>
        Dashboard
      </router-link>
      <router-link to="/vendor/orders" class="bottom-nav-item">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2"/><rect x="9" y="3" width="6" height="4" rx="1"/></svg>
        Orders
      </router-link>
      <router-link to="/vendor/menu" class="bottom-nav-item">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 2v6M15 2v6M3 10h18M5 22h14a2 2 0 002-2v-8H3v8a2 2 0 002 2z"/></svg>
        Menu
      </router-link>
    </nav>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useVendorOrdersStore } from '@/stores/vendorOrders'
import { useNotificationStore } from '@/stores/notifications'

const auth  = useAuthStore()
const store = useVendorOrdersStore()
const notif = useNotificationStore()
const showNotif = ref(false)
const loading = ref(true)

const today = new Date().toLocaleDateString('en-MY', { weekday:'long', day:'numeric', month:'long', year:'numeric' })

const columns = [
  { status:'placed',    label:'Placed',    color:'#3b82f6' },
  { status:'preparing', label:'Preparing', color:'#f59e0b' },
  { status:'ready',     label:'Ready',     color:'#10b981' },
  { status:'collected', label:'Collected', color:'#6b7280' },
]

const revenue    = computed(() => (store.dashboard?.dailyRevenue ?? 0).toFixed(2))
const totalOrders = computed(() => store.dashboard?.totalOrders ?? 0)
const activeOrders = computed(() => Array.isArray(store.dashboard?.activeOrders) ? store.dashboard.activeOrders.length : 0)
const avgRating   = computed(() => store.dashboard?.avgRating ? Number(store.dashboard.avgRating).toFixed(1) : 'N/A')

const localOrders = ref({})

const effectiveLiveQueue = computed(() => {
  const serverQueue = store.dashboard?.liveQueue ?? []
  const merged = serverQueue.map(o => localOrders.value[o.id] ? { ...o, ...localOrders.value[o.id] } : o)
  Object.values(localOrders.value).forEach(lo => {
    if (!merged.find(o => o.id === lo.id)) merged.push(lo)
  })
  return merged
})

function isUrgent(order) {
  if (!order.pickupAt) return false
  const now = new Date(); const pickup = new Date(); const [h,m] = order.pickupAt.split(':')
  pickup.setHours(+h, +m, 0, 0)
  return (pickup - now) < 10 * 60 * 1000
}

function formatItems(items) { return (items||[]).map(i=>`+${i.quantity||i.qty||1} ${i.name}`).join(' ') }

function toggleNotif() {
  showNotif.value = !showNotif.value
}

function handleNotifClick(n) {
  notif.markAsRead(n.id)
  showNotif.value = false
}

async function toggleOpen() { if (store.myVendor) await store.toggleOpen(store.myVendor.id) }

async function advance(id, status) {
  const current = effectiveLiveQueue.value.find(o => o.id === id)
  if (current) localOrders.value[id] = { ...current, status }
  await store.updateStatus(id, status)
}

onMounted(async () => {
  try { await store.fetchMyVendor(); store.startPolling(10000) } finally { loading.value = false }
  notif.fetchNotifications()
})
onUnmounted(() => store.stopPolling())
</script>
