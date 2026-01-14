<template>
  <div class="container-chicken">
    <slot></slot>
  </div>
</template>

<style scoped>
.container-chicken {
  display: flex;
  justify-content: center;
  align-items: center;
  flex-direction: column;
  width: 100%;
  max-width: 800px;
  position: relative;
  margin: 0 auto;
}
</style>
