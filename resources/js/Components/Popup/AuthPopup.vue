<template>
  <!-- Pop-up com novos estilos inspirados na imagem de referência -->
  <div v-if="show" class="popup-overlay" @click.self="closeWithoutRedemption">
    <div class="popup-container">
      <!-- Conteúdo do pop-up com novo design -->
      <div class="popup-content">
        <!-- Botão de fechar (X) no canto superior direito -->
        <button @click="closeWithoutRedemption" type="button" class="close-button">
          <svg class="w-4 h-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
          </svg>
          <span class="sr-only">Fechar</span>
        </button>
        
        <!-- Área superior com logo e fundo verde degradê -->
        <div class="popup-header">
          <!-- Logo do cassino no topo (mesmo usado no navtop) -->
          <div class="casino-logo">
            <img v-if="settingStore && settingStore.setting" :src="`/storage/`+settingStore.setting.software_logo_white" alt="Logo do Cassino" class="logo-image block">
          </div>
          
          <!-- Área da imagem promocional com fundo verde degradê -->
          <div class="promo-image-container">
            <div v-if="image" class="promo-image">
              <img :src="image.startsWith('http') ? image : '/storage/' + image" :alt="title" class="w-full h-auto">
            </div>
          </div>
        </div>
        
        <!-- Área de conteúdo com fundo escuro esfumaçado -->
        <div class="popup-body">
          <!-- Título abaixo da imagem -->
          <h3 class="popup-title">
            {{ title }}
          </h3>
          
          <!-- Mensagem/descrição -->
          <p class="popup-message">
            {{ message }}
          </p>
          
          <!-- Botão de ação -->
          <button @click="closeWithRedemption" type="button" class="action-button">
            {{ buttonText }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { useAuthStore } from "@/Stores/Auth.js";
import { usePopupStore } from "@/Stores/PopupStore.js";
import { useSettingStore } from "@/Stores/SettingStore.js";
import { useToast } from "vue-toastification";

export default {
  data() {
    return {
      isProcessingFreespin: false,
      freespinResult: null,
      toast: null
    }
  },
  created() {
    this.toast = useToast();
  },
  mounted() {
    document.body.classList.remove('overflow-hidden');
    if (this.show) {
        document.body.classList.add('overflow-hidden');
    }
  },
  unmounted() {
    document.body.classList.remove('overflow-hidden');
  },
  computed: {
    // Stores
    authStore() { return useAuthStore() },
    popupStore() { return usePopupStore() },
    settingStore() { return useSettingStore() },

    // State from stores
    show() { return this.popupStore.showAuthPopup },
    title() { return this.popupStore.popupTitle },
    message() { return this.popupStore.popupMessage },
    image() { return this.popupStore.popupImage },
    buttonText() { return this.popupStore.popupButtonText },
    redirectUrl() { return this.popupStore.redirectUrl },
    popupId() { return this.popupStore.currentPopupId },
    showOnlyOnce() { return this.popupStore.showOnlyOnce },
    requireRedemption() { return this.popupStore.requireRedemption },
    browserPersistent() { return this.popupStore.browserPersistent },
    currentUserId() { return this.authStore.user?.id || 'guest' },
  },
  watch: {
      show(newVal) {
          if(newVal) {
              document.body.classList.add('overflow-hidden');
              // Registrar visualização quando o pop-up é exibido
              this.recordView();
          } else {
              document.body.classList.remove('overflow-hidden');
          }
      }
  },

  methods: {
    async closeWithRedemption() {
      // Registrar clique no botão
      this.recordClick();
      
      if (this.popupStore.gameFreespinActive && this.popupStore.gameCodeFreespin && this.popupStore.roundsFreespin > 0) {
        this.isProcessingFreespin = true;
        try {
          const token = this.authStore.getToken();
          const response = await fetch('/api/popups/process-freespin', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'Authorization': `Bearer ${token}`
            },
            body: JSON.stringify({
              popup_id: this.popupId,
              game_code: this.popupStore.gameCodeFreespin,
              rounds: this.popupStore.roundsFreespin
            })
          });
          
          const data = await response.json();
          this.freespinResult = data;
          
          if (data.success) {
            // Registrar resgate bem-sucedido
            this.recordRedemption(true);
            
            let successMessage = '';
            if (data.rounds) {
              const gameName = this.popupStore.gameNameFreespin || data.game_name || this.popupStore.gameCodeFreespin;
              successMessage = `Parabéns! Você recebeu ${data.rounds} rodadas grátis no jogo ${gameName}!`;
            }
            if (data.credit_added) {
              if (this.authStore && this.authStore.user) {}
            }
            if (data.message && !successMessage) {
              successMessage = data.message;
            }
            this.toast.success(successMessage, { timeout: 5000, position: "top-right", closeOnClick: true, pauseOnHover: true, draggable: true });
          } else if (data.already_redeemed) {
            // Registrar tentativa de resgate já realizado (não conta como sucesso)
            this.recordRedemption(false);
            this.toast.info('Você já resgatou as rodadas grátis deste popup anteriormente.', { timeout: 4000, position: "top-right", closeOnClick: true });
          } else {
            // Registrar resgate falhado
            this.recordRedemption(false);
            this.toast.error(`Erro ao processar rodadas grátis: ${data.message}`, { timeout: 4000, position: "top-right", closeOnClick: true });
          }
        } catch (error) {
          this.freespinResult = { success: false, message: 'Erro ao processar rodadas grátis' };
          this.toast.error('Erro ao processar rodadas grátis. Por favor, tente novamente mais tarde.', { timeout: 4000, position: "top-right", closeOnClick: true });
        } finally {
          this.isProcessingFreespin = false;
        }
      }
      
      if (this.showOnlyOnce && this.popupId) {
        this.markPopupAsShownToUser(this.popupId);
      }
      
      if (this.requireRedemption && this.popupId) {
        this.markPopupAsRedeemedByUser(this.popupId);
        this.markPopupAsShownToUser(this.popupId);
      }
      
      if (this.redirectUrl) {
        window.location.href = this.redirectUrl;
      }
      
      this.popupStore.hidePopup();
    },
    
    closeWithoutRedemption() {
      if (this.showOnlyOnce && !this.requireRedemption && this.popupId) {
        this.markPopupAsShownToUser(this.popupId);
      }
      this.popupStore.hidePopup();
    },
    
    markPopupAsShownToUser(popupId) {
      if (!popupId) return;
      try {
        if (this.browserPersistent) {
          this.markPopupAsShownInBrowser(popupId);
          return;
        }
        let viewedPopups = JSON.parse(localStorage.getItem(`viewed_popups_${this.currentUserId}`)) || [];
        if (!viewedPopups.includes(Number(popupId))) {
          viewedPopups.push(Number(popupId));
          localStorage.setItem(`viewed_popups_${this.currentUserId}`, JSON.stringify(viewedPopups));
        }
      } catch (error) {
          console.error('Error marking popup as shown for user:', error);
      }
    },
    
    markPopupAsShownInBrowser(popupId) {
      if (!popupId) return;
      try {
        let viewedPopups = JSON.parse(localStorage.getItem('browser_viewed_popups')) || [];
        if (!viewedPopups.includes(Number(popupId))) {
          viewedPopups.push(Number(popupId));
          localStorage.setItem('browser_viewed_popups', JSON.stringify(viewedPopups));
        }
      } catch (error) {
          console.error('Error marking popup as shown in browser:', error);
      }
    },
    
    markPopupAsRedeemedByUser(popupId) {
        if (!popupId) return;
        try {
            if (this.browserPersistent) {
                this.markPopupAsRedeemedInBrowser(popupId);
                return;
            }
            let redeemedPopups = JSON.parse(localStorage.getItem(`redeemed_popups_${this.currentUserId}`)) || [];
            if (!redeemedPopups.includes(Number(popupId))) {
                redeemedPopups.push(Number(popupId));
                localStorage.setItem(`redeemed_popups_${this.currentUserId}`, JSON.stringify(redeemedPopups));
            }
        } catch (error) {
            console.error('Error marking popup as redeemed for user:', error);
        }
    },
    
    markPopupAsRedeemedInBrowser(popupId) {
        if (!popupId) return;
        try {
            let redeemedPopups = JSON.parse(localStorage.getItem('browser_redeemed_popups')) || [];
            if (!redeemedPopups.includes(Number(popupId))) {
                redeemedPopups.push(Number(popupId));
                localStorage.setItem('browser_redeemed_popups', JSON.stringify(redeemedPopups));
            }
        } catch (error) {
            console.error('Error marking popup as redeemed in browser:', error);
        }
    },

    // Métodos para rastreamento de métricas
    async recordView() {
        if (!this.popupId) return;
        
        try {
            await fetch('/api/popups/metrics/view', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': this.authStore.getToken() ? `Bearer ${this.authStore.getToken()}` : ''
                },
                body: JSON.stringify({
                    popup_id: this.popupId
                })
            });
        } catch (error) {
            console.error('Erro ao registrar visualização:', error);
        }
    },

    async recordClick() {
        if (!this.popupId) return;
        
        try {
            await fetch('/api/popups/metrics/click', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': this.authStore.getToken() ? `Bearer ${this.authStore.getToken()}` : ''
                },
                body: JSON.stringify({
                    popup_id: this.popupId
                })
            });
        } catch (error) {
            console.error('Erro ao registrar clique:', error);
        }
    },

    async recordRedemption(success = true) {
        if (!this.popupId) return;
        
        try {
            await fetch('/api/popups/metrics/redemption', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': this.authStore.getToken() ? `Bearer ${this.authStore.getToken()}` : ''
                },
                body: JSON.stringify({
                    popup_id: this.popupId,
                    success: success
                })
            });
        } catch (error) {
            console.error('Erro ao registrar resgate:', error);
        }
    }
  }
}
</script>

<style scoped>
/* Estilos base do popup */
.popup-overlay {
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

/* Logo do cassino */
.casino-logo {
  text-align: center;
  padding: 8px 0;
  position: relative;
  z-index: 2;
}

.logo-image {
  height: 20px;
  margin: 0 auto;
  filter: drop-shadow(0 2px 4px rgba(0,0,0,0.3));
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
