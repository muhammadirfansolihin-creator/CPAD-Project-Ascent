<template>
  <div>
    <nav class="navbar">
      <div class="navbar-left">
        <button class="navbar-back-btn" @click="$router.push('/')">‹</button>
        <div class="navbar-brand">Today's Special</div>
      </div>
    </nav>

    <div class="page">
      <div v-if="loading" class="loading"><div class="spinner"></div></div>

      <div v-else-if="!items.length" class="empty">
        <div class="empty-icon">🍽️</div>
        <p>No special items right now</p>
      </div>

      <div v-else class="see-all-list">
        <router-link
          v-for="item in items"
          :key="item.vendorId + item.name"
          :to="`/vendors/${item.vendorId}`"
          class="menu-item"
        >
          <div
            class="menu-item-img"
            :style="(item.imageUrl || foodImage(item.category)) ? `background-image:url('${item.imageUrl || foodImage(item.category)}');background-size:cover;background-position:center;` : ''"
          >
            <span v-if="!item.imageUrl && !foodImage(item.category)">{{ itemEmoji(item.category) }}</span>
          </div>
          <div class="menu-item-info">
            <div class="menu-item-name">{{ item.name }}</div>
            <div class="menu-item-desc">From {{ item.vendorName }}</div>
            <div class="menu-item-price">RM {{ Number(item.price).toFixed(2) }}</div>
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
import { ref, onMounted } from 'vue'
import axios from 'axios'

const items   = ref([])
const loading = ref(true)

const FOOD_IMAGES = {
  rice: '/rice.jpg',
  noodles: '/noodles.jpg',
  drinks: '/drinks.jpg',
  snacks: '/snacks.jpg',
  other: '/other.jpg'
}
function foodImage(category) {
  return FOOD_IMAGES[(category || '').toLowerCase().trim()] || FOOD_IMAGES.other
}
function itemEmoji(category) {
  return { rice: '🍚', noodles: '🍜', drinks: '🥤', snacks: '🍡', other: '🍽️' }[category] || '🍽️'
}

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