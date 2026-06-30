<template>
  <div>
    <nav class="navbar">
      <router-link to="/" class="navbar-icon-btn" style="text-decoration:none"><ChevronLeft :size="22" /></router-link>
      <div class="navbar-brand" style="font-size:1rem">Your Cart</div>
      <button class="navbar-icon-btn" @click="cart.clear()" style="font-size:0.8rem;width:auto;padding:0 0.5rem;border-radius:0.4rem">Clear</button>
    </nav>

    <div class="page">
      <div v-if="!cart.items.length" class="empty">
        <div class="empty-icon"><ShoppingCart :size="40" /></div>
        <p>Your cart is empty</p>
        <router-link to="/" class="btn btn-primary" style="margin-top:1rem">Browse Vendors</router-link>
      </div>

      <template v-else>
        <div v-if="error" class="alert alert-error">{{ error }}</div>
        <div v-if="success" class="alert alert-success">{{ success }}</div>

        <p style="text-align:center;font-size:0.82rem;color:var(--color-muted);margin-bottom:1rem">
          Estimated preparation time: <strong style="color:var(--color-primary)">~15 minutes</strong>
        </p>

        <!-- Cart items -->
        <div class="card" style="margin-bottom:0.85rem">
          <div class="card-header" style="font-size:0.75rem;text-transform:uppercase;letter-spacing:0.05em;color:var(--color-muted)">{{ vendorName }}</div>
          <div class="card-body" style="padding:0 1.25rem">
            <div v-for="item in cart.items" :key="item.id" class="cart-item">
              <div class="cart-item-img">{{ itemEmoji(item.category) }}</div>
              <div style="flex:1;min-width:0">
                <div style="font-weight:700;font-size:0.9rem">{{ item.name }}</div>
                <div style="color:var(--color-primary);font-weight:700;font-size:0.88rem">RM {{ Number(item.price).toFixed(2) }}</div>
              </div>
              <div class="qty-control">
                <button class="qty-btn" @click="cart.removeItem(item.id)">−</button>
                <span style="font-weight:700;min-width:20px;text-align:center">{{ item.qty }}</span>
                <button class="qty-btn" @click="cart.addItem(item, {id: cart.vendorId})">+</button>
              </div>
            </div>
          </div>
        </div>

        <!-- Pickup Time -->
        <div class="card" style="margin-bottom:0.85rem">
          <div class="card-body">
            <div style="display:flex;align-items:center;gap:0.5rem;font-weight:700;font-size:0.95rem;margin-bottom:0.75rem">
              <Clock :size="18" /> Pickup Time
            </div>
            <div class="pickup-tabs">
              <button :class="['pickup-tab', pickupMode==='now'?'active':'']" @click="pickupMode='now'">
                <Zap :size="14" /> Now
              </button>
              <button :class="['pickup-tab', pickupMode==='schedule'?'active':'']" @click="pickupMode='schedule'">
                <CalendarDays :size="14" /> Schedule
              </button>
            </div>
            <div v-if="pickupMode==='now'" class="pickup-info">
              Your order will be prepared as soon as possible. Typical wait: <strong>~15 min</strong>
            </div>
            <div v-else style="margin-top:0.5rem">
              <select v-model="pickupAt" class="form-control">
                <option v-for="slot in timeSlots" :key="slot" :value="slot">{{ slot }}</option>
              </select>
            </div>
          </div>
        </div>

        <!-- Special Instructions -->
        <div class="card" style="margin-bottom:0.85rem">
          <div class="card-body">
            <div style="font-weight:700;font-size:0.95rem;margin-bottom:0.6rem">Special Instructions</div>
            <textarea v-model="instructions" class="form-control" placeholder="E.g. less spicy, no onions…" rows="3"></textarea>
          </div>
        </div>

        <!-- Total & Place Order -->
        <div class="card">
          <div class="card-body">
            <div style="display:flex;justify-content:space-between;font-size:0.88rem;color:var(--color-muted);margin-bottom:0.4rem">
              <span>Subtotal</span><span>RM {{ cart.total.toFixed(2) }}</span>
            </div>
            <div style="display:flex;justify-content:space-between;font-size:1.05rem;font-weight:800;border-top:1px solid var(--color-border);padding-top:0.6rem;margin-top:0.4rem">
              <span>Total</span>
              <span style="color:var(--color-primary)">RM {{ cart.total.toFixed(2) }}</span>
            </div>
            <button class="btn btn-primary btn-block" style="margin-top:1rem" :disabled="loading" @click="placeOrder">
              {{ loading ? 'Placing Order…' : 'Place Order' }}
            </button>
          </div>
        </div>
      </template>
    </div>

    <nav class="bottom-nav">
      <router-link to="/" class="bottom-nav-item">
        <Home :size="22" />
        Home
      </router-link>
      <router-link to="/orders" class="bottom-nav-item">
        <ClipboardList :size="22" />
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
import { ChevronLeft, ShoppingCart, Clock, Zap, CalendarDays, Home, ClipboardList } from 'lucide-vue-next'

const cart         = useCartStore()
const router       = useRouter()
const loading      = ref(false)
const error        = ref('')
const success      = ref('')
const pickupMode   = ref('now')
const instructions = ref('')

const vendorName = computed(() => cart.items[0]?.vendorName || 'Your Order')

function itemEmoji(cat) { return { rice:'🍚', noodles:'🍜', drinks:'🥤', snacks:'🍡', other:'🍽️' }[cat] || '🍽️' }

function generateSlots() {
  const slots = []
  const now = new Date()
  now.setMinutes(Math.ceil(now.getMinutes() / 15) * 15, 0, 0)
  for (let i = 1; i < 10; i++) {
    const t = new Date(now.getTime() + i * 15 * 60 * 1000)
    slots.push(t.toLocaleTimeString('en-MY', { hour: '2-digit', minute: '2-digit', hour12: true }))
  }
  return slots
}
const timeSlots = generateSlots()
const pickupAt  = ref(timeSlots[1] || timeSlots[0])

const effectivePickup = computed(() => {
  if (pickupMode.value === 'now') {
    const now = new Date()
    now.setMinutes(now.getMinutes() + 15)
    return now.toLocaleTimeString('en-MY', { hour: '2-digit', minute: '2-digit', hour12: true })
  }
  return pickupAt.value
})

async function placeOrder() {
  error.value = ''; success.value = ''; loading.value = true
  try {
    await axios.post('/api/orders', {
      vendorId: cart.vendorId,
      pickupAt: effectivePickup.value,
      items: cart.items.map(i => ({ menuItemId: i.id, qty: i.qty })),
    })
    cart.clear()
    router.push('/orders')
  } catch (e) {
    error.value = e.response?.data?.error || 'Failed to place order. Please try again.'
    loading.value = false
  }
}
</script>
