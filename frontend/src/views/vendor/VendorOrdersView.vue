<template>
  <div>
    <nav class="navbar">
      <div>
        <div class="navbar-brand"><img src="/favicon.png" alt="CampusEats Logo" class="navbar-brand-icon" /> CampusEats</div>
        <div class="navbar-subtitle" style="padding-left:1.6rem">for Vendor</div>
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
      
       <div style="display:flex;gap:0.5rem;margin-bottom:1.25rem;margin-top:2rem">
        <button
          :class="['order-tab', activeView==='live' ? 'active' : '']"
          @click="activeView='live'"
          style="font-weight:700;padding:0.6rem 1.25rem">
          Live Orders
        </button>
        <button
          :class="['order-tab', activeView==='history' ? 'active' : '']"
          @click="activeView='history'"
          style="font-weight:700;padding:0.6rem 1.25rem">
          Order History
        </button>
      </div>

      <div v-if="loading" class="loading"><div class="spinner"></div></div>

      <template v-else>
        <!-- Kanban board -->
        <div v-if="activeView==='live'" class="kanban">
          <div v-for="col in columns" :key="col.status" class="kanban-col">
            <div class="kanban-col-header">
              <div style="display:flex;align-items:center;gap:0.4rem">
                <span :style="`width:8px;height:8px;border-radius:50%;background:${col.color};display:inline-block`"></span>
                {{ col.label }}
              </div>
              <span class="kanban-col-count">{{ filteredByStatus(col.status).length }}</span>
            </div>
            <div class="kanban-col-body">
              <div v-if="!filteredByStatus(col.status).length" style="text-align:center;padding:1rem;font-size:0.78rem;color:var(--color-muted)">Empty</div>
              <div v-for="order in filteredByStatus(col.status)" :key="order.id" class="kanban-card">
                <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:0.4rem">
                  <div>
                    <div style="display:flex;align-items:center;gap:0.4rem">
                      <div class="kanban-card-id">#CE-{{ String(order.id).padStart(4,'0') }}</div>
                      <span style="font-size:0.7rem;color:var(--color-muted)">{{ formatTime(order.createdAt) }}</span>
                    </div>
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

        <!-- Order history table -->
        <template v-if="activeView==='history'">
        <div style="display:flex;justify-content:space-between;align-items:center;margin:1.5rem 0 0.75rem">
          <div style="font-weight:800;font-size:1rem">Order History</div>
          <button class="btn btn-ghost btn-sm" @click="exportCSV">↗ Export CSV</button>
        </div>

        <!-- Search + filter -->
        <div style="display:flex;gap:0.75rem;margin-bottom:1.25rem;align-items:center;flex-wrap:wrap">
          <div class="search-bar" style="flex:1;margin:0;min-width:160px">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
            <input v-model="search" class="form-control" placeholder="Search order ID or customer…" style="padding-left:2.5rem" />
          </div>
          <select v-model="dateFilter" class="form-control" style="width:auto;flex-shrink:0">
            <option value="today">Today</option>
            <option value="week">This Week</option>
            <option value="all">All Time</option>
          </select>
          <select v-model="statusFilter" class="form-control" style="width:auto;flex-shrink:0">
            <option value="all">All Status</option>
            <option value="placed">Placed</option>
            <option value="preparing">Preparing</option>
            <option value="ready">Ready</option>
            <option value="collected">Collected</option>
          </select>
        </div>

        <div class="card" style="overflow:auto">
          <table class="data-table">
            <thead>
              <tr>
                <th>ORDER</th>
                <th>CUSTOMER</th>
                <th>ITEMS</th>
                <th>TOTAL</th>
                <th>PICKUP</th>
                <th>STATUS</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="!allOrders.length"><td colspan="6" style="text-align:center;padding:2rem;color:var(--color-muted)">No orders yet</td></tr>
              <tr v-for="order in allOrders" :key="order.id">
                <td>
                  <div style="font-weight:700;font-size:0.82rem">#CE-{{ String(order.id).padStart(4,'0') }}</div>
                  <div style="font-size:0.72rem;color:var(--color-muted)">{{ formatTime(order.createdAt) }}</div>
                </td>
                <td style="font-weight:600">{{ order.customerName }}</td>
                <td style="font-size:0.8rem;color:var(--color-muted)">{{ formatItems(order.items) }}</td>
                <td style="font-weight:700;color:var(--color-primary)">RM {{ Number(order.total||0).toFixed(2) }}</td>
                <td style="font-size:0.8rem">🕐 {{ order.pickupAt }}</td>
                <td><span :class="['badge', `badge-${order.status}`]">{{ order.status.toUpperCase() }}</span></td>
              </tr>
            </tbody>
          </table>
        </div>
        </template> 
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
import { ref, computed, onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useVendorOrdersStore } from '@/stores/vendorOrders'
import { useNotificationStore } from '@/stores/notifications'

