<template>
  <div>
    <nav class="navbar">
      <div class="navbar-brand"><span class="navbar-brand-icon">🍴</span> CampusEats</div>
      <div class="navbar-actions">
        <button class="navbar-icon-btn" @click="$router.push('/admin')" title="Back">←</button>
      </div>
    </nav>

    <div class="admin-tabs">
      <router-link to="/admin"          class="admin-tab">Sales Summary</router-link>
      <router-link to="/admin/vendors"  class="admin-tab">Vendors</router-link>
      <router-link to="/admin/disputes" class="admin-tab">Disputes</router-link>
    </div>

    <div class="page" style="padding-bottom:2rem">
      <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.25rem">
        <div class="page-title" style="margin-bottom:0">Vendor Sales Report</div>
        <select v-model="period" class="form-control" style="width:140px" @change="fetchData">
          <option value="today">Today</option>
          <option value="week">This Week</option>
          <option value="month">This Month</option>
        </select>
      </div>

      <div v-if="loading" class="loading"><div class="spinner"></div></div>
      <template v-else>
        <div v-if="!vendorRevenue.length" class="empty"><p>No revenue data for this period</p></div>
        <div class="card" v-else>
          <div class="card-body">
            <div v-for="v in vendorRevenue" :key="v.vendorId" style="margin-bottom:1.25rem">
              <div style="display:flex;justify-content:space-between;margin-bottom:0.4rem">
                <span style="font-weight:700;font-size:0.9rem">{{ v.vendorName }}</span>
                <span style="font-weight:700;color:var(--color-primary)">RM {{ v.revenue.toFixed(2) }}</span>
              </div>
              <div class="progress-bar" style="height:8px">
                <div class="progress-fill" :style="`width:${barWidth(v.revenue)}%`"></div>
              </div>
              <div style="font-size:0.72rem;color:var(--color-muted);margin-top:0.2rem">{{ v.orderCount }} orders</div>
            </div>
          </div>
        </div>
      </template>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import axios from 'axios'

const period = ref('today')
const vendorRevenue = ref([])
const loading = ref(true)

const maxRevenue = computed(() => Math.max(...(vendorRevenue.value.map(v => v.revenue) || [1]), 1))
function barWidth(rev) { return Math.round((rev / maxRevenue.value) * 100) }

async function fetchData() {
  loading.value = true
  try {
    const { data } = await axios.get('/api/admin/analytics', { params: { period: period.value } })
    vendorRevenue.value = data.vendorRevenue
  } finally {
    loading.value = false
  }
}

onMounted(fetchData)
</script>