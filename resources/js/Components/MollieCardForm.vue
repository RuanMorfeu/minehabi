<template>
  <div class="mollie-card-form">
    <div class="CreditCardForm_container_Ry3Wr">
      <!-- Saved Cards Section -->
      <div v-if="savedCards.length > 0" class="saved-cards-section">
        <h4 style="color: white; margin-bottom: 1rem;">Cartões Salvos</h4>
        <div class="saved-cards-list">
          <div 
            v-for="card in savedCards" 
            :key="card.id"
            class="saved-card-item"
            :class="{ 'selected': selectedCard?.id === card.id }"
            @click="selectSavedCard(card)">
            <button class="delete-card-btn" 
                    @click.stop="deleteSavedCard(card)"
                    title="Remover cartão">
              <i class="fas fa-trash"></i>
            </button>
            <div class="card-info">
              <div class="card-brand">{{ card.cardLabel || 'Cartão' }}</div>
              <div class="card-number">**** **** **** {{ card.cardNumber || '****' }}</div>
              <div class="card-expiry">{{ card.cardHolder || 'Titular' }}</div>
            </div>
            <div class="card-actions">
              <button 
                class="btn-use-card"
                :disabled="processing"
                @click.stop="payWithSavedCard(card)"
              >
                {{ processing ? 'Processando...' : 'Usar Cartão' }}
              </button>
            </div>
          </div>
        </div>
        
        <div class="divider">
          <span>ou use um novo cartão</span>
        </div>
      </div>
      
      <!-- Loading Saved Cards -->
      <div v-if="loadingSavedCards" class="loading-saved-cards" style="color: white; text-align: center; margin-bottom: 1rem;">
        Carregando cartões salvos...
      </div>

      <form @submit.prevent="processPayment" id="mollie-form">
        <!-- Card Holder Name -->
        <div class="m-form-field">
          <div class="m-input m-gradient-border m-input--dark m-input--m" tabindex="0">
            <div class="m-icon-container m-input-prepend">
              <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="m-icon m-icon-loadable" name="ContactCard" loading="false">
                <path d="M21.974 18.905V8.53C21.974 6.032 19.859 4 17.258 4H6.716C4.116 4 2 6.032 2 8.531v6.938C2 17.968 4.115 20 6.716 20H21.11a.886.886 0 0 0 .863-1.095M7.662 8.814c.755 0 1.368.607 1.368 1.357s-.613 1.358-1.368 1.358a1.363 1.363 0 0 1-1.368-1.358c0-.75.612-1.357 1.368-1.357M9.8 13.777a2.34 2.34 0 0 1-2.14 1.39 2.35 2.35 0 0 1-2.15-1.416.85.85 0 0 1 .532-1.133l.691-.226a3 3 0 0 1 1.856 0l.69.225a.855.855 0 0 1 .52 1.16m7.578 1.025h-4.97a.886.886 0 0 1-.89-.883c0-.487.399-.882.89-.882h4.97c.49 0 .888.395.888.882a.886.886 0 0 1-.888.883m0-3.765h-4.97a.886.886 0 0 1-.89-.882c0-.487.399-.883.89-.883h4.97c.49 0 .888.396.888.883a.886.886 0 0 1-.888.882" fill="currentColor"></path>
              </svg>
            </div>
            <div class="m-input-content">
              <label class="floating-label" :class="{ 'has-content': cardHolderHasContent, 'has-focus': cardHolderHasFocus }">
                Nome no cartão
              </label>
              <div id="card-holder" class="mollie-component-container card-input"></div>
            </div>
          </div>
        </div>

        <!-- Card Number -->
        <div class="m-form-field">
          <div class="m-input m-gradient-border m-input--dark m-input--m" tabindex="0">
            <div class="m-icon-container m-input-prepend">
              <img class="card-icon" src="https://cdn.bdmstatic.com/front/components/cashier/cards/default.svg" alt="">
            </div>
            <div class="m-input-content">
              <label class="floating-label" :class="{ 'has-content': cardNumberHasContent, 'has-focus': cardNumberHasFocus }">
                Número do cartão
              </label>
              <div id="card-number" class="mollie-component-container card-input"></div>
            </div>
          </div>
        </div>
        
        <!-- Expiry Date and CVC Row -->
        <div class="CreditCardForm_row_XfUrw">
          <div class="m-form-field">
            <div class="m-input m-gradient-border m-input--dark m-input--m" tabindex="0">
              <div class="m-icon-container m-input-prepend">
                <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="m-icon m-icon-loadable" name="Calendar" loading="false">
                  <path d="M20.983 11.119H3.001l.017 6.171c0 2.597 2.096 4.71 4.673 4.71h8.637C18.904 22 21 19.887 21 17.29v-.002zM8.284 18.424c-.536 0-.971-.45-.971-1.006s.435-1.006.971-1.006a1.363 1.363 0 0 1-1.368-1.358c0-.75.612-1.357 1.368-1.357.537 0 .972.45.972 1.006 0 .555-.435 1.006-.972 1.006m0-3.521c-.536 0-.971-.45-.971-1.006 0-.555.435-1.006.971-1.006.537 0 .972.45.972 1.006s-.435 1.006-.972 1.006m3.724 3.52c-.537 0-.971-.45-.971-1.005 0-.556.434-1.006.97-1.006.537 0 .972.45.972 1.006 0 .555-.435 1.006-.971 1.006m0-3.52c-.537 0-.971-.45-.971-1.006 0-.555.434-1.006.97-1.006.537 0 .972.45.972 1.006s-.435 1.006-.971 1.006m4.365-.25a.95.95 0 0 1-1.371-.092 1.03 1.03 0 0 1 .088-1.42.95.95 0 0 1 1.371.092 1.03 1.03 0 0 1-.088 1.42M18.229 5.317V3.838c0-.462-.363-.838-.81-.838-.446 0-.81.376-.81.838v1.073q-.15-.011-.303-.011H7.67q-.145 0-.287.01V3.838c0-.462-.363-.838-.81-.838-.446 0-.809.376-.809.838v1.473A4.72 4.72 0 0 0 3 9.442h17.976a4.72 4.72 0 0 0-2.747-4.125" fill="currentColor"></path>
                </svg>
              </div>
              <div class="m-input-content">
                <div id="expiry-date" class="mollie-component-container card-input"></div>
              </div>
            </div>
          </div>
          <div class="m-form-field">
            <div class="m-input m-gradient-border m-input--dark m-input--m" tabindex="0">
              <div class="m-icon-container m-input-prepend">
                <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="m-icon m-icon-loadable" name="ShieldTask" loading="false">
                  <path d="m21 8.378-.001-1.991c-.002-.983-.638-1.629-1.621-1.644a9.6 9.6 0 0 1-3.426-.689c-1.007-.4-1.978-.956-2.97-1.7-.629-.47-1.325-.472-1.96-.004l-.069.05c-.127.095-.247.183-.369.268-1.977 1.378-3.95 2.058-6.033 2.077-.892.009-1.542.68-1.546 1.595l-.001.536c-.004.83-.008 1.687.004 2.535q.004.34.005.683c.003.789.007 1.603.088 2.418.221 2.232 1.172 4.221 2.827 5.913C7.339 19.87 9.09 20.973 11.44 21.9a1.62 1.62 0 0 0 1.147-.01c1.946-.773 3.515-1.695 4.797-2.818 2.235-1.957 3.44-4.403 3.58-7.27.03-.586.023-1.17.016-1.736H21zm-5.226 2.128-4.216 4.227a.857.857 0 0 1-1.217.001l-2.114-2.112a.88.88 0 0 1-.007-1.236.86.86 0 0 1 1.223-.007l1.505 1.503 3.607-3.617a.86.86 0 0 1 1.223.005.88.88 0 0 1-.004 1.236" fill="currentColor"></path>
                </svg>
              </div>
              <div class="m-input-content">
                <label class="floating-label" :class="{ 'has-content': verificationCodeHasContent, 'has-focus': verificationCodeHasFocus }">
                  CVC/CVV
                </label>
                <div id="verification-code" class="mollie-component-container card-input"></div>
              </div>
            </div>
          </div>
        </div>
        
        <!-- Security Badges -->
        <div class="security-badges">
          <div class="visa-verified-badge"></div>
          <div class="mc-securecode-badge"></div>
        </div>
        
        <div class="form-actions">
          <button 
            type="submit"
            :disabled="processing || !isFormValid"
            class="btn btn-primary"
          >
            <span v-if="processing">Processando...</span>
            <span v-else>Pagar {{ formatCurrency(amount) }}</span>
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script>
import HttpApi from '@/Services/HttpApi.js'
import { useAuthStore } from '@/Stores/Auth.js'

