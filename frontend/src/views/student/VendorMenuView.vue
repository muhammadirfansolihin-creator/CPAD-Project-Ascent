<template>
  <div>
    <nav class="navbar">
      <router-link to="/" class="navbar-icon-btn" style="text-decoration:none"><ChevronLeft :size="22" /></router-link>
      <div class="navbar-brand" style="font-size:1rem">{{ vendor?.name || 'Menu' }}</div>
      <router-link to="/cart" class="navbar-icon-btn">
        <ShoppingCart :size="20" /><span v-if="cart.itemCount" class="badge-dot"></span>
      </router-link>
    </nav>

    <div v-if="loading" class="loading"><div class="spinner"></div></div>
    <template v-else-if="vendor">
      <!-- Vendor hero -->
      <div class="vendor-hero">
        <h1>{{ vendor.name }}</h1>
        <div class="vendor-hero-location" style="display:flex;align-items:center;gap:0.3rem;flex-wrap:wrap">
          <MapPin :size="13" /> {{ vendor.location }}
          <span style="margin:0 0.5rem;color:var(--color-border)">·</span>
          <strong>Opening Hours:</strong> {{ vendor.openingHours }}
        </div>
        <div class="vendor-hero-rating" style="display:flex;align-items:center;gap:0.35rem">
          <template v-if="vendor.rating">
            <Star :size="14" fill="#d97706" stroke="none" /> {{ vendor.rating }}
            <span style="color:var(--color-muted);font-weight:400">({{ reviews.length }} ratings)</span>
          </template>
          <span class="badge badge-halal" style="margin-left:0.5rem">HALAL</span>
        </div>
      </div>

      <!-- Category filter tabs -->
      <div style="background:var(--color-surface);border-bottom:1px solid var(--color-border)">
        <div class="category-tabs" style="padding:0.6rem 1rem">
          <button v-for="cat in availableCategories" :key="cat.value"
            :class="['cat-tab', activeCat===cat.value?'active':'']"
            @click="activeCat=cat.value">{{ cat.label }}</button>
        </div>
      </div>

      <!-- Menu grid -->
      <div style="padding:1rem 1rem 7rem">
        <template v-for="cat in menuCategories" :key="cat">
          <div v-if="visibleItems(cat).length">
            <div class="section-title" style="padding:0;margin-bottom:0.75rem">{{ catLabel(cat).toUpperCase() }} DISHES</div>
            <div class="menu-grid" style="margin-bottom:1.25rem">
              <div v-for="item in visibleItems(cat)" :key="item.id" class="menu-grid-card">
                <div :class="['menu-grid-img', !item.inStock?'out-of-stock':'']" :style="{backgroundImage: `url(${foodImage(item.category)})`, backgroundSize: 'cover', backgroundPosition: 'center'}">
                  <span v-if="!item.inStock" class="menu-grid-oos">OUT OF STOCK</span>
                </div>
                <div class="menu-grid-body">
                  <div class="menu-grid-name">{{ item.name }}</div>
                  <div class="menu-grid-price">RM {{ Number(item.price).toFixed(2) }}</div>
                </div>
                <button v-if="item.inStock && vendor.isOpen" class="menu-grid-add" @click="addToCart(item)">+</button>
              </div>
            </div>
          </div>
        </template>

        <!-- Reviews -->
        <div class="section-title" style="padding:0">REVIEWS</div>
        <div v-if="!reviews.length" class="empty" style="padding:1.5rem"><p>No reviews yet.</p></div>
        <template v-else>
          <div v-for="r in paginatedReviews" :key="r.id" class="order-card" style="margin-bottom:0.5rem">
            <div style="display:flex;justify-content:space-between;align-items:center">
              <span style="font-weight:700;font-size:0.88rem">{{ r.userName || 'Student' }}</span>
              <span style="display:flex;align-items:center;gap:0.1rem">
                <Star v-for="s in 5" :key="s" :size="13"
                  :fill="s <= r.rating ? '#d97706' : 'none'"
                  :stroke="s <= r.rating ? '#d97706' : '#d1d5db'" />
              </span>
            </div>
            <p v-if="r.itemsOrdered" style="display:flex;align-items:center;gap:0.3rem;font-size:0.78rem;color:var(--color-muted);margin-top:0.3rem">
              <UtensilsCrossed :size="12" /> {{ r.itemsOrdered }}
            </p>
            <p v-if="r.comment" style="font-size:0.82rem;color:var(--color-muted);margin-top:0.3rem">{{ r.comment }}</p>
            <p style="font-size:0.72rem;color:var(--color-muted);margin-top:0.2rem">{{ formatDate(r.createdAt) }}</p>
          </div>

          <!-- Reviews pagination -->
          <div v-if="totalReviewPages > 1" style="display:flex;justify-content:center;align-items:center;gap:0.5rem;margin-top:1rem">
            <button class="btn btn-ghost btn-sm" :disabled="reviewPage===1" @click="goToReviewPage(reviewPage-1)">← Previous</button>
            <button v-for="p in totalReviewPages" :key="p"
              :class="['btn', 'btn-sm', reviewPage===p ? 'btn-primary' : 'btn-outline']"
              style="min-width:36px"
              @click="goToReviewPage(p)">{{ p }}</button>
            <button class="btn btn-ghost btn-sm" :disabled="reviewPage===totalReviewPages" @click="goToReviewPage(reviewPage+1)">Next →</button>
          </div>
        </template>
      </div>
    </template>

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

    <!-- View Cart FAB -->
    <router-link v-if="cart.itemCount" to="/cart" class="view-cart-fab">
      <div class="view-cart-fab-left">
        <span class="view-cart-count">{{ cart.itemCount }}</span>
        <span>View Cart</span>
      </div>
      <span>RM {{ cart.total.toFixed(2) }}</span>
    </router-link>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import axios from 'axios'
