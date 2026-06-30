<template>
  <div class="page-wrap">
    <nav class="navbar">
      <div class="navbar-left">
        <button class="navbar-back-btn" @click="$router.push('/')">
          <ChevronLeft :size="20" />
        </button>
        <div class="navbar-brand">{{ catLabel }}</div>
      </div>
    </nav>

    <!-- Hero -->
    <div class="cat-hero">
      <div class="cat-hero-text">
        <div class="cat-hero-eyebrow">Browse</div>
        <h1 class="cat-hero-title">{{ catLabel }}</h1>
        <p class="cat-hero-sub" v-if="!loading">
          {{ vendors.length }} vendor{{ vendors.length !== 1 ? 's' : '' }} available
        </p>
      </div>
      <div class="cat-hero-icon">{{ catEmoji }}</div>
    </div>

    <!-- Content -->
    <div class="list-wrap">
      <div v-if="loading" class="loading"><div class="spinner"></div></div>

      <div v-else-if="!vendors.length" class="empty">
        <div class="empty-icon"><UtensilsCrossed :size="40" /></div>
        <p>No vendors in this category</p>
      </div>

      <div v-else class="vendor-list">
        <router-link
          v-for="v in vendors"
          :key="v.id"
          :to="`/vendors/${v.id}`"
          class="vl-item"
        >
          <!-- Thumbnail -->
          <div
            class="vl-thumb"
            :style="vendorImage(v.id) ? `background-image:url(${vendorImage(v.id)})` : ''"
          >
            <span v-if="!vendorImage(v.id)" class="vl-thumb-emoji">{{ vendorEmoji(v.name) }}</span>
          </div>

          <!-- Info -->
          <div class="vl-info">
            <div class="vl-name">{{ v.name }}</div>
            <div class="vl-location">
              <MapPin :size="10" />
              <span>{{ v.location }}</span>
            </div>
            <div class="vl-meta">
              <template v-if="v.rating">
                <Star :size="10" fill="#d97706" stroke="none" />
                <span class="vl-rating">{{ Number(v.rating).toFixed(1) }}</span>
                <span class="vl-sep">·</span>
              </template>
              <span class="vl-tags">{{ catLabel }}, Halal</span>
            </div>
          </div>

          <!-- Right side: badge + arrow inline -->
          <div class="vl-right">
            <span :class="['vl-badge', v.isOpen ? 'vl-badge--open' : 'vl-badge--closed']">
              {{ v.isOpen ? 'OPEN' : 'CLOSED' }}
            </span>
            <ChevronRight :size="15" class="vl-chevron" />
          </div>
        </router-link>
      </div>
    </div>

    <div style="height:80px"></div>

    <nav class="bottom-nav">
      <router-link to="/" class="bottom-nav-item"><Home :size="22" />Home</router-link>
      <router-link to="/orders" class="bottom-nav-item"><ClipboardList :size="22" />Orders</router-link>
    </nav>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useRoute } from 'vue-router'
import axios from 'axios'
import { ChevronLeft, ChevronRight, Home, ClipboardList, MapPin, Star, UtensilsCrossed } from 'lucide-vue-next'

const route   = useRoute()
const vendors = ref([])
const loading = ref(true)

const LABELS  = { rice:'Rice', noodles:'Noodles', drinks:'Drinks', snacks:'Snacks', other:'Other', halal:'Halal', vegetarian:'Vegetarian' }
const EMOJIS  = { rice:'🍚', noodles:'🍜', drinks:'🥤', snacks:'🍡', vegetarian:'🥦', halal:'☪️', other:'🍽️' }
const IMAGES  = { 1:'/canteen.jpg', 2:'/warung.jpg', 3:'/drinkstall.jfif' }

const catLabel = computed(() => LABELS[route.params.catValue] || route.params.catValue || 'Category')
const catEmoji = computed(() => EMOJIS[route.params.catValue] || '🍽️')

function vendorImage(id) { return IMAGES[id] || '' }
function vendorEmoji(name) {
  const n = (name||'').toLowerCase()
  if (n.includes('minuman')||n.includes('drink')||n.includes('segar')) return '🥤'
  if (n.includes('mee')||n.includes('noodle')||n.includes('laksa')) return '🍜'
  if (n.includes('nasi')||n.includes('rice')||n.includes('warung')) return '🍚'
  return '🍽️'
}