export default {
  name: 'MollieCardForm',
  props: {
    amount: {
      type: [String, Number],
      required: true
    },
    profileId: {
      type: String,
      required: true
    }
  },
  data() {
    return {
      mollie: null,
      cardNumber: null,
      cardHolder: null,
      expiryDate: null,
      verificationCode: null,
      cardNumberHasContent: false,
      cardNumberHasFocus: false,
      cardHolderHasContent: false,
      cardHolderHasFocus: false,
      expiryDateHasContent: false,
      expiryDateHasFocus: false,
      verificationCodeHasContent: false,
      verificationCodeHasFocus: false,
      processing: false,
      savedCards: [],
      selectedCard: null,
      selectedSavedCard: null,
      showSavedCards: false,
      loadingSavedCards: false
    }
  },
  watch: {
    profileId: {
      handler(newProfileId) {
        if (newProfileId && newProfileId.startsWith('pfl_')) {
          this.initializeMollie()
        }
      },
      immediate: true
    }
  },
  beforeUnmount() {
    this.cleanup()
  },
  beforeDestroy() {
    this.cleanup()
  },
  methods: {
    async initializeMollie() {
      try {
        // Verificar se já foi inicializado para evitar duplicação
        if (this.mollie) {
          return
        }

        // Validar Profile ID antes de inicializar
        if (!this.profileId || !this.profileId.startsWith('pfl_')) {
          return
        }
        
        // Carregar cartões salvos
        await this.loadSavedCards()

        // Carregar Mollie.js se não estiver carregado
        if (!window.Mollie) {
          await this.loadMollieScript()
        }

        // Aguardar o DOM estar pronto
        await this.$nextTick()

        // Inicializar Mollie com profile ID seguindo documentação oficial
        this.mollie = window.Mollie(this.profileId, {
          locale: 'pt_PT',
          testmode: false
        })

        // Criar os 4 componentes individuais seguindo documentação oficial
        await this.createComponents()

      } catch (error) {
        this.$emit('error', 'Erro ao carregar formulário de pagamento')
      }
    },

    async createComponents() {
      
      // Configuração base para todos os componentes
      const baseOptions = {
        styles: {
          base: {
            fontSize: '16px',
            color: '#ffffff',
            fontWeight: '400',
            fontFamily: '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif',
            '::placeholder': {
              color: '#9ca3af'
            }
          },
          invalid: {
            color: '#3b82f6',
          },
          valid: {
            color: '#3b82f6',
          },
        }
      }

      try {
        // Criar componente do nome no cartão
        this.cardHolderComponent = this.mollie.createComponent('cardHolder', baseOptions)
        await this.cardHolderComponent.mount('#card-holder')
        this.cardHolderComponent.addEventListener('change', (event) => this.handleComponentChange('cardHolder', event))
        this.cardHolderComponent.addEventListener('focus', () => this.cardHolderHasFocus = true)
        this.cardHolderComponent.addEventListener('blur', () => this.cardHolderHasFocus = false)

        // Criar componente do número do cartão
        this.cardNumberComponent = this.mollie.createComponent('cardNumber', baseOptions)
        await this.cardNumberComponent.mount('#card-number')
        this.cardNumberComponent.addEventListener('change', (event) => this.handleComponentChange('cardNumber', event))
        this.cardNumberComponent.addEventListener('focus', () => this.cardNumberHasFocus = true)
        this.cardNumberComponent.addEventListener('blur', () => this.cardNumberHasFocus = false)

        // Criar componente da data de expiração
        this.expiryDateComponent = this.mollie.createComponent('expiryDate', baseOptions)
        await this.expiryDateComponent.mount('#expiry-date')
        this.expiryDateComponent.addEventListener('change', (event) => this.handleComponentChange('expiryDate', event))

        // Criar componente do CVC
        this.verificationCodeComponent = this.mollie.createComponent('verificationCode', baseOptions)
        await this.verificationCodeComponent.mount('#verification-code')
        this.verificationCodeComponent.addEventListener('change', (event) => this.handleComponentChange('verificationCode', event))
        this.verificationCodeComponent.addEventListener('focus', () => this.verificationCodeHasFocus = true)
        this.verificationCodeComponent.addEventListener('blur', () => this.verificationCodeHasFocus = false)

        
      } catch (error) {
        console.error('[MOLLIE] Erro ao criar componentes:', error)
        throw error
      }
    },

    loadMollieScript() {
      return new Promise((resolve, reject) => {
        if (document.querySelector('script[src*="mollie.js"]')) {
          resolve()
          return
        }

        const script = document.createElement('script')
        script.src = 'https://js.mollie.com/v1/mollie.js'
        script.onload = resolve
        script.onerror = reject
        document.head.appendChild(script)
      })
    },

    handleComponentChange(componentType, event) {
      // Atualizar estados dos labels flutuantes baseado no conteúdo
      this.updateFloatingLabels(componentType, event)

      // Verificar se todos os componentes são válidos
      this.checkFormValidity()
    },

    updateFloatingLabels(componentType, event) {
      // Lógica original que funcionava antes da exclusão de cartões
      const hasContent = (event.touched && !event.empty) || event.valid
      
      
      switch (componentType) {
        case 'cardHolder':
          this.cardHolderHasContent = hasContent
          break
        case 'cardNumber':
          this.cardNumberHasContent = hasContent
          break
        case 'verificationCode':
          this.verificationCodeHasContent = hasContent
          break
      }
    },

    validateForm() {
      // Método para validar formulário - chama checkFormValidity
      this.checkFormValidity()
    },

    checkFormValidity() {
      // Habilitar botão quando pelo menos os campos obrigatórios estão preenchidos
      // Na prática, o Mollie valida internamente antes de criar o token
      this.isFormValid = true
    },

    async processPayment() {
      if (!this.isFormValid || this.processing) return

      this.processing = true

      try {
        const authStore = useAuthStore()
        const token = authStore.getToken()
        
        if (!token) {
          throw new Error('Usuário não autenticado. Faça login para continuar.')
        }

        // Criar token do cartão
        const { token: cardToken, error } = await this.mollie.createToken()
        
        if (error) {
          let errorMessage = 'Por favor, preencha todos os campos do cartão corretamente'
          
          if (error.message && error.message.includes('inválidos')) {
            errorMessage = 'Por favor, verifique os dados do cartão e preencha todos os campos'
          } else if (error.message) {
            errorMessage = error.message
          }
          
          throw new Error(errorMessage)
        }

        this.cardToken = cardToken

        // Enviar token para o backend
        
        const response = await HttpApi.post('/mollie/create-payment-token', {
          amount: this.amount,
          cardToken: cardToken
        }).catch(error => {
          console.error('[MOLLIE] Erro detalhado da requisição:', {
            status: error.response?.status,
            statusText: error.response?.statusText,
            data: error.response?.data,
            headers: error.response?.headers,
            config: {
              url: error.config?.url,
              method: error.config?.method,
              headers: error.config?.headers
            }
          })
          throw error
        })

        if (response.data.status) {
          // Se requer 3DS, redirecionar
          if (response.data.requires_3ds && response.data.checkout_url) {
            // Redirecionar para 3DS na mesma janela
            window.location.href = response.data.checkout_url
          } else {
            // Pagamento processado com sucesso
            this.$emit('payment-success', response.data)
          }
        } else {
          throw new Error(response.data.message || 'Erro no pagamento')
        }

      } catch (error) {
        let userFriendlyMessage = 'Erro no processamento do pagamento'
        
        if (error.message && error.message.includes('campos')) {
          userFriendlyMessage = error.message
        } else if (error.message && error.message.includes('autenticado')) {
          userFriendlyMessage = error.message
        } else if (error.message) {
          userFriendlyMessage = error.message
        }
        
        this.$emit('payment-error', userFriendlyMessage)
      } finally {
        this.processing = false
      }
    },

    cleanup() {
      try {
        if (this.cardNumber && typeof this.cardNumber.unmount === 'function') {
          this.cardNumber.unmount()
        }
        if (this.cardHolder && typeof this.cardHolder.unmount === 'function') {
          this.cardHolder.unmount()
        }
        if (this.expiryDate && typeof this.expiryDate.unmount === 'function') {
          this.expiryDate.unmount()
        }
        if (this.verificationCode && typeof this.verificationCode.unmount === 'function') {
          this.verificationCode.unmount()
        }
      } catch (error) {
        console.error('[MOLLIE] Erro no cleanup:', error)
      }
    },
    async loadSavedCards() {
      try {
        this.loadingSavedCards = true
        
        const response = await HttpApi.get('mollie/saved-cards')
        
        if (response.data.status && response.data.savedCards) {
          this.savedCards = response.data.savedCards
        } else {
          this.savedCards = []
        }
      } catch (error) {
        this.savedCards = []
      } finally {
        this.loadingSavedCards = false
      }
    },
    selectSavedCard(card) {
      this.selectedSavedCard = card
    },
    async payWithSavedCard(card) {
      try {
        this.processing = true
        
        const paymentData = {
          amount: this.amount,
          mandate_id: card.id,
          accept_bonus: true
        }
        
        const response = await HttpApi.post('mollie/create-payment-with-saved-card', paymentData)
        
        if (response.data.status) {
          
          // Verificar status real do pagamento antes de emitir sucesso
          if (response.data.payment_status === 'paid') {
            this.$emit('payment-success', {
              payment_id: response.data.payment_id,
              deposit_id: response.data.deposit_id,
              amount: this.amount
            })
          } else if (response.data.payment_status === 'failed') {
            this.$emit('payment-error', 'Pagamento recusado. Verifique os dados do cartão.')
          } else {
            // Para status 'open' ou 'pending', aguardar webhook
            this.$emit('payment-pending', {
              payment_id: response.data.payment_id,
              deposit_id: response.data.deposit_id,
              amount: this.amount
            })
            
            // Verificar status após 3 segundos
            setTimeout(async () => {
              try {
                const statusCheck = await HttpApi.get(`mollie/check-status?payment_id=${response.data.payment_id}`)
                
                if (statusCheck.data.status === 'paid') {
                  this.$emit('payment-success', {
                    payment_id: response.data.payment_id,
                    deposit_id: response.data.deposit_id,
                    amount: this.amount
                  })
                  
                  // Recarregar a página para atualizar o saldo visualmente
                  setTimeout(() => {
                    window.location.reload()
                  }, 1000)
                } else if (statusCheck.data.status === 'failed') {
                  this.$emit('payment-error', 'Pagamento recusado. Verifique os dados do cartão.')
                }
              } catch (error) {
              }
            }, 3000)
          }
        } else {
          this.$emit('payment-error', response.data.message)
        }
      } catch (error) {
        this.$emit('payment-error', 'Erro ao processar pagamento com cartão salvo')
      } finally {
        this.processing = false
      }
    },
    async deleteSavedCard(card) {
      try {
        if (!confirm('Tem certeza que deseja remover este cartão?')) {
          return
        }

        // Mostrar loading
        this.processing = true
        
        const response = await HttpApi.delete('mollie/delete-saved-card', {
          data: { mandate_id: card.id }
        })

        if (response.data.status) {
          // Sucesso - remover cartão da lista imediatamente
          this.savedCards = this.savedCards.filter(c => c.id !== card.id)
          
          // Limpar seleção se o cartão removido estava selecionado
          if (this.selectedCard?.id === card.id) {
            this.selectedCard = null
          }
          
          // NÃO recarregar lista imediatamente - Mollie pode demorar para atualizar
          // A lista será atualizada na próxima vez que o componente for carregado
          
          // Mostrar mensagem de sucesso
          alert('Cartão removido com sucesso!')
          
        } else {
          console.error('[MOLLIE-DELETE-CARD] Erro:', response.data.message)
          alert('Erro ao remover cartão: ' + (response.data.message || 'Erro desconhecido'))
        }
        
      } catch (error) {
        console.error('[MOLLIE-DELETE-CARD] Erro na requisição:', error)
        
        // Verificar se é erro de resposta da API
        if (error.response && error.response.data) {
          // Verificar se é erro 410 (cartão já removido) - tratar como sucesso
          if (error.response.status === 400 && 
              error.response.data.message && 
              (error.response.data.message.includes('no longer available') || 
               error.response.data.message.includes('já foi removido'))) {
            
            // Tratar como sucesso - remover da lista
            this.savedCards = this.savedCards.filter(c => c.id !== card.id)
            
            // Limpar seleção se necessário
            if (this.selectedCard?.id === card.id) {
              this.selectedCard = null
            }
            
            // NÃO recarregar lista - manter remoção local
            
            alert('Cartão já foi removido anteriormente!')
          } else {
            alert('Erro ao remover cartão: ' + (error.response.data.message || 'Erro do servidor'))
          }
        } else {
          alert('Erro ao remover cartão: Falha na comunicação com o servidor')
        }
      } finally {
        this.processing = false
      }
    },
    formatCurrency(amount) {
      return new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: 'EUR'
      }).format(amount)
    }
  }
}
</script>

