<template>
  <BaseLayout :title="$t('Mines Game')">
    <div v-if="!auth?.user" class="mines-game-container flex items-center justify-center">
      <div class="text-center">
        <i class="fa-solid fa-lock text-6xl text-gray-400 mb-4"></i>
        <h2 class="text-2xl font-bold text-white mb-2">Acesso Restrito</h2>
        <p class="text-gray-300 mb-4">Faça login para jogar Mines</p>
        <button @click="$router.push({ name: 'login' })" class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
          Fazer Login
        </button>
      </div>
    </div>
    
    <div v-else>
      <Teleport to="body" :disabled="!isMobile">
        <div class="mines-game-container">
          <Mines />
        </div>
      </Teleport>
    </div>
  </BaseLayout>
</template>

<script setup>
import BaseLayout from '@/Layouts/BaseLayout.vue'
import Mines from '@/Components/Games/Mines/Mines.vue'
import { useAuthStore } from '@/Stores/Auth.js'
import { computed, ref, onMounted, onUnmounted } from 'vue'

const authStore = useAuthStore()
const auth = computed(() => ({
  user: authStore.user
}))

// Controle de Mobile/Fullscreen
const isMobile = ref(false)

const checkMobile = () => {
  isMobile.value = window.innerWidth <= 1055
}

onMounted(() => {
  checkMobile()
  window.addEventListener('resize', checkMobile)
})

onUnmounted(() => {
  window.removeEventListener('resize', checkMobile)
})
</script>

<style scoped>
@font-face {
  font-family: 'Roboto';
  src: url('/assets/fonts/Roboto-Regular.ttf');
}

.mines-game-container {
  min-height: 100vh;
  background: #2e343f;
  margin: 0;
  padding: 0;
  font-family: 'Roboto', sans-serif;
}

@media screen and (max-width: 1055px) {
  .mines-game-container {
    position: fixed !important;
    top: 0 !important;
    left: 0 !important;
    width: 100vw !important;
    height: 100vh !important;
    height: 100dvh !important;
    z-index: 99999 !important;
    overflow-y: auto !important; /* Permitir rolagem se o conteúdo for maior que a tela */
    background: #2e343f !important;
    margin: 0 !important;
    padding: 0 !important;
    display: block !important;
  }
}

.mines-game-container * {
  font-family: 'Roboto', sans-serif;
  box-sizing: border-box;
}
</style>
