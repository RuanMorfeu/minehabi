<template>
  <div :class="['saldo', paralelo && 'paralelo']" @click="refreshSaldo" title="Clique para atualizar o saldo">
    <p v-if="jogo.estadojogo == 'finalizou' && !jogo.indiceGameOver" class="lucro">
      +{{ Number(jogo.valorCashOut).toFixed(2) }} EUR
    </p>
    <p class="saldoMines">{{ (wallet && wallet.balance != null) ? Number(wallet.balance).toFixed(2) : Number(jogo.saldo).toFixed(2) }}</p>
    <p class="moedaMines">EUR</p>
  </div>
</template>

<script setup>
const emit = defineEmits(['refresh'])

defineProps({
  jogo: {
    type: Object,
    required: true
  },
  wallet: {
    type: Object,
    default: null
  },
  paralelo: {
    type: Boolean,
    default: false
  }
})

const refreshSaldo = () => {
  emit('refresh')
}
</script>

<style scoped>
.saldo {
  border-radius: 10px;
  width: 100%;
  height: 30px;
  display: flex;
  justify-content: right;
  align-items: center;
  background-color: #0c4c84;
  cursor: pointer;
  transition: background-color 0.2s;
}

.saldo:hover {
  background-color: #0d5d9f;
}

.paralelo {
  display: none;
}

.saldoMines, .moedaMines, .lucro {
  margin: 0;
  font-size: 12px;
  color: #fff;
}

.saldoMines {
  margin: 0 3px 0 7px;
}

.moedaMines {
  margin-right: 10px;
  color: #FFFFFF80;
}

.lucro {
  padding: 4px 8px;
  border-radius: 15px;
  background-color: #15902D;
  margin-right: 0;
}

@media screen and (max-width: 1055px) {
  .saldo {
    display: none;
  }
  
  .saldo.paralelo {
    display: flex;
    background-image: linear-gradient(to right, #0484cc, #0454dc);
    border-radius: 0;
    margin-bottom: 0;
  }
}
</style>
