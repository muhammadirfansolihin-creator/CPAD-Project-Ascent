<template>
  <div>
    <nav class="navbar">
      <div>
        <div class="navbar-brand"><span class="navbar-brand-icon">🍴</span> CampusEats</div>
      </div>
      
      <div class="navbar-actions" style="position:relative">
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

        <router-link to="/cart" class="navbar-icon-btn">
          🛒
          <span v-if="cart.itemCount" class="badge-dot"></span>
        </router-link>
        <button class="navbar-icon-btn" @click="auth.logout()" title="Sign out">👤</button>
      </div>
    </nav>

    <!-- Search -->
    <div class="search-wrap">
      <div class="search-bar">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
        <input v-model="search" class="form-control" placeholder="Search for a dish or vendor…" />
      </div>
    </div>

    <!-- Category tabs -->
    <div class="category-tabs">
      <button v-for="cat in categories" :key="cat.value"
        :class="['cat-tab', selectedCat===cat.value?'active':'']"
        @click="selectedCat=cat.value">{{ cat.label }}</button>
    </div>

    <!-- Promo banner -->
    <div class="promo-banner">
      <div>
        <h3>Lunch Rush Promo 🔥</h3>
        <p>Order before 12:30 — skip the queue!</p>
      </div>
      <button class="promo-btn" @click="scrollToVendors">Order Now</button>
    </div>

    <div v-if="loading" class="loading"><div class="spinner"></div></div>
    <template v-else-if="!filteredVendors.length">
      <div class="empty"><div class="empty-icon">🍽️</div><p>No vendors found</p></div>
    </template>
    <template v-else>
      <!-- Today's Special: food item cards -->
      <div class="section-row">
        <span class="section-row-title">Today's Special</span>
        <a class="section-row-link">See all →</a>
      </div>
      <div class="horizontal-scroll" ref="vendorsRef">
        <router-link
          v-for="item in featuredItems.slice(0, 6)"
          :key="'fi'+item.vendorId+item.name"
          :to="`/vendors/${item.vendorId}`"
          class="vendor-card"
        >
          <div class="vendor-card-img">{{ itemEmoji(item.category) }}</div>
          <div class="vendor-card-body">
            <div class="vendor-card-name">{{ item.name }}</div>
            <div class="vendor-card-meta" style="color:var(--color-muted);font-size:0.72rem;margin-top:0.15rem">From {{ item.vendorName }}</div>
            <div class="vendor-card-rating">
              <span style="color:var(--color-primary);font-weight:700;font-size:0.8rem">RM {{ Number(item.price).toFixed(2) }}</span>
              <span class="badge badge-halal">HALAL</span>
            </div>
          </div>
        </router-link>
      </div>

      <!-- Category sections (vendor cards) -->
      <template v-for="cat in visibleCategories" :key="cat.value">
        <div v-if="vendorsByCategory[cat.value]?.length" class="section-row" style="margin-top:0.5rem">
          <span class="section-row-title">{{ cat.label }}</span>
          <a class="section-row-link">See all →</a>
        </div>
        <div v-if="vendorsByCategory[cat.value]?.length" class="horizontal-scroll">
          <router-link v-for="v in vendorsByCategory[cat.value]" :key="cat.value+v.id" :to="`/vendors/${v.id}`" class="vendor-card">
            <div class="vendor-card-img">{{ vendorEmoji(v.name) }}</div>
            <div class="vendor-card-body">
              <div class="vendor-card-name">{{ v.name }}</div>
              <div class="vendor-card-rating">
                <span v-if="v.rating">★ {{ v.rating }}</span>
                <span class="badge badge-halal">HALAL</span>
              </div>
            </div>
          </router-link>
        </div>
      </template>

      <!-- All vendors grid if searching -->
      <template v-if="search">
        <div class="section-row"><span class="section-row-title">Search Results</span></div>
        <div style="padding:0 1rem 1rem">
          <div class="grid-2">
            <router-link v-for="v in filteredVendors" :key="'all'+v.id" :to="`/vendors/${v.id}`" class="vendor-card">
              <div class="vendor-card-img">{{ vendorEmoji(v.name) }}</div>
              <div class="vendor-card-body">
                <div class="vendor-card-name">{{ v.name }}</div>
                <div class="vendor-card-meta">📍 {{ v.location }}</div>
                <div class="vendor-card-rating">
                  <span v-if="v.rating">★ {{ v.rating }}</span>
                  <span :class="['badge', v.isOpen?'badge-active':'badge-inactive']" style="font-size:0.65rem">{{ v.isOpen?'Open':'Closed' }}</span>
                </div>
              </div>
            </router-link>
          </div>
        </div>
      </template>
    </template>

    <div style="height:80px"></div>

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
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import axios from 'axios'
import { useAuthStore } from '@/stores/auth'
import { useCartStore } from '@/stores/cart'
import { useNotificationStore } from '@/stores/notifications'

