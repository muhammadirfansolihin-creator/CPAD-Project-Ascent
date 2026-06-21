<template>
  <div>
    <nav class="navbar">
      <span class="navbar-brand">CampusEats</span>
      <button class="btn btn-ghost btn-sm" @click="auth.logout()">Sign Out</button>
    </nav>

    <div class="page">
      <div class="page-title">My Orders</div>

      <div v-if="loading" class="loading"><div class="spinner"></div></div>
      <div v-else-if="!orders.length" class="empty">
        <div class="empty-icon">📋</div>
        <p>No orders yet</p>
        <router-link to="/" class="btn btn-primary" style="margin-top:1rem">Order Now</router-link>
      </div>

      <div v-for="order in orders" :key="order.id" class="order-card">
        <div class="order-card-header">
          <div>
            <div style="font-weight:700;font-size:0.95rem">{{ order.vendorName }}</div>
            <div class="text-muted" style="font-size:0.78rem;margin-top:0.2rem">
              Pickup: {{ order.pickupAt }} · {{ formatDate(order.createdAt) }}
            </div>
          </div>
          <span :class="['badge', `badge-${order.status}`]">{{ order.status }}</span>
        </div>

        <div style="margin:0.5rem 0;border-top:1px solid var(--color-border);padding-top:0.5rem">
          <div v-for="item in order.items" :key="item.id" style="display:flex;justify-content:space-between;font-size:0.85rem;margin-bottom:0.2rem">
            <span>{{ item.qty }}x {{ item.name }}</span>
            <span>RM {{ (item.unitPrice * item.qty).toFixed(2) }}</span>
          </div>
        </div>

        <div style="display:flex;justify-content:space-between;align-items:center;margin-top:0.5rem">
          <span style="font-weight:800;color:var(--color-primary)">Total: RM {{ Number(order.total).toFixed(2) }}</span>
          <button class="btn btn-outline btn-sm" @click="reorder(order)">Reorder</button>
        </div>
      </div>
    </div>

    <nav class="bottom-nav">
      <router-link to="/" class="bottom-nav-item">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
        Home
      </router-link>
      <router-link to="/orders" class="bottom-nav-item active">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2"/><rect width="6" height="4" x="9" y="3" rx="2"/></svg>
        Orders
      </router-link>
    </nav>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'
import { useAuthStore } from '@/stores/auth'
import { useCartStore } from '@/stores/cart'

const auth    = useAuthStore()
const cart    = useCartStore()
const router  = useRouter()
const orders  = ref([])
const loading = ref(true)

function formatDate(d) { return new Date(d).toLocaleDateString('en-MY', { day:'numeric', month:'short', year:'numeric' }) }

async function reorder(order) {
  cart.clear()
  for (const item of order.items) {
    cart.addItem({ id: item.menuItemId, name: item.name, price: item.unitPrice }, { id: order.vendorId })
  }
  router.push('/cart')
}

onMounted(async () => {
  try { const { data } = await axios.get('/api/orders'); orders.value = data } finally { loading.value = false }
})
</script>
