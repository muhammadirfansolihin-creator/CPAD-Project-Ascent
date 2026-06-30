<template>
  <div>
    <nav class="navbar">
      <div>
        <div class="navbar-brand"><img src="/favicon.png" alt="CampusEats Logo" class="navbar-brand-icon" /> CampusEats</div>
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
        <router-link to="/profile" class="navbar-icon-btn">👤</router-link>
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

    <div v-if="banner" class="promo-banner" :class="banner.theme" :style="{ backgroundImage: `linear-gradient(rgba(0, 0, 0, 0.65), rgba(0, 0, 0, 0.65)), url(${getBackgroundImage(banner.theme)})`, backgroundSize: 'cover', backgroundPosition: 'center'}">
        <div class="promo-banner-text-content">
        <h4 class="promo-banner-title">{{ banner.title }}</h4><br>
        <p class="promo-banner-subtitle">{{ banner.subtitle }}</p>
        </div>
      <button class="promo-btn" @click="scrollToVendors">Order Now</button>
    </div>
    <div v-else class="promo-banner-fallback-box">
      <p> No active promotion</p>
    </div>

    <div v-if="loading" class="loading"><div class="spinner"></div></div>
    <template v-else-if="!search && !filteredVendors.length && !Object.values(vendorsByCategory).some(arr => arr.length)">
      <div class="empty"><div class="empty-icon">🍽️</div><p>No vendors found</p></div>
    </template>
    <template v-else>
      <!-- Today's Special: food item cards -->
      <template v-if="!search && selectedCat === 'all'">
        <div class="section-row">
          <span class="section-row-title">Today's Special</span>
          <router-link to="/today-special" class="section-row-link">See all →</router-link>
        </div>
        <div :class="expandedSection === 'featured' ? 'grid-2' : 'horizontal-scroll'" :ref="expandedSection === 'featured' ? null : 'vendorsRef'" :style="expandedSection === 'featured' ? 'padding:0 1rem 1rem' : ''">
          <router-link
            v-for="item in (expandedSection === 'featured' ? featuredItems : featuredItems.slice(0, 6))"
            :key="'fi'+item.vendorId+item.name"
            :to="`/vendors/${item.vendorId}`"
            class="vendor-card"
          >
            <div class="vendor-card-img" :style="(item.imageUrl || foodImage(item.category)) ? `background-image:url('${item.imageUrl || foodImage(item.category)}');background-size:cover;background-position:center;` : ''">
              <span v-if="!item.imageUrl && !foodImage(item.category)">{{ itemEmoji(item.category) }}</span>
            </div>
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
      </template>

      <!-- Category sections (vendor cards) - Hide when searching -->
      <template v-if="!search">
        <template v-for="cat in visibleCategories" :key="cat.value">
          <div v-if="vendorsByCategory[cat.value]?.length" class="section-row" style="margin-top:0.5rem">
            <span class="section-row-title">{{ cat.label }}</span>
            <router-link :to="`/category/${cat.value}`" class="section-row-link">See all →</router-link>
          </div>
          <div v-if="vendorsByCategory[cat.value]?.length" :class="expandedSection === cat.value ? 'grid-2' : 'horizontal-scroll'" :style="expandedSection === cat.value ? 'padding:0 1rem 1rem' : ''">
            <router-link v-for="v in vendorsByCategory[cat.value]" :key="cat.value+v.id" :to="`/vendors/${v.id}`" class="vendor-card">
              <div class="vendor-card-img" :style="{ backgroundImage: `url(${vendorImage(v.id)})`, backgroundSize: 'cover', backgroundPosition: 'center'}">
                <span v-if="!vendorImage(v.id)">{{ getCategoryEmoji(cat.value) }}</span>
              </div>
              <div class="vendor-card-body">
                <div class="vendor-card-name">{{ v.name }}</div>
                <div class="vendor-card-meta">📍 {{ v.location }}</div>
                <div class="vendor-card-rating">
                  <span v-if="v.rating">★ {{ v.rating }}</span>
                  <span :class="['badge', v.isOpen ? 'badge-active' : 'badge-inactive']" style="font-size:0.65rem">
                    {{ v.isOpen ? 'Open' : 'Closed' }}
                  </span>
                  <span class="badge badge-halal">HALAL</span>
                </div>
              </div>
            </router-link>
          </div>
        </template>
      </template>

      <!-- Combined Search Results -->
      <template v-if="search">
        <div class="section-row">
          <span class="section-row-title">Search Results for "{{ search }}"</span>
        </div>
        
        <!-- Meals Section -->
        <template v-if="menuSearchResults.length">
          <div style="padding:0 1rem 0.5rem">
            <h4 style="margin:0 0 0.5rem 0; color: var(--color-muted); font-size:0.9rem">🍽️ Meals ({{ menuSearchResults.length }})</h4>
            <div class="grid-2">
              <router-link
                v-for="item in menuSearchResults"
                :key="'menu'+item.id"
                :to="`/vendors/${item.vendorId}`"
                class="vendor-card"
              >
                <div class="vendor-card-img" :style="(item.imageUrl || foodImage(item.category)) ? `background-image:url('${item.imageUrl || foodImage(item.category)}');background-size:cover;background-position:center;` : ''">
                  <span v-if="!item.imageUrl && !foodImage(item.category)">{{ itemEmoji(item.category) }}</span>
                </div>
                <div class="vendor-card-body">
                  <div class="vendor-card-name">{{ item.name }}</div>
                  <div class="vendor-card-meta" style="color:var(--color-muted);font-size:0.72rem">From {{ item.vendorName }}</div>
                  <div class="vendor-card-rating">
                    <span style="color:var(--color-primary);font-weight:700;font-size:0.8rem">RM {{ Number(item.price).toFixed(2) }}</span>
                    <span class="badge badge-halal">HALAL</span>
                  </div>
                </div>
              </router-link>
            </div>
          </div>
        </template>
        <template v-else-if="searchingMenu">
          <div style="padding:0 1rem; text-align:center; color:var(--color-muted)">Searching meals...</div>
        </template>

        <!-- Vendors Section -->
        <template v-if="filteredVendors.length">
          <div style="padding:0 1rem 0.5rem">
            <h4 style="margin:0 0 0.5rem 0; color: var(--color-muted); font-size:0.9rem">🏪 Vendors ({{ filteredVendors.length }})</h4>
            <div class="grid-2">
              <router-link v-for="v in filteredVendors" :key="'all'+v.id" :to="`/vendors/${v.id}`" class="vendor-card">
                <div class="vendor-card-img" :style="`background-image:url(${vendorImage(v.id)});background-size:cover;background-position:center;`">
                  <span v-if="!vendorImage(v.id)">{{ vendorEmoji(v.name) }}</span>
                </div>
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
        
        <!-- No Results -->
        <template v-if="!menuSearchResults.length && !filteredVendors.length && !searchingMenu">
          <div class="empty"><div class="empty-icon">🔍</div><p>No results found for "{{ search }}"</p></div>
        </template>
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
import { ref, computed, onMounted, watch } from 'vue'
import axios from 'axios'
import { useAuthStore } from '@/stores/auth'
import { useCartStore } from '@/stores/cart'
import { useNotificationStore } from '@/stores/notifications'

