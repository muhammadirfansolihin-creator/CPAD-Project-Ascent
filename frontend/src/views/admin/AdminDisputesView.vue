<template>
  <div>
    <nav class="navbar">
      <span class="navbar-brand">CampusEats Admin</span>
      <button class="btn btn-ghost btn-sm" @click="auth.logout()">Sign Out</button>
    </nav>

    <div class="admin-tabs">
      <router-link to="/admin"           class="admin-tab">Sales Summary</router-link>
      <router-link to="/admin/vendors"   class="admin-tab">Vendors</router-link>
      <router-link to="/admin/disputes"  class="admin-tab active">Disputes</router-link>
    </div>

    <div class="page" style="padding-bottom:2rem">
      <div v-if="loading" class="loading"><div class="spinner"></div></div>
      <div v-else-if="!store.disputes.length" class="empty">
        <div class="empty-icon">✅</div>
        <p>No disputes found</p>
      </div>

      <template v-else>
        <div class="category-tabs" style="margin-bottom:1rem">
          <button v-for="f in filters" :key="f.value" :class="['cat-tab', filter===f.value?'active':'']" @click="setFilter(f.value)">
            {{ f.label }}
          </button>
        </div>

        <div v-for="d in store.disputes" :key="d.id" class="card" style="margin-bottom:0.75rem">
          <div class="card-body">
            <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:0.5rem">
              <div>
                <div style="font-weight:700;font-size:0.88rem">Dispute #{{ d.id }} · Order #{{ d.orderId }}</div>
                <div class="text-muted" style="font-size:0.78rem;margin-top:0.2rem">{{ formatDate(d.createdAt) }} · {{ d.reporterName }}</div>
              </div>
              <span :class="['badge', `badge-${d.status}`]">{{ d.status }}</span>
            </div>

            <p style="margin:0.6rem 0;font-size:0.88rem;line-height:1.5">{{ d.description }}</p>

            <div v-if="d.resolution" style="background:#f0fdf4;border:1px solid #86efac;border-radius:0.4rem;padding:0.6rem;font-size:0.82rem;color:#166534;margin-bottom:0.6rem">
              Resolution: {{ d.resolution }}
            </div>

            <div v-if="d.status === 'open'" style="display:flex;gap:0.5rem;align-items:flex-end">
              <div style="flex:1">
                <input v-model="resolutions[d.id]" class="form-control" placeholder="Enter resolution…" />
              </div>
              <button class="btn btn-primary btn-sm" :disabled="!resolutions[d.id]" @click="resolve(d.id)">
                Resolve
              </button>
            </div>
          </div>
        </div>
      </template>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useAdminDashboardStore } from '@/stores/adminDashboard'

const auth        = useAuthStore()
const store       = useAdminDashboardStore()
const loading     = ref(true)
const filter      = ref('all')
const resolutions = reactive({})

const filters = [
  { value: 'all',      label: 'All' },
  { value: 'open',     label: 'Open' },
  { value: 'resolved', label: 'Resolved' },
]

function formatDate(d) { return new Date(d).toLocaleDateString('en-MY', { day:'numeric', month:'short', year:'numeric', hour:'2-digit', minute:'2-digit' }) }

async function setFilter(val) {
  filter.value = val
  loading.value = true
  try { await store.fetchDisputes(val === 'all' ? null : val) } finally { loading.value = false }
}

async function resolve(id) {
  if (!resolutions[id]) return
  await store.resolveDispute(id, resolutions[id])
  delete resolutions[id]
}

onMounted(async () => { try { await store.fetchDisputes() } finally { loading.value = false } })
</script>
