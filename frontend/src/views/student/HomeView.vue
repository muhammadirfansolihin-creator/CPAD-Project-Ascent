<template>
  <div>
    <nav class="navbar">
      <span class="navbar-brand">CampusEats</span>
      <div class="navbar-actions">
        <button class="btn btn-ghost btn-sm" @click="auth.logout()">Sign Out</button>
      </div>
    </nav>

    <div class="page">
      <!-- Search -->
      <div class="search-bar">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
        <input v-model="search" class="form-control" placeholder="Search vendors or food…" />
      </div>

      <!-- Promo banner -->
      <div class="promo-banner">
        <div>
          <h3>Lunch Rush Promo</h3>
          <p>Order before 12:00 PM and skip the queue entirely!</p>
        </div>
        <span style="font-size:2rem">🍱</span>
      </div>

      <!-- Category tabs -->
      <div class="category-tabs">
        <button v-for="cat in categories" :key="cat.value"
          :class="['cat-tab', selectedCat===cat.value?'active':'']"
          @click="selectedCat=cat.value">{{ cat.label }}</button>
      </div>

      <!-- Vendors -->
      <div v-if="loading" class="loading"><div class="spinner"></div> Loading vendors…</div>
      <div v-else-if="!filteredVendors.length" class="empty">
        <div class="empty-icon">🍽️</div>
        <p>No vendors found</p>
      </div>
      <div v-else class="grid-2">
        <router-link v-for="v in filteredVendors" :key="v.id" :to="`/vendors/${v.id}`" class="vendor-card">
          <div class="vendor-card-img">{{ vendorEmoji(v.name) }}</div>
          <div class="vendor-card-body">
            <div class="vendor-card-name">{{ v.name }}</div>
            <div class="vendor-card-meta">📍 {{ v.location }}</div>
            <div style="display:flex;align-items:center;gap:0.5rem;margin-top:0.4rem">
              <span :class="['badge', v.isOpen?'badge-open':'badge-inactive']">{{ v.isOpen?'Open':'Closed' }}</span>
              <span v-if="v.rating" style="font-size:0.78rem;color:#92400e">⭐ {{ v.rating }}</span>
            </div>
          </div>
        </router-link>
      </div>
    </div>

    <!-- Bottom nav -->
    <nav class="bottom-nav">
      <router-link to="/" class="bottom-nav-item active">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
        Home
      </router-link>
      <router-link to="/orders" class="bottom-nav-item">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2"/><rect width="6" height="4" x="9" y="3" rx="2"/></svg>
        Orders
      </router-link>
    </nav>

    <!-- FAB cart -->
    <router-link v-if="cart.itemCount" to="/cart" class="fab">
      🛒 Cart ({{ cart.itemCount }}) — RM {{ cart.total.toFixed(2) }}
    </router-link>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import axios from 'axios'
import { useAuthStore } from '@/stores/auth'
import { useCartStore } from '@/stores/cart'

const auth   = useAuthStore()
const cart   = useCartStore()
const search = ref('')
const selectedCat = ref('all')
const vendors = ref([])
const loading = ref(true)

const categories = [
  { value: 'all', label: 'All' },
  { value: 'rice', label: 'Rice' },
  { value: 'noodles', label: 'Noodles' },
  { value: 'drinks', label: 'Drinks' },
  { value: 'snacks', label: 'Snacks' },
]

const filteredVendors = computed(() => {
  let list = vendors.value
  if (search.value) list = list.filter(v => v.name.toLowerCase().includes(search.value.toLowerCase()) || v.location.toLowerCase().includes(search.value.toLowerCase()))
  return list
})

function vendorEmoji(name) {
  const n = name.toLowerCase()
  if (n.includes('minuman') || n.includes('drink')) return '🥤'
  if (n.includes('nasi') || n.includes('rice'))     return '🍚'
  if (n.includes('mee')  || n.includes('noodle'))   return '🍜'
  return '🍽️'
}

onMounted(async () => {
  try { const { data } = await axios.get('/api/vendors'); vendors.value = data } finally { loading.value = false }
})
</script>
