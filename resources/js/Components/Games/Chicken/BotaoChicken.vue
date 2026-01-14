<template>
  <button
    type="button"
    @click="handleClick"
    :class="[
      'botaoJogo',
      ['iniciou'].includes(props.jogo.estadojogo) && 'cashOut',
      (props.jogo.loading) ? 'desativado' : ''
    ]"
    :disabled="isDisabled"
  >
    <template v-if="props.jogo.loading">
      <div class="conteudo-botao">
        <span class="loader"></span>
      </div>
    </template>
    <template v-else-if="props.jogo.estadojogo == 'pendente' || props.jogo.estadojogo == 'finalizou'">
      <div class="conteudo-botao">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="play-icon">
          <path d="M5 3L19 12L5 21V3Z" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
        <span class="texto-botao">JOGAR</span>
      </div>
    </template>
    <template v-else>
      <div class="conteudo-botao cashout-content">
        <p class="cashout-label">CASH OUT</p>
        <p class="cashout-value">{{ props.jogo.valorCashOut.toFixed(2) }} EUR</p>
      </div>
    </template>
  </button>
</template>

<script setup>
import { computed } from 'vue'
import { useChicken } from '@/composables/useChicken.js'

const props = defineProps({
  jogo: {
    type: Object,
    required: true
  }
})

const emit = defineEmits(['iniciarPartida', 'darCashOut'])

const { wallet } = useChicken()

const iniciarPartida = () => {
  emit('iniciarPartida')
}

const darCashOut = () => {
  emit('darCashOut')
}

const handleClick = () => {
  if (props.jogo.loading) return

  if (props.jogo.estadojogo == "pendente" || props.jogo.estadojogo == "finalizou") {
    iniciarPartida()
  } else if (props.jogo.estadojogo == "iniciou") {
    darCashOut()
  }
}

const isDisabled = computed(() => {
  if (props.jogo.loading) return true
  
  // Usa o saldo total do wallet se disponível, senão usa o saldo do jogo
  let saldoAtual = 0
  
  if (wallet && wallet.total_balance != null) {
    saldoAtual = wallet.total_balance
  } else if (wallet && wallet.balance != null) {
    saldoAtual = wallet.balance
  } else {
    saldoAtual = props.jogo.saldo
  }
  
  // Se estiver pendente, valida saldo
  if (props.jogo.estadojogo == "pendente" || props.jogo.estadojogo == "finalizou") {
      return props.jogo.valorBet > saldoAtual
  }
  
  return false
})
</script>

<style scoped>
.botaoJogo {
  width: 100%;
  max-width: 340px;
  flex: 1;
  height: 50px;
  background: linear-gradient(180deg, #4CAF50 0%, #45a049 100%);
  background-color: #4CAF50;
  border-radius: 25px;
  border: none;
  cursor: pointer;
  position: relative;
  box-shadow: 0 4px 0 #2E7D32;
  transition: all 0.1s;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 0 20px;
}

.botaoJogo:active:not(:disabled) {
  transform: translateY(2px);
  box-shadow: 0 2px 0 #2E7D32;
}

.conteudo-botao {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 15px;
  width: 100%;
}

.play-icon {
  width: 20px;
  height: 20px;
}

.texto-botao {
  color: #fff;
  font-size: 16px;
  font-weight: bold;
  letter-spacing: 0.5px;
}

/* Estilos de Cash Out */
.botaoJogo.cashOut {
  background: linear-gradient(180deg, #FF9800 0%, #F57C00 100%);
  background-color: #FF9800;
  box-shadow: 0 4px 0 #E65100;
}

.botaoJogo.cashOut:active:not(:disabled) {
  box-shadow: 0 2px 0 #E65100;
}

.cashout-content {
  flex-direction: column;
  gap: 2px;
}

.cashout-label {
  font-size: 11px;
  font-weight: bold;
  color: #fff;
  margin: 0;
  line-height: 1;
}

.cashout-value {
  font-size: 15px;
  font-weight: 800;
  color: #fff;
  background-color: rgba(0, 0, 0, 0.2);
  padding: 2px 8px;
  border-radius: 10px;
  margin: 0;
}

/* Estado Desativado */
.botaoJogo:disabled, .botaoJogo.desativado {
  background: #555;
  box-shadow: 0 4px 0 #333;
  cursor: not-allowed;
  filter: brightness(0.8);
}

.botaoJogo:disabled:active, .botaoJogo.desativado:active {
  transform: none;
  box-shadow: 0 4px 0 #333;
}

/* Loader simples */
.loader {
  border: 3px solid #f3f3f3;
  border-radius: 50%;
  border-top: 3px solid #3498db;
  width: 20px;
  height: 20px;
  -webkit-animation: spin 1s linear infinite; /* Safari */
  animation: spin 1s linear infinite;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

@media screen and (max-width: 1055px) {
  .botaoJogo {
    width: 100%;
  }
}
</style>