const auth   = useAuthStore()
const cart   = useCartStore()

const notif  = useNotificationStore()
const showNotif = ref(false)

function toggleNotif() {
  showNotif.value = !showNotif.value
}

async function handleNotifClick(n) {
  if (!n.isRead) await notif.markAsRead(n.id)
}

const search = ref('')
const selectedCat = ref('all')
const vendors = ref([])
const loading = ref(true)
const vendorsRef = ref(null)
const allMenuItems = ref([])

const categories = [
  { value: 'all',        label: 'All' },
  { value: 'rice',       label: '🍚 Rice' },
  { value: 'noodles',    label: '🍜 Noodles' },
  { value: 'drinks',     label: '🥤 Drinks' },
  { value: 'snacks',     label: '🍡 Snacks' },
  { value: 'halal',      label: '☪️ Halal' },
  { value: 'vegetarian', label: '🥦 Vegetarian' },
]

const visibleCategories = computed(() =>
  selectedCat.value === 'all'
    ? [{ value: 'rice', label: 'Rice' }, { value: 'noodles', label: 'Noodles' }, { value: 'drinks', label: 'Drinks' }, { value: 'snacks', label: 'Snacks' }]
    : []
)

const filteredVendors = computed(() => {
  let list = vendors.value
  if (search.value) list = list.filter(v => v.name.toLowerCase().includes(search.value.toLowerCase()) || v.location?.toLowerCase().includes(search.value.toLowerCase()))
  return list
})

const vendorsByCategory = computed(() => ({
  rice:    filteredVendors.value.filter(v => vendorEmoji(v.name) === '🍚'),
  noodles: filteredVendors.value.filter(v => vendorEmoji(v.name) === '🍜'),
  drinks:  filteredVendors.value.filter(v => vendorEmoji(v.name) === '🥤'),
  snacks:  filteredVendors.value.filter(() => false),
}))

const featuredItems = computed(() => {
  if (allMenuItems.value.length) return allMenuItems.value.filter(i => i.isAvailable !== false)
  return []
})

function vendorEmoji(name) {
  const n = (name || '').toLowerCase()
  if (n.includes('minuman') || n.includes('drink') || n.includes('segar') || n.includes('tebu') || n.includes('bandung')) return '🥤'
  if (n.includes('mee') || n.includes('noodle') || n.includes('laksa')) return '🍜'
  if (n.includes('nasi') || n.includes('rice') || n.includes('arif') || n.includes('mak') || n.includes('warung')) return '🍚'
  if (n.includes('pisang') || n.includes('snack') || n.includes('goreng')) return '🍡'
  return '🍽️'
}

function itemEmoji(category) {
  return { rice:'🍚', noodles:'🍜', drinks:'🥤', snacks:'🍡', other:'🍽️' }[category] || '🍽️'
}

function scrollToVendors() {
  vendorsRef.value?.scrollIntoView({ behavior: 'smooth', block: 'start' })
}

onMounted(async () => {
  notif.fetchNotifications()
  try {
    const { data } = await axios.get('/api/vendors')
    vendors.value = data
    const topVendors = data.slice(0, 5)
    const menus = await Promise.allSettled(
      topVendors.map(v => axios.get(`/api/vendors/${v.id}/menu`).then(r => r.data.map(item => ({ ...item, vendorName: v.name, vendorId: v.id }))))
    )
    allMenuItems.value = menus
      .filter(r => r.status === 'fulfilled')
      .flatMap(r => r.value)
  } finally { loading.value = false }
})
</script>
