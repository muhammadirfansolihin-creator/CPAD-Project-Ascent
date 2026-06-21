<template>
  <div class="login-page">
    <div class="login-card">
      <div class="login-brand">
        <h1>CampusEats</h1>
        <p>Your campus food, ready when you are.</p>
      </div>

      <div class="login-tabs">
        <button :class="['tab', mode==='login'?'active':'']" @click="mode='login'">Sign In</button>
        <button :class="['tab', mode==='register'?'active':'']" @click="mode='register'">Register</button>
      </div>

      <form @submit.prevent="submit">
        <div v-if="error" class="alert alert-error">{{ error }}</div>

        <template v-if="mode==='register'">
          <div class="form-group">
            <label class="form-label">Full Name</label>
            <input v-model="name" class="form-control" placeholder="Your full name" required />
          </div>
          <div class="form-group">
            <label class="form-label">Register as</label>
            <select v-model="role" class="form-control">
              <option value="student">Student</option>
              <option value="vendor">Vendor / Stall Operator</option>
            </select>
          </div>
        </template>

        <div class="form-group">
          <label class="form-label">Email</label>
          <input v-model="email" type="email" class="form-control" placeholder="name@student.utm.my" required />
        </div>
        <div class="form-group">
          <label class="form-label">Password</label>
          <input v-model="password" type="password" class="form-control" placeholder="••••••••" required />
        </div>

        <button type="submit" class="btn btn-primary btn-block" :disabled="loading">
          <span v-if="loading">Loading…</span>
          <span v-else>{{ mode==='login' ? 'Sign In' : 'Create Account' }}</span>
        </button>
      </form>

      <p class="login-hint">
        Demo — student@campuseats.my / vendor@campuseats.my / admin@campuseats.my (password: password)
      </p>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const auth     = useAuthStore()
const router   = useRouter()
const mode     = ref('login')
const name     = ref('')
const email    = ref('')
const password = ref('')
const role     = ref('student')
const error    = ref('')
const loading  = ref(false)

async function submit() {
  error.value   = ''
  loading.value = true
  try {
    let user
    if (mode.value === 'login') {
      user = await auth.login(email.value, password.value)
    } else {
      user = await auth.register(name.value, email.value, password.value, role.value)
    }
    if (user.role === 'vendor') router.push('/vendor')
    else if (user.role === 'admin') router.push('/admin')
    else router.push('/')
  } catch (e) {
    error.value = e.response?.data?.error || 'Something went wrong'
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
.login-page {
  min-height: 100vh; display: flex; align-items: center; justify-content: center;
  background: var(--color-bg); padding: 1rem;
}
.login-card {
  background: var(--color-surface); border-radius: var(--radius); border: 1.5px solid var(--color-border);
  box-shadow: var(--shadow); padding: 2rem; width: 100%; max-width: 420px;
}
.login-brand { text-align: center; margin-bottom: 1.5rem; }
.login-brand h1 { font-size: 2rem; font-weight: 900; color: var(--color-primary); }
.login-brand p  { color: var(--color-muted); font-size: 0.9rem; margin-top: 0.25rem; }
.login-tabs { display: flex; border-radius: 0.55rem; overflow: hidden; border: 1.5px solid var(--color-border); margin-bottom: 1.25rem; }
.tab { flex: 1; padding: 0.6rem; font-weight: 600; font-size: 0.9rem; cursor: pointer; background: none; border: none; color: var(--color-muted); transition: var(--transition); }
.tab.active { background: var(--color-primary); color: #fff; }
.login-hint { text-align: center; font-size: 0.72rem; color: var(--color-muted); margin-top: 1rem; line-height: 1.5; }
</style>
