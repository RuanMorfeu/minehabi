<template>
    <div class="card shadow-xl">
        <div class="card-body">
            <h2 class="card-title">{{ title }}</h2>
            <p>{{ subtitle }}</p>
            <div>
                <slot />
            </div>
        </div>
    </div>
</template>
<script setup>
const props = defineProps({
    title: {
        type: String,
        default: "",
        required: false,
    },
    subtitle: {
        type: String,
        default: "",
        required: false,
    },
});
</script>
