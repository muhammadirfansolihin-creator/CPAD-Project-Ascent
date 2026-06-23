<template>
  <div>
    <nav class="navbar">
      <div class="navbar-left">
        <button class="navbar-back-btn" @click="$router.push('/profile')">‹</button>
        <div class="navbar-brand">My Reviews</div>
      </div>
    </nav>

    <div class="page">

      <!-- Loading -->
      <div v-if="loading" class="loading"><div class="spinner"></div></div>

      <!-- Empty -->
      <div v-else-if="!reviews.length" class="empty">
        <div class="empty-icon">⭐</div>
        <p>You haven't written any reviews yet.</p>
        <router-link to="/orders" class="btn btn-primary" style="margin-top:1rem">
          View Orders
        </router-link>
      </div>

      <!-- Review cards -->
      <div v-else>
        <div class="my-review-summary">
          {{ reviews.length }} review{{ reviews.length !== 1 ? 's' : '' }} written
        </div>

        <div v-for="review in reviews" :key="review.id" class="order-card my-review-card">

          <!-- Header: vendor name + date -->
          <div class="order-card-header">
            <div style="flex:1;min-width:0">
              <div class="order-card-vendor">{{ review.vendorName }}</div>
              <div class="order-card-date">{{ formatDate(review.createdAt) }}</div>
            </div>
            <div class="my-review-stars">
              <span v-for="s in 5" :key="s" class="my-review-star">
                {{ s <= review.rating ? '⭐' : '☆' }}
              </span>
            </div>
          </div>

          <!-- Items ordered -->
          <div class="order-card-items my-review-items">
            🍽 {{ review.itemsOrdered }}
          </div>

          <!-- Comment -->
          <div v-if="review.comment" class="my-review-comment">
            "{{ review.comment }}"
          </div>
          <div v-else class="my-review-comment my-review-no-comment">
            No comment left
          </div>

        </div>
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

const reviews = ref([])
const loading = ref(true)

function formatDate(d) {
  const dt = new Date(d)
  const today = new Date()
  const yesterday = new Date(today)
  yesterday.setDate(today.getDate() - 1)

  if (dt.toDateString() === today.toDateString())
    return 'Today · ' + dt.toLocaleTimeString('en-MY', { hour: '2-digit', minute: '2-digit', hour12: true })
  if (dt.toDateString() === yesterday.toDateString())
    return 'Yesterday · ' + dt.toLocaleTimeString('en-MY', { hour: '2-digit', minute: '2-digit', hour12: true })
  return dt.toLocaleDateString('en-MY', { day: 'numeric', month: 'short', year: 'numeric' })
    + ' · ' + dt.toLocaleTimeString('en-MY', { hour: '2-digit', minute: '2-digit', hour12: true })
}

onMounted(async () => {
  try {
    const { data } = await axios.get('/api/profile/reviews')
    reviews.value = data
  } finally {
    loading.value = false
  }
})
</script>