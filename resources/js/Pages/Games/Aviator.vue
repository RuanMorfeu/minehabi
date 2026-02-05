<template>
  <BaseLayout :title="$t('Aviator')">
    <div v-if="!auth?.user" class="game-lock-container flex items-center justify-center">
      <div class="text-center">
        <i class="fa-solid fa-lock text-6xl text-gray-400 mb-4"></i>
        <h2 class="text-2xl font-bold text-white mb-2">Acesso Restrito</h2>
        <p class="text-gray-300 mb-4">Faça login para jogar Aviator</p>
        <a href="/login" class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors inline-block">
          Fazer Login
        </a>
      </div>
    </div>
    
    <div v-else>
      <Teleport to="body" :disabled="!isMobile">
        <div class="aviator-game-container">
            <iframe 
                src="/aviator/play" 
                class="w-full h-full border-0"
                allow="autoplay; fullscreen"
                title="Aviator Game"
                @load="onIframeLoad"
            ></iframe>
        </div>
      </Teleport>
    </div>
  </BaseLayout>
</template>

<script setup>
import BaseLayout from '@/Layouts/BaseLayout.vue'
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

const onIframeLoad = () => {
    // Iframe carregado
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
.game-lock-container {
  min-height: 600px;
  background: #2e343f;
}

.aviator-game-container {
  width: 100%;
  height: 800px; /* Altura padrão para desktop */
  background: #000;
  overflow: hidden;
  border-radius: 8px;
}

@media screen and (max-width: 1055px) {
  .aviator-game-container {
    position: fixed !important;
    top: 0 !important;
    left: 0 !important;
    width: 100vw !important;
    height: 100vh !important;
    height: 100dvh !important; /* Altura dinâmica para mobile browsers */
    z-index: 99999 !important;
    background: #000 !important;
    margin: 0 !important;
    padding: 0 !important;
    display: block !important;
    border-radius: 0 !important;
  }
}
</style>
