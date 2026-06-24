<template>
  <div>
    <nav class="navbar">
      <div class="navbar-left">
        <button class="navbar-back-btn" @click="handleBack">‹</button>
        <div class="navbar-brand">Edit Profile</div>
      </div>
    </nav>

    <div class="page">
      <div class="card">
        <div class="card-body">

          <!-- Avatar preview -->
          <div class="edit-profile-avatar-wrap">
            <div class="profile-avatar edit-profile-avatar">{{ initials }}</div>
          </div>

          <!-- Name field -->
          <div class="form-group">
            <label class="form-label">Name</label>
            <input
              v-model="form.name"
              type="text"
              class="form-control"
              :class="{ 'form-control-error': errors.name }"
              placeholder="Your full name"
              maxlength="120"
              @input="isDirty = true"
            />
            <span v-if="errors.name" class="form-error">{{ errors.name }}</span>
          </div>

          <!-- Email (read-only, just for reference) -->
          <div class="form-group">
            <label class="form-label">Email</label>
            <input
              :value="auth.user?.email"
              type="email"
              class="form-control form-control-readonly"
              disabled
            />
            <span class="form-hint">Email cannot be changed</span>
          </div>

          <!-- Save button -->
          <button
            class="btn btn-primary btn-block"
            :disabled="saving || !isDirty"
            @click="save"
          >
            {{ saving ? 'Saving…' : 'Save Changes' }}
          </button>

          <!-- Success message -->
          <div v-if="saved" class="alert alert-success edit-profile-success">
            Profile updated successfully!
          </div>

        </div>
      </div>
    </div>

    <!-- Unsaved changes modal -->
    <div v-if="showDiscardModal" class="modal-overlay" @click.self="showDiscardModal = false">
      <div class="modal">
        <div class="modal-header">Unsaved Changes</div>
        <div class="modal-body">You have unsaved changes. Do you want to discard them?</div>
        <div class="modal-footer">
          <button class="btn btn-ghost" @click="showDiscardModal = false">Keep Editing</button>
          <button class="btn btn-primary" @click="confirmDiscard">Discard</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const auth   = useAuthStore()

const form = ref({
  name: auth.user?.name || ''
})

const errors          = ref({})
const saving          = ref(false)
const saved           = ref(false)
const isDirty         = ref(false)
const showDiscardModal = ref(false)

const initials = computed(() => (form.value.name || '?').charAt(0).toUpperCase())

function validate() {
  errors.value = {}
  if (!form.value.name.trim()) {
    errors.value.name = 'Name is required'
  } else if (form.value.name.trim().length > 120) {
    errors.value.name = 'Name must be 120 characters or less'
  }
  return Object.keys(errors.value).length === 0
}

async function save() {
  if (!validate()) return
  saving.value = true
  saved.value  = false
  try {
    const { data } = await axios.put('/api/profile', { name: form.value.name.trim() })
    auth.updateUser(data)   // update store + localStorage immediately
    isDirty.value = false
    saved.value   = true
    setTimeout(() => (saved.value = false), 3000)
  } catch (err) {
    const msg = err.response?.data?.error || 'Something went wrong. Please try again.'
    errors.value.name = msg
  } finally {
    saving.value = false
  }
}

function handleBack() {
  if (isDirty.value) {
    showDiscardModal.value = true
  } else {
    router.push('/profile')
  }
}

function confirmDiscard() {
  showDiscardModal.value = false
  router.push('/profile')
}
</script>