const banner = ref(null)
const auth = useAuthStore()
const cart = useCartStore()
const notif = useNotificationStore()
const showNotif = ref(false)

const backgrounds = {
  breakfast: 'https://images.unsplash.com/photo-1533089860892-a7c6f0a88666?q=80&w=1200&auto=format&fit=crop',
  dinner: 'https://images.unsplash.com/photo-1544025162-d76694265947?q=80&w=1200&auto=format&fit=crop',
  dessert: 'https://images.unsplash.com/photo-1551024601-bec78aea704b?q=80&w=1200&auto=format&fit=crop',
  default: 'https://images.unsplash.com/photo-1504674900247-0877df9cc836?q=80&w=1200&auto=format&fit=crop'
}

const menuSearchResults = ref([])
const searchingMenu = ref(false)
const expandedSection = ref(null)
const search = ref('')
const selectedCat = ref('all')
const vendors = ref([])
const loading = ref(true)
const vendorsRef = ref(null)
const allMenuItems = ref([])
const categoryLoading = ref(false)

// Category data from API
const vendorsByCategory = ref({
  rice: [],
  noodles: [],
  drinks: [],
  snacks: [],
  vegetarian: [],
  other: []
})

const categories = [
  { value: 'all', label: 'All' },
  { value: 'rice', label: '🍚 Rice' },
  { value: 'noodles', label: '🍜 Noodles' },
  { value: 'drinks', label: '🥤 Drinks' },
  { value: 'snacks', label: '🍡 Snacks' },
  { value: 'halal', label: '☪️ Halal' },
  { value: 'vegetarian', label: '🥦 Vegetarian' },
]