<style>
/* Saved Cards Styles */
.saved-cards-section {
  margin-bottom: 1.5rem;
}

.saved-cards-list {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.saved-card-item {
  background: rgba(255, 255, 255, 0.05);
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 8px;
  padding: 1rem;
  cursor: pointer;
  transition: all 0.2s ease;
  display: flex;
  justify-content: space-between;
  align-items: center;
  position: relative;
}

.saved-card-item:hover {
  background: rgba(255, 255, 255, 0.08);
  border-color: rgba(255, 255, 255, 0.2);
}

.saved-card-item.selected {
  border-color: #3b82f6;
  background: rgba(59, 130, 246, 0.1);
}

.card-info {
  flex: 1;
}

.card-number {
  color: white;
  font-size: 1rem;
  font-weight: 500;
  margin-bottom: 0.25rem;
}

.card-holder {
  color: rgba(255, 255, 255, 0.7);
  font-size: 0.875rem;
  margin-bottom: 0.25rem;
}

.card-label {
  color: rgba(255, 255, 255, 0.5);
  font-size: 0.75rem;
}

.card-actions {
  display: flex;
  gap: 0.5rem;
  align-items: center;
}

.btn-use-card {
  background: #2563eb;
  color: white;
  border: none;
  padding: 0.5rem 1rem;
  border-radius: 4px;
  cursor: pointer;
  font-size: 0.875rem;
  transition: background-color 0.2s ease;
}

.btn-use-card:hover:not(:disabled) {
  background: #1d4ed8;
}

.btn-use-card:disabled {
  background: #666;
  cursor: not-allowed;
}

.delete-card-btn {
  position: absolute;
  top: -8px;
  right: -8px;
  background: rgba(231, 76, 60, 0.9);
  color: white;
  border: 2px solid rgba(255, 255, 255, 0.2);
  padding: 0;
  border-radius: 50%;
  cursor: pointer;
  font-size: 0.75rem;
  transition: all 0.2s ease;
  display: flex;
  align-items: center;
  justify-content: center;
  width: 24px;
  height: 24px;
  z-index: 10;
  backdrop-filter: blur(4px);
}

.delete-card-btn:hover {
  background: rgba(192, 57, 43, 0.95);
  transform: scale(1.1);
  box-shadow: 0 2px 8px rgba(231, 76, 60, 0.4);
}

.delete-card-btn i {
  font-size: 0.7rem;
  font-weight: bold;
}

.divider {
  text-align: center;
  margin: 1.5rem 0;
  position: relative;
}

.divider::before {
  content: '';
  position: absolute;
  top: 50%;
  left: 0;
  right: 0;
  height: 1px;
  background: rgba(255, 255, 255, 0.1);
}

.divider span {
  background: #1a1a1a;
  color: rgba(255, 255, 255, 0.5);
  padding: 0 1rem;
  font-size: 0.875rem;
  position: relative;
}

/* Form Styles - Based on example */
.mollie-card-form {
  width: 100%;
  max-width: none;
  margin: 0;
  padding: 0;
  background: transparent;
  border-radius: 12px;
  color: #f9fafb;
  position: relative;
}

#mollie-form {
  width: 100%;
  background: transparent;
  padding: 0;
  margin: 0;
}

