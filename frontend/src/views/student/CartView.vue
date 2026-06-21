<template>
  <div>
    <nav class="navbar">
      <router-link to="/" class="btn btn-ghost btn-sm">← Back</router-link>
      <span style="font-weight:700">Your Cart</span>
      <button class="btn btn-ghost btn-sm" @click="cart.clear()">Clear</button>
    </nav>

    <div class="page">
      <div v-if="!cart.items.length" class="empty">
        <div class="empty-icon">🛒</div>
        <p>Your cart is empty</p>
        <router-link to="/" class="btn btn-primary" style="margin-top:1rem">Browse Vendors</router-link>
      </div>

      <template v-else>
        <div v-if="error" class="alert alert-error">{{ error }}</div>
        <div v-if="success" class="alert alert-success">{{ success }}</div>

        <div class="card">
          <div class="card-header">Order Items</div>
          <div class="card-body">
            <div v-for="item in cart.items" :key="item.id" class="cart-item">
              <span style="flex:1;font-weight:600;font-size:0.9rem">{{ item.name }}</span>
              <div class="qty-control">
                <button class="qty-btn" @click="cart.removeItem(item.id)">−</button>
                <span style="font-weight:700;min-width:20px;text-align:center">{{ item.qty }}</span>
                <button class="qty-btn" @click="cart.addItem(item, {id: cart.vendorId})">+</button>
              </div>
              <span style="min-width:72px;text-align:right;font-weight:700;color:var(--color-primary)">
                RM {{ (item.price * item.qty).toFixed(2) }}
              </span>
            </div>
          </div>
        </div>

        <!-- Pickup time -->
        <div class="card" style="margin-top:0.85rem">
          <div class="card-header">Pickup Time</div>
          <div class="card-body">
            <select v-model="pickupAt" class="form-control">
              <option v-for="slot in timeSlots" :key="slot" :value="slot">{{ slot }}</option>
            </select>
            <p class="text-muted" style="margin-top:0.5rem;font-size:0.8rem">
              Your order will be ready at the selected time. Please collect from the vendor's stall.
            </p>
          </div>
        </div>

        <!-- Total -->
        <div class="card" style="margin-top:0.85rem">
          <div class="card-body">
            <div style="display:flex;justify-content:space-between;font-size:1.1rem;font-weight:800">
              <span>Total</span>
              <span style="color:var(--color-primary)">RM {{ cart.total.toFixed(2) }}</span>
            </div>
            <button class="btn btn-primary btn-block" style="margin-top:1rem" :disabled="loading" @click="placeOrder">
              {{ loading ? 'Placing…' : 'Place Order' }}
            </button>
          </div>
        </div>
      </template>
    </div>

    <nav class="bottom-nav">
      <router-link to="/" class="bottom-nav-item">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
        Home
      </router-link>
      <router-link to="/orders" class="bottom-nav-item">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2"/><rect width="6" height="4" x="9" y="3" rx="2"/></svg>
        Orders
      </router-link>
    </nav>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'
import { useCartStore } from '@/stores/cart'

const cart    = useCartStore()
const router  = useRouter()
const loading = ref(false)
const error   = ref('')
const success = ref('')

function generateSlots() {
  const slots = []
  const now = new Date()
  now.setMinutes(Math.ceil(now.getMinutes() / 15) * 15, 0, 0)
  for (let i = 0; i < 8; i++) {
    const t = new Date(now.getTime() + i * 15 * 60 * 1000)
    slots.push(t.toLocaleTimeString('en-MY', { hour: '2-digit', minute: '2-digit', hour12: true }))
  }
  return slots
}
const timeSlots = generateSlots()
const pickupAt  = ref(timeSlots[1] || timeSlots[0])

async function placeOrder() {
  error.value = ''; success.value = ''
  loading.value = true
  try {
    await axios.post('/api/orders', {
      vendorId: cart.vendorId,
      pickupAt: pickupAt.value,
      items: cart.items.map(i => ({ menuItemId: i.id, qty: i.qty })),
    })
    success.value = 'Order placed! Heading to your orders…'
    cart.clear()
    setTimeout(() => router.push('/orders'), 1500)
  } catch (e) {
    error.value = e.response?.data?.error || 'Failed to place order'
  } finally {
    loading.value = false
  }
}
</script>
