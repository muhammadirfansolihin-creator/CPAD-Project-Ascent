<template>
  <div>
    <nav class="navbar">
      <div class="navbar-left">
        <button class="navbar-back-btn" @click="$router.push('/')"><ChevronLeft :size="22" /></button>
        <div class="navbar-brand">Today's Special</div>
      </div>
    </nav>

    <div class="page">
      <div v-if="loading" class="loading"><div class="spinner"></div></div>

      <div v-else-if="!items.length" class="empty">
        <div class="empty-icon"><UtensilsCrossed :size="40" /></div>
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
import { ref, onMounted } from 'vue'
import axios from 'axios'
import { ChevronLeft, Home, ClipboardList, UtensilsCrossed } from 'lucide-vue-next'

const items   = ref([])
const loading = ref(true)

const FOOD_IMAGES = {
  rice: '/rice.jpg', noodles: '/noodles.jpg', drinks: '/drinks.jpg', snacks: '/snacks.jpg', other: '/other.jpg'
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