/* Form Field */
.m-form-field {
  margin-bottom: 16px;
  width: 100%;
}

/* Input Container */
.m-input {
  position: relative;
  display: flex;
  align-items: center;
  background: transparent;
  border-radius: 8px;
  min-height: 56px;
  transition: all 0.3s ease;
  width: 100%;
}

.m-gradient-border {
  border: 1px solid rgba(255, 255, 255, 0.2);
}

.m-input--dark {
  background: transparent;
  color: #ffffff;
}

.m-input--m {
  min-height: 56px;
  padding: 0 16px;
}

.m-input:focus-within {
  border-color: #00d4ff;
}

/* Icon Container */
.m-icon-container {
  display: flex;
  align-items: center;
  justify-content: center;
  margin-right: 12px;
  flex-shrink: 0;
}

.m-input-prepend {
  margin-right: 12px;
}

.card-icon {
  width: 24px;
  height: 24px;
  opacity: 0.7;
}

.m-icon {
  width: 20px;
  height: 20px;
  color: #ffffff;
  opacity: 0.7;
}

/* Input Content */
.m-input-content {
  flex: 1;
  position: relative;
  display: flex;
  flex-direction: column;
  justify-content: center;
  min-height: 40px;
}



.mollie-component-container {
  background: transparent !important;
  border: none !important;
  padding: 0 !important;
  margin: 0 !important;
  width: 100%;
  position: relative;
  z-index: 2;
}

