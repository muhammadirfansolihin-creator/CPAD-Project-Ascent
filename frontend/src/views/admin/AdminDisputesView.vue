<template>
  <div>
    <nav class="navbar">
      <div class="navbar-brand"><img src="/favicon.png" alt="CampusEats Logo" class="navbar-brand-icon" /> CampusEats</div>
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
      <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.25rem">
        <div>
          <div class="page-title" style="margin-bottom:0">Disputes</div>
          <div style="font-size:0.8rem;color:var(--color-muted)">{{ openCount }} open · {{ resolvedCount }} resolved</div>
        </div>
      </div>

      <!-- Filter tabs -->
      <div class="category-tabs" style="padding:0;margin-bottom:1rem">
        <button v-for="f in filters" :key="f.value" :class="['cat-tab', filter===f.value?'active':'']" @click="setFilter(f.value)">
          {{ f.label }} <span style="margin-left:0.3rem;background:rgba(0,0,0,0.1);border-radius:999px;padding:0.05rem 0.45rem;font-size:0.7rem">{{ countByStatus[f.value] || 0 }}</span>
        </button>
      </div>

      <div v-if="loading" class="loading"><div class="spinner"></div></div>
      <div v-else-if="!store.disputes.length" class="empty">
        <div class="empty-icon">✅</div>
        <p>No disputes found</p>
      </div>

      <div v-for="d in store.disputes" :key="d.id" class="card" style="margin-bottom:0.75rem">
        <div class="card-body">
          <!-- Header row -->
          <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:0.5rem;margin-bottom:0.6rem">
            <div>
              <div style="font-weight:700;font-size:0.88rem">#DSP-{{ String(d.id).padStart(4,'0') }} · Order #CE-{{ String(d.orderId).padStart(4,'0') }}</div>
              <div class="text-muted" style="font-size:0.75rem;margin-top:0.15rem">{{ formatDate(d.createdAt) }} · Raised by {{ d.reporterName }}</div>
            </div>
            <span :class="['badge', `badge-${d.status}`]">{{ d.status.toUpperCase() }}</span>
          </div>

          <!-- Description quote -->
          <div class="dispute-desc">"{{ d.description }}"</div>

          <!-- Resolution shown if already resolved -->
          <div v-if="d.resolution" style="background:#f0fdf4;border:1px solid #86efac;border-radius:0.4rem;padding:0.6rem;font-size:0.82rem;color:#166534;margin-bottom:0.6rem">
            <strong>Resolution:</strong> {{ d.resolution }}
            <span v-if="d.refunded" style="margin-left:0.5rem;background:#dcfce7;border-radius:999px;padding:0.1rem 0.5rem;font-size:0.7rem;font-weight:700">REFUNDED</span>
          </div>

          <!-- Actions for open disputes -->
          <template v-if="d.status==='open'">
            <div style="display:flex;gap:0.5rem;align-items:flex-end;margin-top:0.5rem">
              <div style="flex:1">
                <input v-model="resolutions[d.id]" class="form-control" style="font-size:0.85rem" placeholder="Enter resolution note…" />
              </div>
            </div>
            <div style="display:flex;gap:0.5rem;margin-top:0.5rem">
              <button class="btn btn-success btn-sm" style="flex:1" :disabled="!resolutions[d.id]" @click="resolve(d.id)">✓ Mark Resolved</button>
              <button class="btn btn-warning btn-sm" style="flex:1" :disabled="!resolutions[d.id]" @click="refund(d.id)">↩ Refund</button>
              <button class="btn btn-outline btn-sm" style="flex:1" @click="viewOrder(d.orderId)">View Order</button>
            </div>
          </template>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useAdminDashboardStore } from '@/stores/adminDashboard'
import { useNotificationStore } from '@/stores/notifications'

const auth        = useAuthStore()
const store       = useAdminDashboardStore()
const router      = useRouter()
const notif = useNotificationStore()
const showNotif = ref(false)
const loading     = ref(true)
const filter      = ref('all')
const resolutions = reactive({})

const filters = [
  { value:'all',      label:'All' },
  { value:'open',     label:'Open' },
  { value:'resolved', label:'Resolved' },
]

const countByStatus = computed(() => {
  const map = { all: store.disputes.length, open: 0, resolved: 0 }
  for (const d of store.disputes) map[d.status] = (map[d.status] || 0) + 1
  return map
})
const openCount     = computed(() => countByStatus.value['open'] || 0)
const resolvedCount = computed(() => countByStatus.value['resolved'] || 0)

function formatDate(d) {
  const dt = new Date(d)
  const now = Date.now()
  const diff = now - dt.getTime()
  if (diff < 3600000) return `${Math.floor(diff/60000)} min ago`
  if (diff < 86400000) return `${Math.floor(diff/3600000)} hr ago`
  return dt.toLocaleDateString('en-MY', { day:'numeric', month:'short', year:'numeric' })
}

async function setFilter(val) {
  filter.value = val; loading.value = true
  try { await store.fetchDisputes(val === 'all' ? null : val) } finally { loading.value = false }
}

async function resolve(id) {
  if (!resolutions[id]) return
  await store.resolveDispute(id, resolutions[id])
  delete resolutions[id]
}

async function refund(id) {
  if (!resolutions[id]) return
  await store.refundDispute(id, resolutions[id])
  delete resolutions[id]
}

function viewOrder(orderId) { alert(`Viewing Order #${orderId}`) }

function toggleNotif() {
  showNotif.value = !showNotif.value
}

function handleNotifClick(n) {
  notif.markAsRead(n.id)
  showNotif.value = false
}

onMounted(async () => { 
  try { await store.fetchDisputes() } finally { loading.value = false } 
  notif.fetchNotifications()
})
</script>