async function load() {
  loading.value = true
  try {
    const cat = route.params.catValue
    if (!cat) return
    if (cat === 'halal') {
      vendors.value = (await axios.get('/api/vendors')).data
    } else {
      try {
        vendors.value = (await axios.get(`/api/vendors/category/${cat}`)).data
      } catch {
        vendors.value = (await axios.get('/api/vendors')).data
      }
    }
  } catch { vendors.value = [] }
  finally  { loading.value = false }
}

watch(() => route.params.catValue, load)
onMounted(load)
</script>

<style scoped>
.page-wrap { min-height: 100vh; background: var(--color-bg); }

/* Navbar */
.navbar-left { display: flex; align-items: center; gap: 0.5rem; }
.navbar-back-btn {
  background: rgba(255,255,255,0.15); border: none; color: #fff;
  width: 34px; height: 34px; border-radius: 50%; cursor: pointer;
  display: flex; align-items: center; justify-content: center;
}
.navbar-back-btn:hover { background: rgba(255,255,255,0.25); }

/* Hero */
.cat-hero {
  background: linear-gradient(135deg, var(--color-primary) 0%, #5a0e0e 100%);
  padding: 1.25rem 1.5rem 1.1rem;
  display: flex; align-items: center; justify-content: space-between;
  overflow: hidden; position: relative;
}
.cat-hero::after {
  content:''; position:absolute; width:200px; height:200px; border-radius:50%;
  background:rgba(255,255,255,0.05); top:-60px; right:-40px; pointer-events:none;
}
.cat-hero-text { position: relative; z-index: 1; }
.cat-hero-eyebrow { font-size:0.65rem; font-weight:700; color:rgba(255,255,255,0.55); text-transform:uppercase; letter-spacing:0.12em; margin-bottom:0.1rem; }
.cat-hero-title { font-size:1.6rem; font-weight:800; color:#fff; letter-spacing:-0.02em; line-height:1.15; margin:0; }
.cat-hero-sub { font-size:0.75rem; color:rgba(255,255,255,0.65); margin-top:0.3rem; margin-bottom:0; }
.cat-hero-icon { font-size:3.5rem; opacity:0.25; z-index:1; flex-shrink:0; }

/* List container */
.list-wrap { padding: 1rem; }

/* Vendor list — card group */
.vendor-list {
  background: var(--color-surface);
  border-radius: var(--radius);
  border: 1px solid var(--color-border);
  box-shadow: var(--shadow-xs);
  overflow: hidden;
}

.vl-item {
  display: flex; align-items: center; gap: 0.85rem;
  padding: 0.9rem 1rem;
  text-decoration: none; color: inherit;
  border-bottom: 1px solid var(--color-border);
  transition: background 0.14s;
}
.vl-item:last-child { border-bottom: none; }
.vl-item:hover { background: var(--color-primary-50); }

/* Thumbnail */
.vl-thumb {
  width: 64px; height: 64px; border-radius: 0.65rem; flex-shrink: 0;
  background: linear-gradient(135deg, #f0e8d8, #e4d4c0) center/cover no-repeat;
  display: flex; align-items: center; justify-content: center;
  overflow: hidden;
}
.vl-thumb-emoji { font-size: 1.7rem; }

/* Info */
.vl-info { flex: 1; min-width: 0; }
.vl-name { font-weight: 700; font-size: 0.93rem; color: var(--color-text); margin-bottom: 0.18rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.vl-location { display: flex; align-items: center; gap: 0.22rem; font-size: 0.73rem; color: var(--color-muted); margin-bottom: 0.18rem; }
.vl-meta { display: flex; align-items: center; gap: 0.22rem; font-size: 0.7rem; }
.vl-rating { font-weight: 600; color: #b45309; }
.vl-sep { color: var(--color-border-strong); }
.vl-tags { color: var(--color-muted); }

/* Right: badge + chevron on same row */
.vl-right { display: flex; align-items: center; gap: 0.35rem; flex-shrink: 0; }
.vl-badge {
  font-size: 0.62rem; font-weight: 800; padding: 0.2rem 0.55rem;
  border-radius: 999px; letter-spacing: 0.06em; text-transform: uppercase; white-space: nowrap;
}
.vl-badge--open   { background: #dcfce7; color: #166534; }
.vl-badge--closed { background: #fee2e2; color: #991b1b; }
.vl-chevron { color: #ccc; flex-shrink: 0; }
</style>
