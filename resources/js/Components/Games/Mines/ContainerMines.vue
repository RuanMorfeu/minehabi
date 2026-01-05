<template>
  <div class="containerMines">
    <slot></slot>
  </div>
</template>

<style scoped>
.containerMines {
  width: 100%;
  max-width: 415px;
  display: flex;
  flex-direction: column;
  justify-content: center;
  flex: 1; /* Ocupa o espaço vertical disponível */
  padding: 10px 0;
}

@media screen and (max-width: 500px) {
  .containerMines {
    width: 90%;
    /* No mobile, removemos altura fixa e deixamos o flex container pai (Mines.vue) gerenciar o espaço */
  }
}
</style>
