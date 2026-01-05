<template>
  <div class="bet-container">
    <ValoresPreDefinidos
      :visibilidadeValoresPredefinidos="visibilidadeValoresPredefinidos"
      :jogo="jogo"
      @setValorBet="setValorBet"
    />

    <div class="bet-input-area">
      <label>Aposta EUR</label>
      <div class="input-wrapper">
        <input
          type="number"
          @input="onInputChange"
          :value="jogo.valorBet.toFixed(2)"
          :readonly="jogo.estadojogo == 'iniciou'"
          min="1"
          max="100"
        />
      </div>
    </div>

    <div class="bet-controls">
      <button 
        class="btn-circle"
        @click="() => alterarValor('diminuir')"
        :disabled="jogo.valorBet <= 1.00 || jogo.estadojogo == 'iniciou'"
      >
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
          <line x1="5" y1="12" x2="19" y2="12"></line>
        </svg>
      </button>

      <button 
        class="btn-circle btn-options"
        @click="() => setVisibilidadeValoresPredefinidos(!visibilidadeValoresPredefinidos)"
        :disabled="jogo.estadojogo == 'iniciou'"
      >
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M12 2a3 3 0 0 0-3 3v14a3 3 0 0 0 6 0V5a3 3 0 0 0-3-3Z"/> <!-- Stack visual simplificado -->
          <ellipse cx="12" cy="5" rx="3" ry="1.5" />
          <path d="M9 9.5c0 .83 1.34 1.5 3 1.5s3-.67 3-1.5" />
          <path d="M9 14c0 .83 1.34 1.5 3 1.5s3-.67 3-1.5" />
        </svg>
      </button>

      <button 
        class="btn-circle"
        @click="() => alterarValor('aumentar')"
        :disabled="jogo.valorBet >= 100 || jogo.estadojogo == 'iniciou'"
      >
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
          <line x1="12" y1="5" x2="12" y2="19"></line>
          <line x1="5" y1="12" x2="19" y2="12"></line>
        </svg>
      </button>
    </div>
  </div>
</template>

<script setup>
import ValoresPreDefinidos from './ValoresPreDefinidos.vue'

defineProps({
  jogo: {
    type: Object,
    required: true
  },
  visibilidadeValoresPredefinidos: {
    type: Boolean,
    required: true
  }
})

const emit = defineEmits(['setValorBet', 'alterarValor', 'setVisibilidadeValoresPredefinidos'])

const setValorBet = (value) => {
  emit('setValorBet', value)
}

const alterarValor = (action) => {
  emit('alterarValor', action)
}

const setVisibilidadeValoresPredefinidos = (value) => {
  emit('setVisibilidadeValoresPredefinidos', value)
}

const onInputChange = (e) => {
  if(e.target.value.length <= 5 && e.target.value >= 1.00) {
    setValorBet(parseFloat(e.target.value))
  }
}
</script>

<style scoped>
.bet-container {
  display: flex;
  align-items: center;
  justify-content: space-between;
  width: 100%;
  max-width: 340px;
  background-color: #0c4c84; /* Azul do fundo da barra */
  height: 60px;
  border-radius: 30px;
  padding: 0 15px;
  border: 1px solid rgba(255, 255, 255, 0.1);
  box-shadow: inset 0 0 10px rgba(0, 0, 0, 0.2);
  position: relative;
}

.bet-input-area {
  display: flex;
  flex-direction: column;
  justify-content: center;
  flex: 1;
  margin-right: 10px;
}

.bet-input-area label {
  color: #fff;
  font-size: 11px;
  margin-bottom: 2px;
  text-align: center;
  width: 100%;
  font-weight: 500;
}

.input-wrapper {
  background-color: #062e52; /* Fundo escuro do input */
  border-radius: 15px;
  padding: 2px;
  width: 100%;
}

.bet-input-area input {
  width: 100%;
  height: 24px;
  background: transparent;
  border: none;
  text-align: center;
  color: #fff;
  font-size: 16px;
  font-weight: bold;
  outline: none;
}

.bet-controls {
  display: flex;
  align-items: center;
  gap: 8px;
}

.btn-circle {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  background-color: #0484cc; /* Azul mais claro dos botões */
  border: 1px solid rgba(255, 255, 255, 0.2);
  display: flex;
  align-items: center;
  justify-content: center;
  color: #fff;
  cursor: pointer;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
  transition: all 0.1s;
  padding: 0;
}

.btn-circle:active:not(:disabled) {
  transform: translateY(1px);
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);
}

.btn-circle:disabled {
  opacity: 0.6;
  cursor: not-allowed;
  background-color: #034e7a;
}

.btn-options svg {
  stroke-width: 1.5;
}

@media screen and (max-width: 1055px) {
  .bet-container {
    width: 100%;
  }
}
</style>