import { useCartStore } from '@/stores/cart'
import { ChevronLeft, ShoppingCart, Home, ClipboardList, MapPin, Star, UtensilsCrossed } from 'lucide-vue-next'

const route   = useRoute()
const cart    = useCartStore()
const vendor  = ref(null)
const menu    = ref([])
const reviews = ref([])
const loading = ref(true)
const activeCat = ref('all')

const reviewPage = ref(1)
const reviewsPerPage = 5

const totalReviewPages = computed(() => Math.max(1, Math.ceil(reviews.value.length / reviewsPerPage)))

const paginatedReviews = computed(() => {
  const start = (reviewPage.value - 1) * reviewsPerPage
  return reviews.value.slice(start, start + reviewsPerPage)
})

function goToReviewPage(p) {
  if (p < 1 || p > totalReviewPages.value) return
  reviewPage.value = p
}

const menuCategories = ['rice','noodles','drinks','snacks','other']

const availableCategories = computed(() => {
  const cats = new Set(menu.value.map(i => i.category))
  const all = [{ value: 'all', label: 'All' }, { value: 'rice', label: 'Rice' }, { value: 'noodles', label: 'Noodles' }, { value: 'drinks', label: 'Drinks' }, { value: 'snacks', label: 'Snacks' }]
  return all.filter(c => c.value === 'all' || cats.has(c.value))
})

const FOOD_IMAGES = {
  rice: '/rice.jpg', noodles: '/noodles.jpg', drinks: '/drinks.jpg', snacks: '/snacks.jpg', other: '/other.jpg'
}

function foodImage(category) { return FOOD_IMAGES[category] || FOOD_IMAGES.other }

function visibleItems(cat) {
  if (activeCat.value !== 'all' && activeCat.value !== cat) return []
  return menu.value.filter(i => i.category === cat)
}

function catLabel(c) { return { rice:'Rice', noodles:'Noodles', drinks:'Drinks', snacks:'Snacks', other:'Other' }[c] || c }
function formatDate(d) { return new Date(d).toLocaleDateString('en-MY', { day:'numeric', month:'short', year:'numeric' }) }

function addToCart(item) { cart.addItem(item, vendor.value) }

onMounted(async () => {
  const id = route.params.id
  try {
    const [vRes, mRes, rRes] = await Promise.all([
      axios.get(`/api/vendors/${id}`),
      axios.get(`/api/vendors/${id}/menu`),
      axios.get(`/api/vendors/${id}/reviews`),
    ])
    vendor.value  = vRes.data
    menu.value    = mRes.data
    reviews.value = rRes.data
  } finally { loading.value = false }
})
</script>
