<template>
  <div>
    <nav class="navbar">
      <div class="navbar-left">
        <button class="navbar-back-btn" @click="$router.push('/')"><ChevronLeft :size="22" /></button>
        <div class="navbar-brand">{{ catLabel }}</div>
      </div>
    </nav>

    <div class="page">
      <div v-if="loading" class="loading"><div class="spinner"></div></div>

      <div v-else-if="!vendors.length" class="empty">
        <div class="empty-icon"><UtensilsCrossed :size="40" /></div>
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
            <div class="menu-item-desc" style="display:flex;align-items:center;gap:0.2rem"><MapPin :size="11" /> {{ v.location }}</div>
            <div class="menu-item-price" v-if="v.rating" style="display:flex;align-items:center;gap:0.2rem">
              <Star :size="11" fill="#d97706" stroke="none" /> {{ v.rating }}
            </div>
          </div>
        </router-link>
      </div>
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
import { ref, computed, onMounted, watch } from 'vue'
import { useRoute } from 'vue-router'
import axios from 'axios'
import { ChevronLeft, Home, ClipboardList, MapPin, Star, UtensilsCrossed } from 'lucide-vue-next'

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

function vendorImage(id) { return VENDOR_IMAGES[id] || '' }

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
    if (!cat) { vendors.value = []; return }
    
    if (cat === 'halal') {
      const { data } = await axios.get('/api/vendors')
      vendors.value = data.filter(v => v.isHalal || true)
    } else {
      try {
        const { data } = await axios.get(`/api/vendors/category/${cat}`)
        vendors.value = data
      } catch (apiError) {
        const { data } = await axios.get('/api/vendors')
        const vendorsWithCategories = await Promise.all(
          data.map(async (vendor) => {
            try {
              const menuRes = await axios.get(`/api/vendors/${vendor.id}/menu`)
              const hasCategory = menuRes.data.some(item => item.category === cat && item.inStock !== false)
              return { ...vendor, hasCategory }
            } catch (err) {
              return { ...vendor, hasCategory: false }
            }
          })
        )
        vendors.value = vendorsWithCategories.filter(v => v.hasCategory)
      }
    }
  } catch (err) {
    error.value = err.message
    vendors.value = []
  } finally {
    loading.value = false
  }
}

watch(() => route.params.catValue, () => { loadVendors() })
onMounted(() => { loadVendors() })
</script>