.card-input {
  width: 100% !important;
  background: transparent !important;
  border: none !important;
  color: #ffffff !important;
  font-size: 16px !important;
  outline: none !important;
  padding: 0 !important;
  margin: 0 !important;
  min-height: 24px;
}

/* Row Layout for Date/CVC */
.CreditCardForm_row_XfUrw {
  display: flex;
  gap: 16px;
}

.CreditCardForm_row_XfUrw .m-form-field {
  flex: 1;
}

/* Dark mode adjustments */
.dark .m-gradient-border {
  border-color: rgba(255, 255, 255, 0.1);
}

.dark .m-input:focus-within {
  border-color: #00d4ff;
}

.dark .m-input-content-label {
  color: rgba(255, 255, 255, 0.6);
}

.dark .m-icon {
  color: #ffffff;
}

.save-label {
  display: flex;
  align-items: center;
}

.checkmark svg {
  opacity: 0;
  color: white;
  transition: opacity 0.2s
}

/* Security Badges */
.security-badges {
  display: flex;
  gap: 8px;
  align-items: center;
  justify-content: flex-end;
  margin-top: 0.5rem;
  margin-bottom: 0.5rem;
}

.visa-verified-badge {
  background-image: url('/assets/images/payments/visa_verified.svg');
  background-size: contain;
  background-repeat: no-repeat;
  background-position: center;
  width: 60px;
  height: 38px;
  opacity: 0.9;
}

