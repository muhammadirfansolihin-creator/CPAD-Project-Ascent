<template>
  <div>
    <nav class="navbar">
      <div class="navbar-left">
        <button class="navbar-back-btn" @click="$router.push('/profile')"><ChevronLeft :size="22" /></button>
        <div class="navbar-brand">My Reviews</div>
      </div>
    </nav>

    <div class="page">
      <div v-if="loading" class="loading"><div class="spinner"></div></div>

      <div v-else-if="!reviews.length" class="empty">
        <div class="empty-icon"><Star :size="40" /></div>
        <p>You haven't written any reviews yet.</p>
        <router-link to="/orders" class="btn btn-primary" style="margin-top:1rem">
          View Orders
        </router-link>
      </div>

      <div v-else>
        <div class="my-review-summary">
          {{ reviews.length }} review{{ reviews.length !== 1 ? 's' : '' }} written
        </div>

        <div v-for="review in paginatedReviews" :key="review.id" class="order-card my-review-card">
          <div class="order-card-header">
            <div style="flex:1;min-width:0">
              <div class="order-card-vendor">{{ review.vendorName }}</div>
              <div class="order-card-date">{{ formatDate(review.createdAt) }}</div>
            </div>
            <div class="my-review-stars" style="display:flex;align-items:center;gap:0.1rem">
              <Star v-for="s in 5" :key="s" :size="14"
                :fill="s <= review.rating ? '#f59e0b' : 'none'"
                :stroke="s <= review.rating ? '#f59e0b' : '#d1d5db'" />
            </div>
          </div>

          <div class="order-card-items my-review-items" style="display:flex;align-items:center;gap:0.3rem">
            <UtensilsCrossed :size="13" /> {{ review.itemsOrdered }}
          </div>

          <div v-if="review.comment" class="my-review-comment">
            "{{ review.comment }}"
          </div>
          <div v-else class="my-review-comment my-review-no-comment">
            No comment left
          </div>
        </div>

        <!-- Pagination -->
        <div v-if="totalPages > 1" style="display:flex;justify-content:center;align-items:center;gap:0.5rem;margin-top:1.5rem">
          <button class="btn btn-ghost btn-sm" :disabled="currentPage===1" @click="goToPage(currentPage-1)">← Previous</button>
          <button v-for="p in totalPages" :key="p"
            :class="['btn', 'btn-sm', currentPage===p ? 'btn-primary' : 'btn-outline']"
            style="min-width:36px"
            @click="goToPage(p)">{{ p }}</button>
          <button class="btn btn-ghost btn-sm" :disabled="currentPage===totalPages" @click="goToPage(currentPage+1)">Next →</button>
        </div>
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
import { ref, computed, onMounted } from 'vue'
import axios from 'axios'
import { ChevronLeft, Star, Home, ClipboardList, UtensilsCrossed } from 'lucide-vue-next'

const reviews = ref([])
const loading = ref(true)
const currentPage = ref(1)
const perPage = 5

const totalPages = computed(() => Math.max(1, Math.ceil(reviews.value.length / perPage)))

const paginatedReviews = computed(() => {
  const start = (currentPage.value - 1) * perPage
  return reviews.value.slice(start, start + perPage)
})

function goToPage(p) {
  if (p < 1 || p > totalPages.value) return
  currentPage.value = p
}

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