const ALL_CATEGORIES = [
  { value: 'rice', label: 'Rice' },
  { value: 'noodles', label: 'Noodles' },
  { value: 'drinks', label: 'Drinks' },
  { value: 'snacks', label: 'Snacks' },
  { value: 'vegetarian', label: 'Vegetarian' },
  { value: 'other', label: 'Other' },
]

const visibleCategories = computed(() =>
  selectedCat.value === 'all'
    ? ALL_CATEGORIES
    : ALL_CATEGORIES.filter(c => c.value === selectedCat.value)
)

function getCategoryEmoji(category) {
  const emojis = {
    rice: '🍚',
    noodles: '🍜',
    drinks: '🥤',
    snacks: '🍡',
    vegetarian: '🥦',
    other: '🍽️'
  }
  return emojis[category] || '🍽️'
}

// Fetch vendors by category from API
async function fetchVendorsByCategory(category) {
  if (categoryLoading.value) return
  
  try {
    categoryLoading.value = true
    const { data } = await axios.get(`/api/vendors/category/${category}`)
    vendorsByCategory.value[category] = data
  } catch (error) {
    console.error(`Error fetching vendors for category ${category}:`, error)
    vendorsByCategory.value[category] = []
  } finally {
    categoryLoading.value = false
  }
}

async function loadAllCategories() {
  const categories = ['rice', 'noodles', 'drinks', 'snacks', 'vegetarian', 'other']
  for (const cat of categories) {
    await fetchVendorsByCategory(cat)
  }
}

// Watch for category changes
watch(selectedCat, (newCat) => {
  if (newCat === 'all') {
    loadAllCategories()
  } else {
    fetchVendorsByCategory(newCat)
  }
})

const filteredVendors = computed(() => {
  if (!search.value) return []
  
  return vendors.value.filter(v =>
    v.name.toLowerCase().includes(search.value.toLowerCase()) ||
    v.location?.toLowerCase().includes(search.value.toLowerCase())
  )
})

const featuredItems = computed(() => {
  let list = allMenuItems.value.filter(i => i.isAvailable !== false)
  if (selectedCat.value !== 'all') {
    list = list.filter(i => (i.category || '').toLowerCase() === selectedCat.value)
  }
  return list
})

async function fetchActiveBanner() {
  try {
    const {data} = await axios.get('/api/active-banner')
    console.log('Active banner:', data)
    if(data){
      banner.value = {
        title: data.title,
        subtitle: data.subtitle,
        theme: data.theme
      }
    } else{
      banner.value = null
    }
  } catch (error) {
    console.error('Error fetching active banner:', error);
  }
}

const FOOD_IMAGES = {
  rice: '/rice.jpg',
  noodles: '/noodles.jpg',
  drinks: '/drinks.jpg',
  snacks: '/snacks.jpg',
  other: '/other.jpg'
}

const VENDOR_IMAGES = {
  1: '/canteen.jpg',
  2: '/warung.jpg',
  3: '/drinkstall.jfif'
}

function vendorImage(id) {
  return VENDOR_IMAGES[id] || ''
}

function foodImage(category) {
  return FOOD_IMAGES[(category|| '').toLowerCase().trim()] || FOOD_IMAGES.other
}

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

function toggleNotif() {
  showNotif.value = !showNotif.value
}

async function handleNotifClick(n) {
  if (!n.isRead) await notif.markAsRead(n.id)
}

function toggleSection(key) {
  expandedSection.value = expandedSection.value === key ? null : key
}

onMounted(async () => {
  await fetchActiveBanner()
  await notif.fetchNotifications()
  
  try {
    const { data } = await axios.get('/api/vendors')
    vendors.value = data
    
    // Load all categories
    await loadAllCategories()
    
    const topVendors = data.slice(0, 5)
    const menus = await Promise.allSettled(
      topVendors.map(v => axios.get(`/api/vendors/${v.id}/menu`).then(r => r.data.map(item => ({ ...item, vendorName: v.name, vendorId: v.id }))))
    )
    allMenuItems.value = menus
      .filter(r => r.status === 'fulfilled')
      .flatMap(r => r.value)
  } finally { 
    loading.value = false 
  }
})

let searchDebounce = null
watch(search, (val) => {
  clearTimeout(searchDebounce)
  if (!val) { menuSearchResults.value = []; return }
  searchDebounce = setTimeout(async () => {
    searchingMenu.value = true
    try {
      const { data } = await axios.get('/api/menu-items/search', { params: { search: val } })
      menuSearchResults.value = data
    } finally {
      searchingMenu.value = false
    }
  }, 300)
})
</script>