.mc-securecode-badge {
  background-image: url('/assets/images/payments/mc_securecode.svg');
  background-size: contain;
  background-repeat: no-repeat;
  background-position: center;
  width: 60px;
  height: 38px;
  opacity: 0.9;
}

.visa-verified-badge:hover,
.mc-securecode-badge:hover {
  opacity: 0.8;
}

/* Estilos para o container .mollie-component gerado pela Mollie */
:global(.mollie-component) {
  border: none !important;
  padding: 0 !important;
  background: transparent !important;
  box-shadow: none !important;
  margin: 0 !important;
}

/* Estilos básicos para inputs Mollie - preservando placeholders nativos */
:global(.mollie-component input) {
  border: none !important;
  outline: none !important;
}

/* Forçar exibição dos placeholders - sobrescrever regras globais do Tailwind */
:global(.mollie-component input::placeholder) {
  color: #9ca3af !important;
  opacity: 1 !important;
  font-size: 16px !important;
}

:global(.dark .mollie-component input::placeholder) {
  color: #9ca3af !important;
  opacity: 1 !important;
}

/* Labels flutuantes para campos sem placeholder nativo */
.floating-label {
  position: absolute;
  left: 0;
  top: 50%;
  transform: translateY(-50%);
  color: #9ca3af;
  font-size: 16px;
  pointer-events: none;
  transition: all 0.2s ease;
  z-index: 1;
  background: transparent;
  padding: 0 4px;
  opacity: 1;
}

