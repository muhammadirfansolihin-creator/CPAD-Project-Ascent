<template>
  <div>
    <nav class="navbar">
      <span class="navbar-brand">CampusEats</span>
      <div class="navbar-actions">
        <button class="btn btn-ghost btn-sm" @click="auth.logout()">Sign Out</button>
      </div>
    </nav>

    <div class="page" style="padding-bottom:80px">
      <h2 class="page-title">Orders</h2>

      <div v-if="loading" class="loading"><div class="spinner"></div></div>

      <template v-else>
        <!-- Kanban board -->
        <div class="kanban" style="padding-bottom:0.5rem">
          <div v-for="col in columns" :key="col.status" class="kanban-col">
            <div class="kanban-col-header" style="display:flex;justify-content:space-between;align-items:center">
              <span>{{ col.label }}</span>
              <span style="font-size:0.75rem;font-weight:700;background:var(--color-border);border-radius:999px;padding:0.1rem 0.5rem">
                {{ ordersByStatus[col.status]?.length ?? 0 }}
              </span>
            </div>
            <div class="kanban-col-body">
              <div v-if="!ordersByStatus[col.status]?.length" style="text-align:center;padding:1rem;color:var(--color-muted);font-size:0.8rem">
                Empty
              </div>
              <div
                v-for="order in ordersByStatus[col.status]"
                :key="order.id"
                class="kanban-card"
              >
                <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:0.4rem">
                  <div style="font-weight:700;font-size:0.85rem">#{{ order.id }}</div>
                  <span :class="['badge', `badge-${order.status}`]">{{ order.status }}</span>
                </div>
                <div class="text-muted" style="font-size:0.78rem">{{ order.studentName }}</div>
                <div class="text-muted" style="font-size:0.75rem;margin-top:0.15rem">⏰ {{ order.pickupTime }}</div>
                <div style="font-size:0.78rem;color:var(--color-text);margin-top:0.4rem;line-height:1.5">
                  {{ formatItems(order.items) }}
                </div>
                <div style="font-weight:700;font-size:0.85rem;color:var(--color-primary);margin-top:0.35rem">
                  RM {{ Number(order.total ?? 0).toFixed(2) }}
                </div>
                <div style="display:flex;gap:0.4rem;margin-top:0.6rem;flex-wrap:wrap">
                  <button
                    v-if="order.status === 'placed'"
                    class="btn btn-primary btn-sm"
                    style="width:100%"
                    @click="advance(order.id, 'preparing')"
                  >Start Preparing</button>
                  <button
                    v-if="order.status === 'preparing'"
                    class="btn btn-success btn-sm"
                    style="width:100%"
                    @click="advance(order.id, 'ready')"
                  >Mark Ready</button>
                  <button
                    v-if="order.status === 'ready'"
                    class="btn btn-ghost btn-sm"
                    style="width:100%"
                    @click="advance(order.id, 'collected')"
                  >Collected</button>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Order history -->
        <div style="margin-top:1.5rem">
          <div class="section-title">Order History</div>
          <div v-if="!collectedOrders.length" class="empty" style="padding:1.5rem">
            <p>No collected orders yet</p>
          </div>
          <div v-for="order in collectedOrders" :key="order.id" class="order-card">
            <div class="order-card-header">
              <div>
                <div style="font-weight:700;font-size:0.9rem">Order #{{ order.id }}</div>
                <div class="text-muted" style="font-size:0.78rem;margin-top:0.15rem">{{ order.studentName }} · {{ order.pickupTime }}</div>
              </div>
              <span class="badge badge-collected">Collected</span>
            </div>
            <div style="font-size:0.82rem;color:var(--color-muted)">{{ formatItems(order.items) }}</div>
            <div style="font-weight:700;color:var(--color-primary);font-size:0.9rem;margin-top:0.35rem">RM {{ Number(order.total ?? 0).toFixed(2) }}</div>
          </div>
        </div>
      </template>
    </div>

    <!-- Bottom nav -->
    <nav class="bottom-nav">
      <router-link to="/vendor" class="bottom-nav-item">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>
        Dashboard
      </router-link>
      <router-link to="/vendor/orders" class="bottom-nav-item active">
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
import { ref, computed, onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useVendorOrdersStore } from '@/stores/vendorOrders'

const auth    = useAuthStore()
const store   = useVendorOrdersStore()
const loading = ref(true)

const columns = [
  { status: 'placed',    label: 'Placed' },
  { status: 'preparing', label: 'Preparing' },
  { status: 'ready',     label: 'Ready' },
]

const ordersByStatus = computed(() => {
  const map = {}
  for (const col of columns) map[col.status] = []
  for (const o of store.orders) {
    if (map[o.status]) map[o.status].push(o)
  }
  return map
})

const collectedOrders = computed(() => store.orders.filter(o => o.status === 'collected'))

function formatItems(items) {
  if (!items?.length) return ''
  return items.map(i => `${i.quantity}x ${i.name}`).join(', ')
}

async function advance(orderId, status) {
  await store.updateStatus(orderId, status)
  if (store.myVendor) await store.fetchOrders(store.myVendor.id)
}

onMounted(async () => {
  try {
    if (!store.myVendor) await store.fetchMyVendor(auth.user?.id)
    if (store.myVendor) await store.fetchOrders(store.myVendor.id)
  } finally {
    loading.value = false
  }
})
</script>