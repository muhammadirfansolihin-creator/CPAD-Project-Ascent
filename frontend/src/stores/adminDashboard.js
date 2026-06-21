import { defineStore } from 'pinia'
import { ref } from 'vue'
import axios from 'axios'

const API = '/api'

export const useAdminDashboardStore = defineStore('adminDashboard', () => {
  const analytics = ref(null)
  const vendors   = ref([])
  const disputes  = ref([])
  const loading   = ref(false)

  async function fetchAnalytics() {
    const { data } = await axios.get(`${API}/admin/analytics`)
    analytics.value = data
  }

  async function fetchVendors() {
    const { data } = await axios.get(`${API}/admin/vendors`)
    vendors.value = data
  }

  async function updateVendorStatus(vendorId, status) {
    await axios.patch(`${API}/vendors/${vendorId}/status`, { status })
    await fetchVendors()
  }

  async function fetchDisputes(status) {
    const params = status ? { status } : {}
    const { data } = await axios.get(`${API}/disputes`, { params })
    disputes.value = data
  }

  async function resolveDispute(id, resolution) {
    await axios.patch(`${API}/disputes/${id}/resolve`, { resolution })
    await fetchDisputes()
  }

  async function refundDispute(id, resolution) {
    await axios.patch(`${API}/disputes/${id}/resolve`, { resolution, refunded: true })
    await fetchDisputes()
  }

  return { analytics, vendors, disputes, loading,
    fetchAnalytics, fetchVendors, updateVendorStatus, fetchDisputes, resolveDispute, refundDispute }
})
