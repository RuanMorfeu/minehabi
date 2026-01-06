<template>
  <div class="seletorMinas">
    <select
      @change="escolherQuantidadeDeMinas"
      :value="jogo.quantidadeDeMinas || 3"
      :disabled="jogo.estadojogo == 'iniciou'"
    >
      <option
        v-for="numero in jogo.numeros.filter(n => n < 24)"
        :key="numero"
        :value="numero + 1"
      >
        {{ numero + 1 }}
      </option>
    </select>

    <label>Mines: {{ jogo.quantidadeDeMinas }}</label>
  </div>
</template>

<script setup>
defineProps({
  jogo: {
    type: Object,
    required: true
  }
})

const emit = defineEmits(['escolherQuantidadeDeMinas'])

const escolherQuantidadeDeMinas = (e) => {
  emit('escolherQuantidadeDeMinas', e)
}
</script>

<style scoped>
.seletorMinas {
  position: relative;
  width: 150px;
  height: 100%;
}

.seletorMinas label {
  left: 40px;
  top: 50%;
  transform: translateY(-50%);
  color: #fff;
  font-size: 12px;
  position: absolute;
  background-color: #0464a4;
  padding: 0 10px;
  pointer-events: none;
  white-space: nowrap;
  border-radius: 5px;
}

.seletorMinas select {
  width: 100%;
  height: 100%;
  background-color: #0464a4;
  border-radius: 20px;
  color: transparent; /* Esconde o texto do select para mostrar apenas o label */
  text-align: center;
  justify-content: center;
  font-size: 12px;
  position: relative;
  border: none;
  appearance: none;
  -webkit-appearance: none;
  cursor: pointer;
}

.seletorMinas select option {
  color: #000;
}

.seletorMinas select:focus {
  outline: none;
}
</style>
