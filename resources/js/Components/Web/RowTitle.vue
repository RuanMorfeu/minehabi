<script setup lang="ts">

</script>

<template>
    <div class="flex gap-2 items-center justify-between h-14">
        <div class="flex gap-2 items-center mb-4">
            <div class="bg-neutral-800 rounded p-2">
                <img class="h-4" src="/assets/icons/money-bag.webp" alt="" />
            </div>
            <div class="h-6 font-oswald uppercase text-white">
                <slot />
            </div>
        </div>
    </div>
</template>

<style scoped>

</style>