const auth    = useAuthStore()
const store   = useVendorOrdersStore()
const notif = useNotificationStore()
const showNotif = ref(false)
const activeView = ref('live')
const loading = ref(true)
const search  = ref('')
const dateFilter = ref('all')
const statusFilter = ref('all')

const columns = [
  { status:'placed',    label:'Placed',    color:'#3b82f6' },
  { status:'preparing', label:'Preparing', color:'#f59e0b' },
  { status:'ready',     label:'Ready',     color:'#10b981' },
  { status:'collected', label:'Collected', color:'#6b7280' },
]

const allOrders = computed(() => {
  let orders = store.orders

  // Filter by date
  if (dateFilter.value !== 'all') {
    const now = new Date()
    const startOfToday = new Date(now.getFullYear(), now.getMonth(), now.getDate())
    orders = orders.filter(o => {
      const d = new Date(o.createdAt)
      if (dateFilter.value === 'today') return d >= startOfToday
      if (dateFilter.value === 'week') {
        const weekAgo = new Date(startOfToday)
        weekAgo.setDate(weekAgo.getDate() - 6)
        return d >= weekAgo
      }
      return true
    })
  }

  // Filter by status
  if (statusFilter.value !== 'all') {
    orders = orders.filter(o => o.status === statusFilter.value)
  }

  // Filter by search
  if (search.value) {
    const q = search.value.toLowerCase()
    orders = orders.filter(o => {
      const formattedId = `#ce-${String(o.id).padStart(4, '0')}`
      return (
        o.customerName?.toLowerCase().includes(q) ||
        String(o.id).includes(q) ||
        formattedId.includes(q)
      )
    })
  }

  return orders
})

function filteredByStatus(status) { return store.orders.filter(o => o.status === status) }

function isUrgent(order) {
  if (!order.createdAt) return false
  return (Date.now() - new Date(order.createdAt).getTime()) > 20 * 60 * 1000 && order.status === 'placed'
}

function formatItems(items) { return (items||[]).map(i=>`+${i.quantity||i.qty||1} ${i.name}`).join(' ') }
function formatTime(d) { return d ? new Date(d).toLocaleTimeString('en-MY', { hour:'2-digit', minute:'2-digit', hour12:false }) : '' }

function toggleNotif() {
  showNotif.value = !showNotif.value
}

function handleNotifClick(n) {
  notif.markAsRead(n.id)
  showNotif.value = false
}

async function advance(id, status) {
  await store.updateStatus(id, status)
  if (store.myVendor) await store.fetchOrders(store.myVendor.id)
}
async function toggleOpen() { if (store.myVendor) await store.toggleOpen(store.myVendor.id) }

function exportCSV() {
  const rows = [['ID','Customer','Items','Total','Pickup','Status']]
  allOrders.value.forEach(o => rows.push([`#CE-${String(o.id).padStart(4,'0')}`, o.customerName, formatItems(o.items), Number(o.total||0).toFixed(2), o.pickupAt, o.status]))
  const csv = rows.map(r => r.map(c => `"${c}"`).join(',')).join('\n')
  const a = document.createElement('a'); a.href = 'data:text/csv,' + encodeURIComponent(csv); a.download = 'orders.csv'; a.click()
}

onMounted(async () => {
  try {
    if (!store.myVendor) await store.fetchMyVendor(auth.user?.id)
    if (store.myVendor) await store.fetchOrders(store.myVendor.id)
  } finally { loading.value = false }
  notif.fetchNotifications()
})
</script>
