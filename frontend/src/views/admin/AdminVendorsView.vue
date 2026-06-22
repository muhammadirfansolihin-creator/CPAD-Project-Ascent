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

        <button class="navbar-icon-btn" @click="auth.logout()">👤</button>
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
          <div class="page-title" style="margin-bottom:0">Vendors</div>
          <div style="font-size:0.8rem;color:var(--color-muted)">{{ pendingCount }} pending approval</div>
        </div>
      </div>

      <!-- Status filter tabs -->
      <div class="category-tabs" style="padding:0;margin-bottom:1rem">
        <button v-for="f in filters" :key="f.value" :class="['cat-tab', filter===f.value?'active':'']" @click="filter=f.value">
          {{ f.label }} <span style="margin-left:0.3rem;background:rgba(0,0,0,0.1);border-radius:999px;padding:0.05rem 0.45rem;font-size:0.7rem">{{ countByStatus[f.value] || 0 }}</span>
        </button>
      </div>

      <div v-if="loading" class="loading"><div class="spinner"></div></div>
      <div v-else-if="!filteredVendors.length" class="empty">
        <div class="empty-icon">🏪</div>
        <p>No vendors found</p>
      </div>

      <template v-else>
        <div v-for="v in filteredVendors" :key="v.id" class="card" style="margin-bottom:0.75rem">
          <div class="card-body">
            <div style="display:flex;align-items:flex-start;gap:0.75rem">
              <div style="width:48px;height:48px;border-radius:0.6rem;background:linear-gradient(135deg,#f0e8d8,#e8dcc8);display:flex;align-items:center;justify-content:center;font-size:1.5rem;flex-shrink:0">🏪</div>
              <div style="flex:1;min-width:0">
                <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:0.5rem">
                  <div>
                    <div style="font-weight:700;font-size:0.95rem">{{ v.name }}</div>
                    <div class="text-muted" style="font-size:0.78rem;margin-top:0.15rem">📍 {{ v.location }}</div>
                    <div class="text-muted" style="font-size:0.75rem">Owner: {{ v.ownerName }}</div>
                    <div v-if="v.rating" style="font-size:0.75rem;color:#b45309;font-weight:700;margin-top:0.2rem">★ {{ v.rating }}</div>
                    <div style="font-size:0.72rem;color:var(--color-muted)">{{ v.totalOrders }} orders total</div>
                  </div>
                  <span :class="['badge', `badge-${v.status}`]">{{ v.status.toUpperCase() }}</span>
                </div>

                <div style="display:flex;gap:0.5rem;flex-wrap:wrap;margin-top:0.85rem">
                  <template v-if="v.status==='pending'">
                    <button class="btn btn-success btn-sm" @click="updateStatus(v.id,'active')">✓ Approve</button>
                    <button class="btn btn-danger btn-sm" @click="updateStatus(v.id,'inactive')">✗ Reject</button>
                  </template>
                  <template v-else-if="v.status==='active'">
                    <button class="btn btn-ghost btn-sm" @click="updateStatus(v.id,'inactive')">Deactivate</button>
                  </template>
                  <template v-else-if="v.status==='inactive'">
                    <button class="btn btn-success btn-sm" @click="updateStatus(v.id,'active')">Reactivate</button>
                  </template>
                </div>
              </div>
            </div>
          </div>
        </div>
      </template>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useAdminDashboardStore } from '@/stores/adminDashboard'
import { useNotificationStore } from '@/stores/notifications'

const auth    = useAuthStore()
const store   = useAdminDashboardStore()
const notif = useNotificationStore()
const showNotif = ref(false)
const loading = ref(true)
const filter  = ref('all')

const filters = [
  { value:'all',      label:'All' },
  { value:'pending',  label:'Pending' },
  { value:'active',   label:'Active' },
  { value:'inactive', label:'Inactive' },
]

const countByStatus = computed(() => {
  const map = { all: store.vendors.length }
  for (const v of store.vendors) map[v.status] = (map[v.status] || 0) + 1
  return map
})
const pendingCount     = computed(() => countByStatus.value['pending'] || 0)
const filteredVendors  = computed(() => filter.value === 'all' ? store.vendors : store.vendors.filter(v => v.status === filter.value))

async function updateStatus(vendorId, status) { await store.updateVendorStatus(vendorId, status) }

function toggleNotif() {
  showNotif.value = !showNotif.value
}

function handleNotifClick(n) {
  notif.markAsRead(n.id)
  showNotif.value = false
}

onMounted(async () => { 
  try { await store.fetchVendors() } finally { loading.value = false } 
  notif.fetchNotifications()
})
</script>
