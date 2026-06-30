<template>
  <div>
    <nav class="navbar">
      <div class="navbar-brand"><img src="/favicon.png" alt="CampusEats Logo" class="navbar-brand-icon" /> CampusEats</div>
      <div class="navbar-actions">
        <button class="navbar-icon-btn" @click="$router.push('/vendor')" title="Back"><ChevronLeft :size="22" /></button>
      </div>
    </nav>

    <div class="page">
      <div v-if="loading" class="loading"><div class="spinner"></div></div>
      <template v-else-if="vendor">
        <!-- Identity -->
        <div class="card profile-identity-card">
          <div class="card-body profile-identity-body">
            <div class="profile-avatar">{{ initials }}</div>
            <div class="profile-identity-text">
              <div class="font-bold profile-name">{{ vendor.ownerName }}</div>
              <div class="text-muted profile-email">{{ auth.user?.email }}</div>
              <div class="text-muted" style="font-size:0.75rem;margin-top:0.2rem">Vendor / Stall Owner</div>
            </div>
          </div>
        </div>

        <!-- Stall info -->
        <div class="card" style="margin-top:1rem">
          <div class="card-header" style="display:flex;justify-content:space-between;align-items:center">
            <span>Stall Info</span>
            <span :class="['badge', `badge-${vendor.status}`]">{{ vendor.status.toUpperCase() }}</span>
          </div>
          <div class="card-body">
            <template v-if="!editing">
              <div class="form-group">
                <div class="form-label">Stall Name</div>
                <div>{{ vendor.name }}</div>
              </div>
              <div class="form-group">
                <div class="form-label">Location</div>
                <div>{{ vendor.location }}</div>
              </div>
              <div class="form-group">
                <div class="form-label">Opening Hours</div>
                <div>{{ vendor.openingHours }}</div>
              </div>
              <button class="btn btn-outline btn-block" style="display:flex;align-items:center;justify-content:center;gap:0.4rem" @click="startEdit">
                <Pencil :size="14" /> Edit Stall Info
              </button>
            </template>

            <template v-else>
              <div class="form-group">
                <label class="form-label">Stall Name</label>
                <input v-model="form.name" class="form-control" />
              </div>
              <div class="form-group">
                <label class="form-label">Location</label>
                <input v-model="form.location" class="form-control" />
              </div>
              <div class="form-group">
                <label class="form-label">Opening Hours</label>
                <input v-model="form.openingHours" class="form-control" placeholder="e.g. 8:00 AM - 5:00 PM" />
              </div>
              <div style="display:flex;gap:0.5rem">
                <button class="btn btn-primary" style="flex:1" :disabled="saving" @click="saveEdit">{{ saving ? 'Saving...' : 'Save Changes' }}</button>
                <button class="btn btn-ghost" style="flex:1" @click="editing=false">Cancel</button>
              </div>
            </template>
          </div>
        </div>

        <button class="btn btn-ghost btn-block profile-logout-btn" style="margin-top:1rem" @click="handleLogout">
          Log Out
        </button>
      </template>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'
import { useAuthStore } from '@/stores/auth'
import { ChevronLeft, Pencil } from 'lucide-vue-next'

const router = useRouter()
const auth   = useAuthStore()

const vendor  = ref(null)
const loading = ref(true)
const editing = ref(false)
const saving  = ref(false)
const form    = ref({ name: '', location: '', openingHours: '' })

const initials = computed(() => (vendor.value?.ownerName || '?').charAt(0).toUpperCase())

function startEdit() {
  form.value = {
    name: vendor.value.name,
    location: vendor.value.location,
    openingHours: vendor.value.openingHours,
  }
  editing.value = true
}

async function saveEdit() {
  saving.value = true
  try {
    const { data } = await axios.put(`/api/vendor/${vendor.value.id}`, form.value)
    vendor.value = data
    editing.value = false
  } finally {
    saving.value = false
  }
}

function handleLogout() {
  auth.logout()
  router.push('/login')
}

onMounted(async () => {
  try {
    const { data } = await axios.get('/api/vendor/profile')
    vendor.value = data
  } finally {
    loading.value = false
  }
})
</script>
