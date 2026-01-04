<template>
    <div v-if="uiStore.showProviderGamePopup" class="popup-backdrop">
        <div class="popup-container">
            <div class="popup-content">
                <button @click="closePopup" class="close-button">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                    <span class="sr-only">Fechar</span>
                </button>

                <div class="popup-header">
                    <div class="promo-image-container">
                        <div class="promo-image">
                            <img src="https://assets.dei.bet/Ganhe%20(3).svg" alt="Deposite para jogar" class="w-full h-auto">
                        </div>
                    </div>
                </div>

                <div class="popup-body">
                    <h3 class="popup-title">
                        Para Jogar Ao Vivo, Faça seu 1º Depósito!
                    </h3>
                    <p class="popup-message">
                        Faça seu primeiro depósito para jogar este e todos os outros jogos da nossa plataforma.
                    </p>

                    <div class="mt-4 space-y-3">
                        <button @click="goToDeposit" type="button" class="action-button w-full">
                            Depositar Agora
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { useUIStore } from '@/Stores/UIStore';
import { useRouter } from 'vue-router';

const uiStore = useUIStore();
const router = useRouter();

const game = computed(() => uiStore.providerGame);

const closePopup = () => {
    uiStore.setProviderGamePopup(false);
};

const goToDeposit = () => {
    router.push('/profile/deposit');
    closePopup();
};

const getCover = (slug) => {
    if (slug && slug.startsWith('http')) {
        return slug;
    }
    return `https://cdn.777.com/game/images/${slug}.png`;
};
</script>

<style scoped>
/* Estilos base do popup */
.popup-backdrop {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: rgba(0, 0, 0, 0.8);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 9999;
  padding: 1rem;
}

.popup-container {
  width: 100%;
  max-width: 380px;
  max-height: 92vh;
  overflow-y: auto;
  background-color: #000;
  border-radius: 0.5rem;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.5);
  position: relative;
  margin: 0 auto;
  border: 1px solid #000000;
}

.popup-content {
  display: flex;
  flex-direction: column;
  width: 100%;
  overflow: hidden;
  border-radius: 0.5rem;
  position: relative;
}

/* Botão de fechar */
.close-button {
  position: absolute;
  top: 12px;
  right: 12px;
  width: 30px;
  height: 30px;
  background-color: rgba(0, 0, 0, 0.5);
  border-radius: 50%;
  display: flex;
  justify-content: center;
  align-items: center;
  color: white;
  z-index: 10;
  border: none;
  cursor: pointer;
  transition: background-color 0.2s;
}

.close-button:hover {
  background-color: rgba(255, 0, 0, 0.7);
}

/* Área do cabeçalho com logo e imagem */
.popup-header {
  background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 50%, #1e3a8a 100%);
  padding-top: 8px;
  position: relative;
  overflow: hidden;
  margin-bottom: -2px; /* Elimina qualquer espaço entre o cabeçalho e o corpo */
}

/* Cria uma transição suave entre o azul e o preto */
.popup-header::after {
  content: '';
  position: absolute;
  bottom: -2px;
  left: 0;
  right: 0;
  height: 60px;
  background: linear-gradient(to bottom, 
    rgba(37,99,235,0) 0%, 
    rgba(30,58,138,0.3) 25%, 
    rgba(23,37,84,0.6) 50%, 
    rgba(10,10,20,0.8) 75%, 
    rgba(0,0,0,1) 100%);
  z-index: 1;
}

.popup-header::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: radial-gradient(circle at center, rgba(255,255,255,0.2) 0%, rgba(0,0,0,0.1) 70%);
  pointer-events: none;
}

/* Container da imagem promocional */
.promo-image-container {
  position: relative;
  padding: 0;
  overflow: hidden;
}

.promo-image {
  display: flex;
  justify-content: center;
  align-items: center;
}

.promo-image img {
  max-width: 100%;
  height: auto;
  max-height: 220px;
  display: block;
  object-fit: contain;
  margin: 0 auto;
  padding-bottom: 20px; /* Aumentado para dar mais espaço para a transição */
}

/* Área do corpo com texto e botão */
.popup-body {
  background: linear-gradient(to bottom, rgba(0,0,0,1) 0%, rgba(20,20,20,0.95) 100%);
  padding: 18px;
  padding-top: 8px; /* Ajustado para um valor intermediário */
  text-align: center;
  position: relative;
  /* Removida a borda para melhorar a transição */
}

.popup-body::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: radial-gradient(ellipse at center, rgba(100,100,100,0.1) 0%, rgba(0,0,0,0.2) 100%);
  opacity: 0.3;
  pointer-events: none;
}

/* Título do popup */
.popup-title {
  font-size: 1.3rem;
  font-weight: bold;
  color: white;
  margin-bottom: 14px;
  text-shadow: 0 2px 4px rgba(0,0,0,0.5);
  text-transform: uppercase;
}

/* Mensagem do popup */
.popup-message {
  font-size: 1rem;
  color: #e0e0e0;
  margin-bottom: 18px;
  line-height: 1.45;
}

/* Botão de ação - igual ao botão de depósito */
.action-button {
  border-radius: 15px;
  border: 1px solid #3b82f6;
  width: 100%;
  padding: 10px 15px;
  font-weight: 600;
  color: #ffffff;
  background: linear-gradient(#3b82f6, #2563eb);
  transition: 0.3s ease all;
  font-size: 0.95rem;
  text-transform: uppercase;
  cursor: pointer;
  animation: glowing 2.5s infinite;
  letter-spacing: 0.5px;
}

/* Efeito hover no botão de ação */
.action-button:hover {
  border-radius: 15px;
  border: 1px solid #3b82f6;
  background: linear-gradient(#3b82f6, #2563eb);
}

/* Animação de glowing para o botão */
@keyframes glowing {
  0% {
    box-shadow: 0 0 5px #3b82f6;
  }
  50% {
    box-shadow: 0 0 20px #3b82f6;
  }
  100% {
    box-shadow: 0 0 5px #3b82f6;
  }
}

.action-button:active {
  opacity: 0.9;
  transform: scale(0.98);
}
</style>
