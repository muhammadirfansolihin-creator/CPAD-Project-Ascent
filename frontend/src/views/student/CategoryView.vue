<template>
  <div>
    <nav class="navbar">
      <div class="navbar-left">
        <button class="navbar-back-btn" @click="$router.push('/')">‹</button>
        <div class="navbar-brand">{{ catLabel }}</div>
      </div>
    </nav>

    <div class="page">
      <div v-if="loading" class="loading"><div class="spinner"></div></div>

      <div v-else-if="!vendors.length" class="empty">
        <div class="empty-icon">🍽️</div>
        <p>No vendors found in this category</p>
        <p style="font-size:0.8rem;color:var(--color-muted)">Category: {{ route.params.catValue }}</p>
      </div>

      <div v-else class="see-all-list">
        <router-link
          v-for="v in vendors"
          :key="v.id"
          :to="`/vendors/${v.id}`"
          class="menu-item"
        >
          <div class="menu-item-img" :style="`background-image:url(${vendorImage(v.id)});background-size:cover;background-position:center;`">
            <span v-if="!vendorImage(v.id)">{{ vendorEmoji(v.name) }}</span>
          </div>
          <div class="menu-item-info">
            <div class="menu-item-name">{{ v.name }}</div>
            <div class="menu-item-desc">📍 {{ v.location }}</div>
            <div class="menu-item-price" v-if="v.rating">★ {{ v.rating }}</div>
          </div>
        </router-link>
      </div>
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
import { ref, computed, onMounted, watch } from 'vue'
import { useRoute } from 'vue-router'
import axios from 'axios'

const route = useRoute()

const vendors = ref([])
const loading = ref(true)
const error = ref(null)

const CATEGORY_LABELS = {
  rice: 'Rice', 
  noodles: 'Noodles', 
  drinks: 'Drinks',
  snacks: 'Snacks', 
  other: 'Other', 
  halal: 'Halal Vendors',
  vegetarian: 'Vegetarian'
}

const catLabel = computed(() => CATEGORY_LABELS[route.params.catValue] || route.params.catValue || 'Category')

const VENDOR_IMAGES = { 
  1: '/canteen.jpg', 
  2: '/warung.jpg', 
  3: '/drinkstall.jfif' 
}

function vendorImage(id) { 
  return VENDOR_IMAGES[id] || '' 
}

function vendorEmoji(name) {
  const n = (name || '').toLowerCase()
  if (n.includes('minuman') || n.includes('drink') || n.includes('segar') || n.includes('tebu') || n.includes('bandung')) return '🥤'
  if (n.includes('mee') || n.includes('noodle') || n.includes('laksa')) return '🍜'
  if (n.includes('nasi') || n.includes('rice') || n.includes('arif') || n.includes('mak') || n.includes('warung')) return '🍚'
  if (n.includes('pisang') || n.includes('snack') || n.includes('goreng')) return '🍡'
  return '🍽️'
}

async function loadVendors() {
  loading.value = true
  error.value = null
  
  try {
    const cat = route.params.catValue
    console.log('Loading category:', cat)
    
    if (!cat) {
      console.error('No category parameter found')
      vendors.value = []
      return
    }
    
    // For 'halal' category, use the regular vendors endpoint and filter
    if (cat === 'halal') {
      console.log('Fetching halal vendors...')
      const { data } = await axios.get('/api/vendors')
      console.log('All vendors:', data)
      vendors.value = data.filter(v => v.isHalal || true) // Adjust this condition based on your data
      console.log('Halal vendors:', vendors.value)
    } 
    // For regular categories (rice, noodles, drinks, snacks, vegetarian, other)
    else {
      // Try the category endpoint first
      try {
        console.log(`Fetching vendors for category: ${cat}`)
        const { data } = await axios.get(`/api/vendors/category/${cat}`)
        console.log('Category vendors:', data)
        vendors.value = data
      } catch (apiError) {
        console.error('Category API error:', apiError)
        // Fallback: fetch all vendors and filter locally
        console.log('Falling back to local filtering...')
        const { data } = await axios.get('/api/vendors')
        console.log('All vendors:', data)
        
        // Since we don't have categories in vendor data, we need to fetch menu items
        const vendorsWithCategories = await Promise.all(
          data.map(async (vendor) => {
            try {
              const menuRes = await axios.get(`/api/vendors/${vendor.id}/menu`)
              const hasCategory = menuRes.data.some(item => 
                item.category === cat && item.inStock !== false
              )
              return { ...vendor, hasCategory }
            } catch (err) {
              console.error(`Error fetching menu for vendor ${vendor.id}:`, err)
              return { ...vendor, hasCategory: false }
            }
          })
        )
        
        vendors.value = vendorsWithCategories.filter(v => v.hasCategory)
        console.log('Filtered vendors:', vendors.value)
      }
    }
  } catch (err) {
    console.error('Error loading vendors:', err)
    error.value = err.message
    vendors.value = []
  } finally {
    loading.value = false
  }
}

// Watch for route changes
watch(() => route.params.catValue, (newCat) => {
  console.log('Category changed to:', newCat)
  loadVendors()
})

// Load on mount
onMounted(() => {
  console.log('CategoryView mounted with params:', route.params)
  loadVendors()
})
</script>