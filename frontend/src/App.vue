<template>
  <router-view />
</template>

<script setup>
import { watch } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const auth   = useAuthStore()
const router = useRouter()

watch(() => auth.isLoggedIn, (val) => {
  if (!val) router.push('/login')
})
</script>
