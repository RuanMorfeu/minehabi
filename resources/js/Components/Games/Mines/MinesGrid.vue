<template>
  <div class="jogo">
    <div 
      v-for="numero in jogo.numeros"
      :key="numero"
      class="cardGiratorio"
    >
      <div :class="['cardGiratorioConteudo', (jogo.girar && !jogo.acertos.includes(numero) && numero != jogo.indiceGameOver) && 'girar']">
        <div class="cardGiratorioConteudoFrente">
          <img
            @click="() => jogo.estadojogo == 'iniciou' && clicarNoCard(numero)"
            :src="getImageSrc(numero)"
            class="card"
            :alt="`Card ${numero}`"
          />
        </div>

        <div class="cardGiratorioConteudoTraseira">
          <img
            :src="getBackImageSrc(numero)"
            class="card"
            :alt="`Card back ${numero}`"
          />
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  jogo: {
    type: Object,
    required: true
  }
})

const emit = defineEmits(['clicarNoCard'])

const clicarNoCard = (numero) => {
  emit('clicarNoCard', numero)
}

const getImageSrc = (numero) => {
  const baseUrl = '/assets/images/cassino/mines/'
  if (props.jogo.estadojogo == "pendente") return baseUrl + 'card.png'
  if (props.jogo.indiceGameOver == numero) return baseUrl + 'cardBombaExplodida.png'
  if (props.jogo.acertos.includes(numero)) return baseUrl + 'cardEstrelaAmarela.png'
  if (props.jogo.estadojogo == "iniciou") return baseUrl + 'cardEscuro.png'
  return baseUrl + 'card.png'
}

const getBackImageSrc = (numero) => {
  const baseUrl = '/assets/images/cassino/mines/'
  if (props.jogo.estadojogo == "pendente") return baseUrl + 'card.png'
  if (props.jogo.estadojogo == "finalizou" && !props.jogo.indicesMinas.includes(numero) && !props.jogo.acertos.includes(numero)) return baseUrl + 'cardEstrela.png'
  if (props.jogo.estadojogo == "finalizou" && props.jogo.indicesMinas.includes(numero)) return baseUrl + 'cardBomba.png'
  return baseUrl + 'card.png'
}
</script>

<style scoped>
.jogo {
  width: 100%;
  display: flex;
  justify-content: left;
  flex-wrap: wrap;
}

.cardGiratorio {
  width: 18.7%;
  margin: 8% 0 8% 1.5%;
  height: auto;
}

.cardGiratorio:nth-child(5n + 1) {
  margin-left: 0;
}

.cardGiratorioConteudo {
  width: 100%;
  height: 100%;
  position: relative;
  text-align: center;
  transition: transform 1.2s;
  transform-style: preserve-3d;
}

.cardGiratorioConteudo.girar {
  transform: rotateY(180deg);
}

.cardGiratorioConteudoFrente,
.cardGiratorioConteudoTraseira {
  position: absolute;
  width: 100%;
  height: 100%;
  -webkit-backface-visibility: hidden;
  backface-visibility: hidden;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-direction: column;
}

.cardGiratorioConteudoTraseira {
  transform: rotateY(180deg);
}

.card {
  width: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: transform 0.2s;
}

.card:hover {
  transform: scale(1.05);
}
</style>
