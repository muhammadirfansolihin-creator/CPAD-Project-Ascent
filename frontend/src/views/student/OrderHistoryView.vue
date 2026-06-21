<template>
  <div>
    <nav class="navbar">
      <div class="navbar-brand"><span class="navbar-brand-icon">🍴</span> CampusEats</div>
      <div class="navbar-actions">
        <router-link to="/cart" class="navbar-icon-btn">🛒<span v-if="cart.itemCount" class="badge-dot"></span></router-link>
        <router-link to="/profile" class="navbar-icon-btn">👤</router-link>
      </div>
    </nav>

    <div class="page">
      <div class="page-title">My Orders</div>

      <!-- Tabs -->
      <div class="order-tabs">
        <button v-for="t in tabs" :key="t.value" :class="['order-tab', activeTab===t.value?'active':'']" @click="activeTab=t.value">{{ t.label }}</button>
      </div>

      <div v-if="loading" class="loading"><div class="spinner"></div></div>
      <div v-else-if="!filteredOrders.length" class="empty">
        <div class="empty-icon">📋</div>
        <p>No orders here</p>
        <router-link to="/" class="btn btn-primary" style="margin-top:1rem">Order Now</router-link>
      </div>

      <div v-for="order in filteredOrders" :key="order.id" class="order-card">
        <div class="order-card-header">
          <div style="flex:1;min-width:0">
            <div class="order-card-vendor">{{ order.vendorName }}</div>
            <div class="order-card-date">{{ formatDate(order.createdAt) }} · {{ order.pickupAt }}</div>
          </div>
          <span :class="['badge', `badge-${order.status}`]">{{ order.status.toUpperCase() }}</span>
        </div>
        <div class="order-card-items">{{ summariseItems(order.items) }}</div>
        <div class="order-card-footer">
          <span class="order-card-total">RM {{ Number(order.total).toFixed(2) }}</span>
          <div style="display:flex;gap:0.5rem;flex-wrap:wrap;justify-content:flex-end">
            <button v-if="order.status==='collected' && !reviewedOrders.has(order.id)"
              class="btn btn-outline btn-sm" @click="openReview(order)">⭐ Review</button>
            <span v-else-if="order.status==='collected' && reviewedOrders.has(order.id)"
              style="font-size:0.78rem;color:var(--color-success);font-weight:700;align-self:center">✓ Reviewed</span>
            <button v-if="order.status==='collected' && !disputedOrders.has(order.id)"
              class="btn btn-ghost btn-sm" @click="openDispute(order)">⚠ Dispute</button>
            <button class="btn btn-outline btn-sm" @click="reorder(order)">Reorder</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Review Modal -->
    <div v-if="reviewModal.open" class="modal-overlay" @click.self="closeReview">
      <div class="modal">
        <div class="modal-header">
          Rate your experience
          <button class="close-btn" @click="closeReview">✕</button>
        </div>
        <div class="modal-body">
          <p style="font-size:0.85rem;color:var(--color-muted);margin-bottom:0.75rem">{{ reviewModal.vendorName }}</p>
          <div class="star-row">
            <span v-for="s in 5" :key="s" :class="['star', reviewModal.rating>=s?'filled':'']" @click="reviewModal.rating=s">
              {{ reviewModal.rating >= s ? '⭐' : '☆' }}
            </span>
          </div>
          <p style="text-align:center;font-size:0.8rem;color:var(--color-muted);margin-bottom:1rem">
            {{ ['','Poor','Fair','Good','Very Good','Excellent'][reviewModal.rating] || 'Select a rating' }}
          </p>
          <textarea v-model="reviewModal.comment" class="form-control" placeholder="Share your experience (optional)…" rows="3"></textarea>
          <div v-if="reviewModal.error" style="color:var(--color-danger);font-size:0.8rem;margin-top:0.5rem">{{ reviewModal.error }}</div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-ghost" @click="closeReview">Cancel</button>
          <button class="btn btn-primary" :disabled="reviewModal.submitting || reviewModal.rating===0" @click="submitReview">
            {{ reviewModal.submitting ? 'Submitting…' : 'Submit Review' }}
          </button>
        </div>
      </div>
    </div>

    <!-- Dispute Modal -->
    <div v-if="disputeModal.open" class="modal-overlay" @click.self="closeDispute">
      <div class="modal">
        <div class="modal-header">
          File a Dispute
          <button class="close-btn" @click="closeDispute">✕</button>
        </div>
        <div class="modal-body">
          <p style="font-size:0.85rem;color:var(--color-muted);margin-bottom:0.5rem">
            Order #{{ disputeModal.orderId }} · {{ disputeModal.vendorName }}
          </p>
          <div class="dispute-desc">
            Describe the issue with your order. Our team will review and respond within 24 hours.
          </div>
          <div class="form-group">
            <label class="form-label">Issue Description *</label>
            <textarea v-model="disputeModal.description" class="form-control" placeholder="E.g. Wrong item received, order marked collected but not received…" rows="4"></textarea>
          </div>
          <div v-if="disputeModal.error" style="color:var(--color-danger);font-size:0.8rem">{{ disputeModal.error }}</div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-ghost" @click="closeDispute">Cancel</button>
          <button class="btn btn-danger" :disabled="disputeModal.submitting || !disputeModal.description.trim()" @click="submitDispute">
            {{ disputeModal.submitting ? 'Submitting…' : 'Submit Dispute' }}
          </button>
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
import { ref, reactive, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'
import { useAuthStore } from '@/stores/auth'
import { useCartStore } from '@/stores/cart'

const auth   = useAuthStore()
const cart   = useCartStore()
const router = useRouter()
const orders  = ref([])
const loading = ref(true)
const activeTab = ref('all')
const reviewedOrders  = ref(new Set())
const disputedOrders  = ref(new Set())

const tabs = [
  { value: 'all',       label: 'All' },
  { value: 'active',    label: 'Active' },
  { value: 'completed', label: 'Completed' },
]

const filteredOrders = computed(() => {
  if (activeTab.value === 'active') return orders.value.filter(o => ['placed','preparing','ready'].includes(o.status))
  if (activeTab.value === 'completed') return orders.value.filter(o => o.status === 'collected')
  return orders.value
})

const reviewModal = reactive({ open:false, orderId:null, vendorId:null, vendorName:'', rating:0, comment:'', submitting:false, error:'' })
const disputeModal = reactive({ open:false, orderId:null, vendorName:'', description:'', submitting:false, error:'' })

function formatDate(d) {
  const dt = new Date(d)
  const today = new Date()
  const yesterday = new Date(today); yesterday.setDate(today.getDate()-1)
  if (dt.toDateString() === today.toDateString()) return 'Today · ' + dt.toLocaleTimeString('en-MY',{hour:'2-digit',minute:'2-digit',hour12:true})
  if (dt.toDateString() === yesterday.toDateString()) return 'Yesterday · ' + dt.toLocaleTimeString('en-MY',{hour:'2-digit',minute:'2-digit',hour12:true})
  return dt.toLocaleDateString('en-MY',{day:'numeric',month:'short',year:'numeric'}) + ' · ' + dt.toLocaleTimeString('en-MY',{hour:'2-digit',minute:'2-digit',hour12:true})
}

function summariseItems(items) {
  if (!items?.length) return ''
  return items.map(i => `${i.qty} × ${i.name}`).join(' · ')
}

async function reorder(order) {
  cart.clear()
  for (const item of order.items) cart.addItem({ id: item.menuItemId, name: item.name, price: item.unitPrice }, { id: order.vendorId })
  router.push('/cart')
}

function openReview(order) {
  Object.assign(reviewModal, { open:true, orderId:order.id, vendorId:order.vendorId, vendorName:order.vendorName, rating:0, comment:'', error:'', submitting:false })
}
function closeReview() { reviewModal.open = false }
async function submitReview() {
  if (!reviewModal.rating) { reviewModal.error = 'Please select a star rating.'; return }
  reviewModal.submitting = true; reviewModal.error = ''
  try {
    await axios.post(`/api/vendors/${reviewModal.vendorId}/reviews`, { rating: reviewModal.rating, comment: reviewModal.comment || null })
    reviewedOrders.value = new Set([...reviewedOrders.value, reviewModal.orderId])
    closeReview()
  } catch(e) { reviewModal.error = e?.response?.data?.error || 'Failed to submit. Please try again.' }
  finally { reviewModal.submitting = false }
}

function openDispute(order) {
  Object.assign(disputeModal, { open:true, orderId:order.id, vendorName:order.vendorName, description:'', error:'', submitting:false })
}
function closeDispute() { disputeModal.open = false }
async function submitDispute() {
  if (!disputeModal.description.trim()) { disputeModal.error = 'Please describe the issue.'; return }
  disputeModal.submitting = true; disputeModal.error = ''
  try {
    await axios.post('/api/disputes', { orderId: disputeModal.orderId, description: disputeModal.description })
    disputedOrders.value = new Set([...disputedOrders.value, disputeModal.orderId])
    closeDispute()
  } catch(e) { disputeModal.error = e?.response?.data?.error || 'Failed to submit dispute.' }
  finally { disputeModal.submitting = false }
}

onMounted(async () => {
  try { const { data } = await axios.get('/api/orders'); orders.value = data } finally { loading.value = false }
})
</script>
