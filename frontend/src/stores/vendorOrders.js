import { defineStore } from 'pinia'
import { ref } from 'vue'
import axios from 'axios'

const API = '/api'

export const useVendorOrdersStore = defineStore('vendorOrders', () => {
  const dashboard = ref(null)
  const orders    = ref([])
  const myVendor  = ref(null)
  const menuItems = ref([])
  const loading   = ref(false)
  let   pollTimer = null

  async function fetchDashboard() {
    try {
      const { data } = await axios.get(`${API}/vendor/dashboard`)
      dashboard.value = data
    } catch (e) { console.error(e) }
  }

  async function fetchOrders(vendorId) {
    const { data } = await axios.get(`${API}/vendor/orders`)
    orders.value = data
  }

  // Fixed: calls /api/vendor/me using the vendor's own JWT — no userId arg needed
  async function fetchMyVendor() {
    try {
      const { data } = await axios.get(`${API}/vendor/dashboard`)
      // Only populate myVendor when the account is approved
      // Pending/inactive vendors stay null → dashboard shows the pending approval screen
      myVendor.value = data.status === 'active' ? data : null
    } catch {
      myVendor.value = null
    }
  }

  async function fetchMenu() {
    const { data } = await axios.get(`${API}/vendor/menu`)
    menuItems.value = data
  }

  async function updateStatus(orderId, status) {
    await axios.put(`${API}/orders/${orderId}/status`, { status })
    await fetchDashboard()
  }

  async function toggleOpen(vendorId) {
    const { data } = await axios.put(`${API}/vendor/${vendorId}/toggle-open`)
    if (myVendor.value) myVendor.value.isOpen = data.isOpen
  }

  async function toggleStock(itemId) {
    const { data } = await axios.put(`${API}/menu-items/${itemId}/stock`)
    const idx = menuItems.value.findIndex(i => i.id === itemId)
    if (idx !== -1) menuItems.value[idx] = data
  }

  async function addMenuItem(vendorId, payload) {
    const { data } = await axios.post(`${API}/vendor/${vendorId}/menu`, payload)
    menuItems.value.unshift(data)
  }

  async function updateMenuItem(itemId, payload) {
    const { data } = await axios.put(`${API}/menu-items/${itemId}`, payload)
    const idx = menuItems.value.findIndex(i => i.id === itemId)
    if (idx !== -1) menuItems.value[idx] = data
  }

  async function deleteMenuItem(itemId) {
    await axios.delete(`${API}/menu-items/${itemId}`)
    menuItems.value = menuItems.value.filter(i => i.id !== itemId)
  }

  function startPolling(interval = 10000) {
    fetchDashboard()
    pollTimer = setInterval(fetchDashboard, interval)
  }

  function stopPolling() {
    if (pollTimer) { clearInterval(pollTimer); pollTimer = null }
  }

  return { dashboard, orders, myVendor, menuItems, loading,
    fetchDashboard, fetchOrders, fetchMyVendor, fetchMenu,
    updateStatus, toggleOpen, toggleStock, addMenuItem, updateMenuItem, deleteMenuItem,
    startPolling, stopPolling }
})