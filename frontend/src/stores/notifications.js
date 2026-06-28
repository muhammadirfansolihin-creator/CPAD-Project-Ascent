import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import axios from 'axios'

const API = '/api'

export const useNotificationStore = defineStore('notifications', () => {
  const notifications = ref([])

  const unreadCount = computed(() =>
    notifications.value.filter(n => !n.isRead).length
  )

  async function fetchNotifications() {
    const { data } = await axios.get(`${API}/notifications`)
    notifications.value = data
  }

  async function markAsRead(id) {
    await axios.put(`${API}/notifications/${id}/read`)
    const n = notifications.value.find(x => x.id === id)
    if (n) n.isRead = true
  }

  return { notifications, unreadCount, fetchNotifications, markAsRead }
})