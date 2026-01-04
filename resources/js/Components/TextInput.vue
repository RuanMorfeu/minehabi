<script setup type="ts">
import { onMounted, ref } from "vue";

const model = defineModel({
    type: String,
    required: true,
});

const input = ref(null);

onMounted(() => {
    if (input.value.hasAttribute("autofocus")) {
        input.value.focus();
    }
});

defineExpose({ focus: () => input.value.focus() });
</script>

<template>
    <input
        class="w-full p-3 bg-gray-800 rounded-md text-white placeholder-gray-400 focus:outline-none"
        v-model="model"
        ref="input"
    />
</template>
