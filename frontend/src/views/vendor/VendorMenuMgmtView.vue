<template>
  <div>
    <nav class="navbar">
      <span class="navbar-brand">CampusEats</span>
      <div class="navbar-actions">
        <button class="btn btn-primary btn-sm" @click="openAdd">+ Add Item</button>
        <button class="btn btn-ghost btn-sm" @click="auth.logout()">Sign Out</button>
      </div>
    </nav>

    <div class="page" style="padding-bottom:80px">
      <h2 class="page-title">Menu</h2>

      <div v-if="loading" class="loading"><div class="spinner"></div></div>

      <div v-else-if="!store.menuItems.length" class="empty">
        <div class="empty-icon">🍴</div>
        <p>No menu items yet. Add your first item!</p>
        <button class="btn btn-primary" style="margin-top:1rem" @click="openAdd">+ Add Item</button>
      </div>

      <template v-else>
        <div v-for="item in store.menuItems" :key="item.id" class="menu-item" style="position:relative">
          <div class="menu-item-img">{{ item.emoji || '🍽️' }}</div>
          <div class="menu-item-info">
            <div class="menu-item-name">{{ item.name }}</div>
            <div class="menu-item-desc">{{ item.description }}</div>
            <div class="menu-item-price">RM {{ Number(item.price).toFixed(2) }}</div>
            <div style="margin-top:0.3rem">
              <span :class="['badge', item.isAvailable ? 'badge-active' : 'badge-inactive']">
                {{ item.isAvailable ? 'In Stock' : 'Out of Stock' }}
              </span>
            </div>
          </div>
          <div style="display:flex;flex-direction:column;gap:0.4rem;flex-shrink:0">
            <button class="btn btn-ghost btn-sm" @click="toggleStock(item.id)">
              {{ item.isAvailable ? 'Mark Out' : 'Mark In' }}
            </button>
            <button class="btn btn-outline btn-sm" @click="openEdit(item)">Edit</button>
            <button class="btn btn-danger btn-sm" @click="confirmDelete(item.id)">Delete</button>
          </div>
        </div>
      </template>
    </div>

    <!-- Add / Edit Modal -->
    <div v-if="showModal" class="modal-overlay" @click.self="closeModal">
      <div class="modal">
        <div class="modal-header">
          {{ editingItem ? 'Edit Item' : 'Add Menu Item' }}
          <button class="close-btn" @click="closeModal">✕</button>
        </div>
        <div class="modal-body">
          <div v-if="formError" class="alert alert-error">{{ formError }}</div>
          <div class="form-group">
            <label class="form-label">Name *</label>
            <input v-model="form.name" class="form-control" placeholder="e.g. Nasi Lemak" />
          </div>
          <div class="form-group">
            <label class="form-label">Description</label>
            <input v-model="form.description" class="form-control" placeholder="Brief description" />
          </div>
          <div class="form-group">
            <label class="form-label">Price (RM) *</label>
            <input v-model="form.price" type="number" step="0.01" min="0" class="form-control" placeholder="0.00" />
          </div>
          <div class="form-group">
            <label class="form-label">Category</label>
            <select v-model="form.category" class="form-control">
              <option value="">Select category</option>
              <option value="rice">Rice</option>
              <option value="noodles">Noodles</option>
              <option value="drinks">Drinks</option>
              <option value="snacks">Snacks</option>
              <option value="other">Other</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Emoji (optional)</label>
            <input v-model="form.emoji" class="form-control" placeholder="🍛" maxlength="4" />
          </div>
          <div class="form-group" style="display:flex;align-items:center;gap:0.75rem">
            <input id="avail" v-model="form.isAvailable" type="checkbox" style="width:18px;height:18px;cursor:pointer" />
            <label for="avail" class="form-label" style="margin:0;cursor:pointer">Available (in stock)</label>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-ghost" @click="closeModal">Cancel</button>
          <button class="btn btn-primary" :disabled="saving" @click="save">
            {{ saving ? 'Saving…' : (editingItem ? 'Update' : 'Add Item') }}
          </button>
        </div>
      </div>
    </div>

    <!-- Bottom nav -->
    <nav class="bottom-nav">
      <router-link to="/vendor" class="bottom-nav-item">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>
        Dashboard
      </router-link>
      <router-link to="/vendor/orders" class="bottom-nav-item">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2"/><rect x="9" y="3" width="6" height="4" rx="1"/><path d="M9 12h6M9 16h4"/></svg>
        Orders
      </router-link>
      <router-link to="/vendor/menu" class="bottom-nav-item active">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2C8 2 5 5.5 5 9c0 3 1.5 5.5 4 7v3a1 1 0 001 1h4a1 1 0 001-1v-3c2.5-1.5 4-4 4-7 0-3.5-3-7-7-7z"/></svg>
        Menu
      </router-link>
    </nav>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useVendorOrdersStore } from '@/stores/vendorOrders'

const auth  = useAuthStore()
const store = useVendorOrdersStore()

const loading     = ref(true)
const showModal   = ref(false)
const editingItem = ref(null)
const saving      = ref(false)
const formError   = ref('')

const form = reactive({ name: '', description: '', price: '', category: '', emoji: '', isAvailable: true })

function resetForm() {
  form.name = ''; form.description = ''; form.price = ''
  form.category = ''; form.emoji = ''; form.isAvailable = true
  formError.value = ''
  editingItem.value = null
}

function openAdd() { resetForm(); showModal.value = true }

function openEdit(item) {
  editingItem.value = item
  form.name        = item.name        || ''
  form.description = item.description || ''
  form.price       = item.price       || ''
  form.category    = item.category    || ''
  form.emoji       = item.emoji       || ''
  form.isAvailable = item.isAvailable ?? true
  formError.value  = ''
  showModal.value  = true
}

function closeModal() { showModal.value = false; resetForm() }

async function save() {
  if (!form.name.trim()) { formError.value = 'Name is required.'; return }
  if (!form.price || isNaN(form.price) || Number(form.price) < 0) { formError.value = 'Enter a valid price.'; return }

  saving.value = true
  formError.value = ''
  try {
    const payload = {
      name:        form.name.trim(),
      description: form.description.trim(),
      price:       Number(form.price),
      category:    form.category,
      emoji:       form.emoji.trim(),
      isAvailable: form.isAvailable,
    }
    if (editingItem.value) {
      await store.updateMenuItem(editingItem.value.id, payload)
    } else {
      await store.addMenuItem(store.myVendor.id, payload)
    }
    closeModal()
  } catch (e) {
    formError.value = e?.response?.data?.message || 'Something went wrong. Please try again.'
  } finally {
    saving.value = false
  }
}

async function toggleStock(itemId) {
  await store.toggleStock(itemId)
}

async function confirmDelete(itemId) {
  if (confirm('Delete this menu item? This cannot be undone.')) {
    await store.deleteMenuItem(itemId)
  }
}

onMounted(async () => {
  try {
    if (!store.myVendor) await store.fetchMyVendor(auth.user?.id)
    if (store.myVendor) await store.fetchMenu(store.myVendor.id)
  } finally {
    loading.value = false
  }
})
</script>