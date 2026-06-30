<template>
  <div class="page-wrap">
    <nav class="navbar">
      <div class="navbar-left">
        <button class="navbar-back-btn" @click="$router.push('/')">
          <ChevronLeft :size="20" />
        </button>
        <div class="navbar-brand">Today's Special</div>
      </div>
    </nav>

    <!-- Hero -->
    <div class="special-hero">
      <div class="special-hero-text">
        <div class="special-hero-eyebrow">Featured Today</div>
        <h1 class="special-hero-title">Today's Special</h1>
        <p class="special-hero-sub" v-if="!loading">
          {{ items.length }} item{{ items.length !== 1 ? 's' : '' }} available now
        </p>
      </div>
      <div class="special-hero-icon">🍽️</div>
    </div>

    <!-- Content -->
    <div class="list-wrap">
      <div v-if="loading" class="loading"><div class="spinner"></div></div>

      <div v-else-if="!items.length" class="empty">
        <div class="empty-icon"><UtensilsCrossed :size="40" /></div>
        <p>No special items right now</p>
      </div>

      <div v-else class="special-list">
        <router-link
          v-for="item in items"
          :key="item.vendorId + item.name"
          :to="`/vendors/${item.vendorId}`"
          class="sl-item"
        >
          <!-- Thumbnail -->
          <div
            class="sl-thumb"
            :style="(item.imageUrl || foodImage(item.category))
              ? `background-image:url('${item.imageUrl || foodImage(item.category)}')`
              : ''"
          >
            <span v-if="!item.imageUrl && !foodImage(item.category)" class="sl-thumb-emoji">
              {{ itemEmoji(item.category) }}
            </span>
          </div>

          <!-- Info -->
          <div class="sl-info">
            <div class="sl-name">{{ item.name }}</div>
            <div class="sl-vendor">
              <Store :size="10" />
              <span>{{ item.vendorName }}</span>
            </div>
            <div class="sl-price">RM {{ Number(item.price).toFixed(2) }}</div>
          </div>

          <!-- Right: order badge + arrow inline -->
          <div class="sl-right">
            <span class="sl-order-badge">Order Now</span>
            <ChevronRight :size="15" class="sl-chevron" />
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
import { ref, onMounted } from 'vue'
import axios from 'axios'
import { ChevronLeft, ChevronRight, Home, ClipboardList, UtensilsCrossed, Store } from 'lucide-vue-next'

const items   = ref([])
const loading = ref(true)

const FOOD_IMAGES = { rice:'/rice.jpg', noodles:'/noodles.jpg', drinks:'/drinks.jpg', snacks:'/snacks.jpg', other:'/other.jpg' }
function foodImage(cat) { return FOOD_IMAGES[(cat||'').toLowerCase().trim()] || FOOD_IMAGES.other }
function itemEmoji(cat) { return {rice:'🍚',noodles:'🍜',drinks:'🥤',snacks:'🍡',other:'🍽️'}[cat]||'🍽️' }

onMounted(async () => {
  try {
    const { data: vendors } = await axios.get('/api/vendors')
    const menus = await Promise.allSettled(
      vendors.map(v =>
        axios.get(`/api/vendors/${v.id}/menu`).then(r =>
          r.data.map(item => ({ ...item, vendorName: v.name, vendorId: v.id }))
        )
      )
    )
    items.value = menus
      .filter(r => r.status === 'fulfilled')
      .flatMap(r => r.value)
      .filter(i => i.isAvailable !== false)
  } finally {
    loading.value = false
  }
})
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
.special-hero {
  background: linear-gradient(135deg, var(--color-primary) 0%, #5a0e0e 100%);
  padding: 1.25rem 1.5rem 1.1rem;
  display: flex; align-items: center; justify-content: space-between;
  overflow: hidden; position: relative;
}
.special-hero::after {
  content:''; position:absolute; width:200px; height:200px; border-radius:50%;
  background:rgba(255,255,255,0.05); top:-60px; right:-40px; pointer-events:none;
}
.special-hero-text { position: relative; z-index: 1; }
.special-hero-eyebrow { font-size:0.65rem; font-weight:700; color:rgba(255,255,255,0.55); text-transform:uppercase; letter-spacing:0.12em; margin-bottom:0.1rem; }
.special-hero-title { font-size:1.6rem; font-weight:800; color:#fff; letter-spacing:-0.02em; line-height:1.15; margin:0; }
.special-hero-sub { font-size:0.75rem; color:rgba(255,255,255,0.65); margin-top:0.3rem; margin-bottom:0; }
.special-hero-icon { font-size:3.5rem; opacity:0.25; z-index:1; flex-shrink:0; }

/* List container */
.list-wrap { padding: 1rem; }

/* Special list — card group */
.special-list {
  background: var(--color-surface);
  border-radius: var(--radius);
  border: 1px solid var(--color-border);
  box-shadow: var(--shadow-xs);
  overflow: hidden;
}

.sl-item {
  display: flex; align-items: center; gap: 0.85rem;
  padding: 0.9rem 1rem;
  text-decoration: none; color: inherit;
  border-bottom: 1px solid var(--color-border);
  transition: background 0.14s;
}
.sl-item:last-child { border-bottom: none; }
.sl-item:hover { background: var(--color-primary-50); }

/* Thumbnail */
.sl-thumb {
  width: 64px; height: 64px; border-radius: 0.65rem; flex-shrink: 0;
  background: linear-gradient(135deg, #f0e8d8, #e4d4c0) center/cover no-repeat;
  display: flex; align-items: center; justify-content: center;
  overflow: hidden;
}
.sl-thumb-emoji { font-size: 1.7rem; }

/* Info */
.sl-info { flex: 1; min-width: 0; }
.sl-name { font-weight: 700; font-size: 0.93rem; color: var(--color-text); margin-bottom: 0.18rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.sl-vendor { display: flex; align-items: center; gap: 0.22rem; font-size: 0.73rem; color: var(--color-muted); margin-bottom: 0.18rem; }
.sl-price { font-weight: 800; color: var(--color-primary); font-size: 0.88rem; }

/* Right: badge + chevron inline */
.sl-right { display: flex; align-items: center; gap: 0.35rem; flex-shrink: 0; }
.sl-order-badge {
  font-size: 0.62rem; font-weight: 800; padding: 0.2rem 0.55rem;
  border-radius: 999px; letter-spacing: 0.04em; white-space: nowrap;
  background: #dcfce7; color: #166534;
}
.sl-chevron { color: #ccc; flex-shrink: 0; }
</style>