/* Label sobe quando há foco */
.floating-label.has-focus {
  top: -8px;
  font-size: 12px;
  color: #6366f1;
  background: #1a1a1a;
}

/* Label fica no topo quando há conteúdo (verde) */
.floating-label.has-content {
  top: -8px;
  font-size: 12px;
  color: #3b82f6;
  background: #1a1a1a;
  opacity: 1;
}

/* Prioridade: conteúdo + foco = verde */
.floating-label.has-content.has-focus {
  color: #3b82f6;
}

/* Ajustar posicionamento do input content para acomodar labels */
.m-input-content {
  position: relative;
  padding-top: 8px;
}

:global(.mollie-component.has-focus) {
  border-color: #6366f1;
  box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
}

/* Mostrar erro apenas com borda vermelha */
:global(.mollie-component.is-invalid) {
  border-color: #e25950;
}

:global(.mollie-component.is-touched.is-invalid) {
  border-color: #e25950;
}

/* Verde apenas quando válido */
:global(.mollie-component.is-valid) {
  border-color: #3b82f6;
}

:global(.mollie-component--is-loading) {
  background-color: transparent !important;
  border-color: rgba(255, 255, 255, 0.2) !important;
}

/* Removido .error-message - não será mais usado */

.form-actions {
  margin-top: 2rem;
}

