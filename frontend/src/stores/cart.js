import { defineStore } from 'pinia'
import { ref, computed } from 'vue'

export const useCartStore = defineStore('cart', () => {
  const items    = ref([])
  const vendorId = ref(null)

  const total     = computed(() => items.value.reduce((s, i) => s + i.price * i.qty, 0))
  const itemCount = computed(() => items.value.reduce((s, i) => s + i.qty, 0))

  function addItem(menuItem, vendor) {
    if (vendorId.value && vendorId.value !== vendor.id) {
      if (!confirm('Your cart has items from another vendor. Clear cart and start new order?')) return
      clear()
    }
    vendorId.value = vendor.id
    const existing = items.value.find(i => i.id === menuItem.id)
    if (existing) existing.qty++
    else items.value.push({ ...menuItem, qty: 1 })
  }

  function removeItem(id) {
    const idx = items.value.findIndex(i => i.id === id)
    if (idx === -1) return
    if (items.value[idx].qty > 1) items.value[idx].qty--
    else items.value.splice(idx, 1)
    if (!items.value.length) vendorId.value = null
  }

  function clear() {
    items.value    = []
    vendorId.value = null
  }

  return { items, vendorId, total, itemCount, addItem, removeItem, clear }
})
