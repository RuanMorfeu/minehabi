<template>
    <div>
      <LoadingComponent :isLoading="isLoading">
        <div class="text-center">
          <span>{{ $t('Loading game information') }}</span>
        </div>
      </LoadingComponent>
  
      <div v-if="showErrorImage" class="error-container">
        <span class="error-message">{{ errorMessage }}</span>
      </div>
  
      <div v-if="!isLoading && game && !showErrorImage" ref="fullscreenContainer" class="game-container">
        <iframe :src="gameUrl" @load="handleIframeLoad" class="game-full" allowfullscreen></iframe>
      </div>
  
      <div v-if="undermaintenance" class="flex flex-col items-center justify-center text-center py-24">
        <h1 class="text-2xl mb-4">JOGO EM MANUTENÇÃO</h1>
        <img :src="`/assets/images/work-in-progress.gif`" alt="Manutenção" width="400">
      </div>
    </div>
  
    <!-- Botão de volta com fundo preto e ícone de casinha branca -->
    <div class="absolute top-4 left-4">
        <a href="/" class="home-button">
            <i class="fas fa-home"></i>
        </a>
    </div>
  </template>
  
  <script>
  import { useRoute, useRouter } from "vue-router";
  import LoadingComponent from "@/Components/UI/LoadingComponent.vue";
  import HttpApi from "@/Services/HttpApi.js";
  import { useAuthStore } from "@/Stores/Auth.js";
  
  export default {
    components: {
      LoadingComponent,
    },
    data() {
      return {
        isLoading: true,
        game: null,
        gameUrl: null,
        gameId: null,
        showErrorImage: false,
        errorMessage: '',
        errorImage: '',
        undermaintenance: false,
      };
    },
    methods: {
      async fetchGameDetails() {
  this.isLoading = true;
  this.showErrorImage = false;

  try {
    const response = await HttpApi.get(`games/single/${this.gameId}`);
    
    // Verifica se a resposta indica erro (status false)
    if (response.data.status === false) {
      this.showErrorImage = true;
      this.errorMessage = response.data.error;
      return;
    }

    // Se não houver erro, procede normalmente
    this.game = response.data.game;
    this.gameUrl = response.data.gameUrl;
  } catch (error) {
    this.showErrorImage = true;
    this.errorMessage = "Erro ao carregar o jogo.";
  } finally {
    this.isLoading = false;
  }
},
      checkAuthentication() {
        const authStore = useAuthStore();
        const router = useRouter();
  
        if (!authStore.isAuth) {
          router.push({ name: 'login' });
        }
      }
    },
    async created() {
      const route = useRoute();
      this.gameId = route.params.id;
      this.checkAuthentication();
      await this.fetchGameDetails();
    }
  };
  </script>
  
  <style>
  .error-container {
    display: flex;
    justify-content: center;
    align-items: center;
    height: 100vh;
    background-color: transparent;
  }
  
  .error-message {
    font-size: 1.5rem;
    color: #fff;
    text-align: center;
  }
  
  .home-button {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 45px;
    height: 45px;
    background-color: #000;  /* Fundo preto */
    border-radius: 50%;
    color: #fff;  /* Ícone branco */
    font-size: 25px;
  }
  
  .game-container {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    display: flex;
    justify-content: center;
    align-items: center;
    overflow: hidden;
    padding: env(safe-area-inset-top) env(safe-area-inset-right) calc(env(safe-area-inset-bottom) + 1px) env(safe-area-inset-left);
  }
  
  .game-full {
    width: 100%;
    height: 100%;
    border: none;
    box-sizing: border-box;
  }
  </style>
  