<template>
  <div>
    <nav class="navbar">
      <div>
        <div class="navbar-brand"><img src="/favicon.png" alt="CampusEats Logo" class="navbar-brand-icon" /> CampusEats</div>
      </div>
      <div class="navbar-actions">
        <button v-if="store.myVendor" :class="['open-toggle-btn', store.myVendor.isOpen?'is-open':'']" @click="toggleOpen">
          <span :class="store.myVendor.isOpen?'open-dot':'closed-dot'"></span>
          {{ store.myVendor.isOpen ? 'Open' : 'Closed' }}
        </button>
        
        <div style="position:relative">
          <button class="navbar-icon-btn" @click="toggleNotif" title="Notifications">
            🔔
            <span v-if="notif.unreadCount" class="notif-badge">{{ notif.unreadCount }}</span>
          </button>

          <div v-if="showNotif" class="notif-dropdown">
            <div class="notif-dropdown-header">Notifications</div>
            <div v-if="!notif.notifications.length" class="notif-empty">No notifications yet</div>
            <div v-for="n in notif.notifications" :key="n.id" @click="handleNotifClick(n)"
              :class="['notif-item', { unread: !n.isRead }]">
              <div>{{ n.message }}</div>
              <div class="notif-item-time">{{ n.createdAt }}</div>
            </div>
          </div>
        </div>

        <router-link to="/vendor/profile" class="navbar-icon-btn">👤</router-link>
      </div>
    </nav>

    <div class="page">
      <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.25rem">
        <div class="page-title" style="margin-bottom:0">Menu Items</div>
        <button class="btn btn-primary btn-sm" @click="openAdd">+ Add Item</button>
      </div>

      <!-- Category filter -->
      <div class="category-tabs" style="padding:0;margin-bottom:1rem">
        <button v-for="c in catFilters" :key="c.value" :class="['cat-tab', activeCat===c.value?'active':'']" @click="activeCat=c.value">{{ c.label }}</button>
      </div>

      <div v-if="loading" class="loading"><div class="spinner"></div></div>

      <div v-else-if="!store.menuItems.length" class="empty">
        <div class="empty-icon">🍴</div>
        <p>No menu items yet.</p>
        <button class="btn btn-primary" style="margin-top:1rem" @click="openAdd">+ Add Item</button>
      </div>

      <div v-else class="card" style="overflow:auto">
        <table class="data-table">
          <thead>
            <tr>
              <th>ITEM</th>
              <th>CATEGORY</th>
              <th>PRICE</th>
              <th>IN STOCK</th>
              <th>ACTIONS</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="item in filteredItems" :key="item.id">
              <td>
                <div style="display:flex;align-items:center;gap:0.75rem">
                  <div style="width:40px;height:40px;border-radius:0.5rem;background:linear-gradient(135deg,#f0e8d8,#e8dcc8);display:flex;align-items:center;justify-content:center;font-size:1.2rem;flex-shrink:0">{{ itemEmoji(item.category) }}</div>
                  <div>
                    <div style="font-weight:700;font-size:0.88rem">{{ item.name }}</div>
                    <div style="font-size:0.72rem;color:var(--color-muted)">☪ Halal</div>
                  </div>
                </div>
              </td>
              <td><span style="background:var(--color-bg);border:1px solid var(--color-border);border-radius:999px;padding:0.2rem 0.65rem;font-size:0.75rem;font-weight:600">{{ catLabel(item.category) }}</span></td>
              <td style="font-weight:700;color:var(--color-primary)">RM {{ Number(item.price).toFixed(2) }}</td>
              <td>
                <label class="toggle-switch">
                  <input type="checkbox" :checked="item.isAvailable" @change="toggleStock(item.id)" />
                  <span class="toggle-slider"></span>
                </label>
              </td>
              <td>
                <div style="display:flex;gap:0.4rem">
                  <button class="btn btn-ghost btn-sm" style="padding:0.3rem 0.55rem" @click="openEdit(item)" title="Edit">✏️</button>
                  <button class="btn btn-ghost btn-sm" style="padding:0.3rem 0.55rem;color:var(--color-danger)" @click="confirmDelete(item.id)" title="Delete">🗑️</button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
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
            <input v-model="form.name" class="form-control" placeholder="e.g. Nasi Lemak Ayam" />
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
              <option value="rice">Rice</option>
              <option value="noodles">Noodles</option>
              <option value="drinks">Drinks</option>
              <option value="snacks">Snacks</option>
              <option value="other">Other</option>
            </select>
          </div>
          <div class="form-group" style="display:flex;align-items:center;gap:0.75rem">
            <label class="toggle-switch">
              <input type="checkbox" v-model="form.isAvailable" />
              <span class="toggle-slider"></span>
            </label>
            <span style="font-size:0.88rem;font-weight:600">Available (in stock)</span>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-ghost" @click="closeModal">Cancel</button>
          <button class="btn btn-primary" :disabled="saving" @click="save">
            {{ saving ? 'Saving…' : (editingItem ? 'Update Item' : 'Add Item') }}
          </button>
        </div>
      </div>
    </div>

    <nav class="bottom-nav">
      <router-link to="/vendor" class="bottom-nav-item">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>
        Dashboard
      </router-link>
      <router-link to="/vendor/orders" class="bottom-nav-item">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2"/><rect x="9" y="3" width="6" height="4" rx="1"/></svg>
        Orders
      </router-link>
      <router-link to="/vendor/menu" class="bottom-nav-item">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 2v6M15 2v6M3 10h18M5 22h14a2 2 0 002-2v-8H3v8a2 2 0 002 2z"/></svg>
        Menu
      </router-link>
    </nav>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useVendorOrdersStore } from '@/stores/vendorOrders'
