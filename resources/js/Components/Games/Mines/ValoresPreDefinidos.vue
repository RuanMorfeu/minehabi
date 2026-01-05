<template>
  <div v-if="visibilidadeValoresPredefinidos" class="valoresPredefinidos">
    <div class="margemValoresPredefinidos">
      <p>Bet EUR</p>
      <button
        v-for="valor in jogo.intervaloDeValores"
        :key="valor"
        :class="['btnValoresPredefinidos', valor == jogo.valorBet && 'selecionado']"
        @click="() => setValorBet(valor)"
      >
        {{ valor.toFixed(2) }}
      </button>
    </div>
  </div>
</template>

<script setup>
defineProps({
  visibilidadeValoresPredefinidos: {
    type: Boolean,
    required: true
  },
  jogo: {
    type: Object,
    required: true
  }
})

const emit = defineEmits(['setValorBet'])

const setValorBet = (value) => {
  emit('setValorBet', value)
}
</script>

<style scoped>
.valoresPredefinidos {
  position: absolute;
  width: 300px;
  background: #032E49;
  bottom: 100%;
  left: 50%;
  transform: translateX(-50%);
  margin-bottom: 10px;
  display: flex;
  justify-content: center;
  border-radius: 10px;
  z-index: 20;
  padding: 10px 0;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.3);
  border: 1px solid rgba(255, 255, 255, 0.1);
}

.margemValoresPredefinidos {
  width: 275px;
  display: flex;
  flex-wrap: wrap;
  justify-content: space-between;
}

.valoresPredefinidos p {
  width: 100%;
  text-align: center;
  color: #fff;
  font-size: 14px;
  margin: 0 0 10px 0;
  font-weight: bold;
}

.btnValoresPredefinidos {
  width: 133px;
  height: 30px;
  font-size: 13px;
  background-color: #094164;
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 8px;
  color: #fff;
  margin-bottom: 8px;
  cursor: pointer;
  transition: all 0.2s;
}

.btnValoresPredefinidos:hover {
  background-color: #0c527c;
}

.selecionado {
  background-color: #0267A5;
  border-color: #4CAF50;
  box-shadow: 0 0 5px rgba(76, 175, 80, 0.5);
}

@media screen and (max-width: 330px) {
  .valoresPredefinidos {
    width: 100%;
  }
}
</style>
