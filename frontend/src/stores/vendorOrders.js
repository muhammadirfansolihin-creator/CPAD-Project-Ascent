import { defineStore } from 'pinia'
import { ref } from 'vue'
import axios from 'axios'

const API = '/api'

export const useVendorOrdersStore = defineStore('vendorOrders', () => {
  const dashboard   = ref(null)
  const orders      = ref([])
  const myVendor    = ref(null)
  const menuItems   = ref([])
  const loading     = ref(false)
  let   pollTimer   = null

  async function fetchDashboard() {
    try {
      const { data } = await axios.get(`${API}/vendor/dashboard`)
      dashboard.value = data
    } catch (e) { console.error(e) }
  }

  async function fetchOrders(vendorId) {
    const { data } = await axios.get(`${API}/orders`, { params: { vendorId } })
    orders.value = data
  }

  async function fetchMyVendor(userId) {
    try {
      const { data } = await axios.get(`${API}/admin/vendors`)
      myVendor.value = data.find(v => v.ownerId === userId) || null
    } catch {
      const { data } = await axios.get(`${API}/vendors`)
      myVendor.value = data[0] || null
    }
  }

  async function fetchMenu(vendorId) {
    const { data } = await axios.get(`${API}/vendors/${vendorId}/menu`)
    menuItems.value = data
  }

  async function updateStatus(orderId, status) {
    await axios.patch(`${API}/orders/${orderId}/status`, { status })
    await fetchDashboard()
  }

  async function toggleOpen(vendorId) {
    const { data } = await axios.patch(`${API}/vendors/${vendorId}/toggle-open`)
    if (myVendor.value) myVendor.value.isOpen = data.isOpen
  }

  async function toggleStock(itemId) {
    const { data } = await axios.patch(`${API}/menu-items/${itemId}/stock`)
    const idx = menuItems.value.findIndex(i => i.id === itemId)
    if (idx !== -1) menuItems.value[idx] = data
  }

  async function addMenuItem(vendorId, payload) {
    const { data } = await axios.post(`${API}/vendors/${vendorId}/menu`, payload)
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