import { useNotificationStore } from '@/stores/notifications'

const auth  = useAuthStore()
const store = useVendorOrdersStore()
const notif = useNotificationStore()
const showNotif = ref(false)
const loading = ref(true); const showModal = ref(false); const editingItem = ref(null)
const saving = ref(false); const formError = ref(''); const activeCat = ref('all')

const form = reactive({ name:'', description:'', price:'', category:'rice', isAvailable:true })

const catFilters = [
  { value:'all', label:'All' }, { value:'rice', label:'🍚 Rice' }, { value:'noodles', label:'🍜 Noodles' },
  { value:'drinks', label:'🥤 Drinks' }, { value:'snacks', label:'🍡 Snacks' },
]

const filteredItems = computed(() => activeCat.value === 'all' ? store.menuItems : store.menuItems.filter(i => i.category === activeCat.value))

function catLabel(c) { return { rice:'Rice', noodles:'Noodles', drinks:'Drinks', snacks:'Snacks', other:'Other' }[c] || c }
function itemEmoji(cat) { return { rice:'🍚', noodles:'🍜', drinks:'🥤', snacks:'🍡', other:'🍽️' }[cat] || '🍽️' }

function toggleNotif() {
  showNotif.value = !showNotif.value
}

function handleNotifClick(n) {
  notif.markAsRead(n.id)
  showNotif.value = false
}

function resetForm() { form.name=''; form.description=''; form.price=''; form.category='rice'; form.isAvailable=true; formError.value=''; editingItem.value=null }
function openAdd() { resetForm(); showModal.value=true }
function openEdit(item) { editingItem.value=item; form.name=item.name||''; form.description=item.description||''; form.price=item.price||''; form.category=item.category||'rice'; form.isAvailable=item.isAvailable??true; formError.value=''; showModal.value=true }
function closeModal() { showModal.value=false; resetForm() }

async function save() {
  if (!form.name.trim()) { formError.value='Name is required.'; return }
  if (!form.price || isNaN(form.price) || Number(form.price) < 0) { formError.value='Enter a valid price.'; return }
  saving.value=true; formError.value=''
  try {
    const payload = { name:form.name.trim(), description:form.description.trim(), price:Number(form.price), category:form.category, isAvailable:form.isAvailable }
    if (editingItem.value) await store.updateMenuItem(editingItem.value.id, payload)
    else await store.addMenuItem(store.myVendor.id, payload)
    closeModal()
  } catch(e) { formError.value = e?.response?.data?.message || 'Something went wrong.' }
  finally { saving.value=false }
}

async function toggleStock(itemId) { await store.toggleStock(itemId) }
async function confirmDelete(itemId) { if (confirm('Delete this menu item?')) await store.deleteMenuItem(itemId) }
async function toggleOpen() { if (store.myVendor) await store.toggleOpen(store.myVendor.id) }

onMounted(async () => {
  try {
    if (!store.myVendor) await store.fetchMyVendor(auth.user?.id)
    if (store.myVendor) await store.fetchMenu(store.myVendor.id)
  } finally { loading.value=false }
  notif.fetchNotifications()
})
</script>
