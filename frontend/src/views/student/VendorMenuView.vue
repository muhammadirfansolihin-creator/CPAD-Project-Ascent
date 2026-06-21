<template>
  <div>
    <nav class="navbar">
      <router-link to="/" class="btn btn-ghost btn-sm">← Back</router-link>
      <span v-if="vendor" style="font-weight:700">{{ vendor.name }}</span>
      <div></div>
    </nav>

    <div v-if="loading" class="loading"><div class="spinner"></div></div>
    <template v-else-if="vendor">
      <div class="vendor-hero">
        <div class="vendor-hero-icon">{{ vendorEmoji(vendor.name) }}</div>
        <div>
          <h1>{{ vendor.name }}</h1>
          <div class="vendor-hero-meta">📍 {{ vendor.location }} · 🕐 {{ vendor.openingHours }}</div>
          <div style="margin-top:0.4rem;display:flex;gap:0.5rem;align-items:center">
            <span :class="['badge', vendor.isOpen?'badge-open':'badge-inactive']">{{ vendor.isOpen?'Open':'Closed' }}</span>
            <span v-if="vendor.rating" style="font-size:0.82rem;color:#92400e">⭐ {{ vendor.rating }}</span>
          </div>
        </div>
      </div>

      <div class="page">
        <template v-for="cat in menuCategories" :key="cat">
          <div v-if="groupedMenu[cat]?.length">
            <div class="section-title">{{ catLabel(cat) }}</div>
            <div v-for="item in groupedMenu[cat]" :key="item.id" class="menu-item">
              <div class="menu-item-img">{{ itemEmoji(item.category) }}</div>
              <div class="menu-item-info">
                <div class="menu-item-name">{{ item.name }}</div>
                <div v-if="item.description" class="menu-item-desc">{{ item.description }}</div>
                <div class="menu-item-price">RM {{ Number(item.price).toFixed(2) }}</div>
              </div>
              <button v-if="item.inStock && vendor.isOpen"
                class="btn btn-primary btn-sm" @click="addToCart(item)">Add</button>
              <span v-else class="badge badge-inactive" style="font-size:0.7rem">{{ !vendor.isOpen ? 'Closed' : 'Sold Out' }}</span>
            </div>
          </div>
        </template>

        <!-- Reviews -->
        <div class="section-title">Reviews</div>
        <div v-if="!reviews.length" class="empty"><p>No reviews yet.</p></div>
        <div v-for="r in reviews" :key="r.id" class="order-card" style="margin-bottom:0.6rem">
          <div style="display:flex;justify-content:space-between">
            <span style="font-weight:700;font-size:0.88rem">{{ r.userName || 'Student' }}</span>
            <span style="color:#d97706">{{ '⭐'.repeat(r.rating) }}</span>
          </div>
          <p style="font-size:0.82rem;color:var(--color-muted);margin-top:0.25rem">{{ r.comment }}</p>
        </div>
      </div>
    </template>

    <!-- Bottom nav -->
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

    <!-- FAB cart -->
    <router-link v-if="cart.itemCount" to="/cart" class="fab">
      🛒 Cart ({{ cart.itemCount }}) — RM {{ cart.total.toFixed(2) }}
    </router-link>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import axios from 'axios'
import { useCartStore } from '@/stores/cart'

const route   = useRoute()
const cart    = useCartStore()
const vendor  = ref(null)
const menu    = ref([])
const reviews = ref([])
const loading = ref(true)

const menuCategories = ['rice','noodles','drinks','snacks','other']
const groupedMenu = computed(() => {
  const g = {}
  for (const cat of menuCategories) g[cat] = menu.value.filter(i => i.category === cat)
  return g
})

function catLabel(c) {
  return { rice:'Rice', noodles:'Noodles', drinks:'Drinks', snacks:'Snacks', other:'Other' }[c] || c
}
function vendorEmoji(name) {
  const n = (name||'').toLowerCase()
  if (n.includes('minuman') || n.includes('drink')) return '🥤'
  if (n.includes('nasi') || n.includes('rice'))     return '🍚'
  if (n.includes('mee')  || n.includes('noodle'))   return '🍜'
  return '🍽️'
}
function itemEmoji(cat) {
  return { rice:'🍚', noodles:'🍜', drinks:'🥤', snacks:'🍡', other:'🍽️' }[cat] || '🍽️'
}

function addToCart(item) {
  cart.addItem(item, vendor.value)
}

onMounted(async () => {
  const id = route.params.id
  try {
    const [vRes, mRes, rRes] = await Promise.all([
      axios.get(`/api/vendors/${id}`),
      axios.get(`/api/vendors/${id}/menu`),
      axios.get(`/api/vendors/${id}/reviews`),
    ])
    vendor.value  = vRes.data
    menu.value    = mRes.data
    reviews.value = rRes.data
  } finally { loading.value = false }
})
</script>