.btn {
  width: 100%;
  padding: 1rem 1.5rem;
  border: none;
  border-radius: 8px;
  font-weight: 600;
  font-size: 1rem;
  cursor: pointer;
  transition: all 0.2s ease-in-out;
  text-transform: uppercase;
  letter-spacing: 0.025em;
}

.btn-primary {
  background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
  color: white;
  box-shadow: 0 4px 14px 0 rgba(59, 130, 246, 0.39);
}

.btn-primary:hover:not(:disabled) {
  background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
  box-shadow: 0 6px 20px 0 rgba(59, 130, 246, 0.5);
  transform: translateY(-1px);
}

.btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
  transform: none;
  box-shadow: none;
}

/* Estilos para modo escuro */
@media (prefers-color-scheme: dark) {
  .mollie-card-form {
    background: transparent;
    color: #f9fafb;
  }
  
  .form-group label {
    color: #f9fafb;
  }
  
  .form-group label {
    color: #f9fafb;
    font-weight: 700;
  }
  
  .mollie-card-container {
    background-color: transparent;
  }
  
  :global(.dark .mollie-component) {
    background: transparent !important;
    border-color: rgba(255, 255, 255, 0.2) !important;
    color: #f9fafb !important;
  }
  
  :global(.dark .mollie-component.has-focus) {
    border-color: #8b5cf6 !important;
    box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.1) !important;
  }
  
  :global(.dark .mollie-component.is-invalid) {
    border-color: #e25950 !important;
  }
  
  :global(.dark .mollie-component.is-touched.is-invalid) {
    border-color: #e25950 !important;
  }
  
  :global(.dark .mollie-component.is-valid) {
    border-color: #3b82f6 !important;
  }
}
</style>
