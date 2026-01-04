<template>
    <button
        class="group relative inline-block cursor-pointer rounded bg-primary-600 p-px leading-6 text-white no-underline shadow-2xl shadow-primary-700"
    >
        <span class="absolute inset-0 overflow-hidden rounded"
            ><span
                class="absolute inset-0 rounded bg-[image:radial-gradient(75%_100%_at_50%_0%,rgba(163,230,55,0.6)_0%,rgba(56,189,248,0)_75%)] opacity-0 transition-opacity duration-500 group-hover:opacity-100"
            ></span
        ></span>
        <div
            class="relative z-10 flex font-oswald text-xs sm:text-sm items-center space-x-2 uppercase rounded bg-primary-950/50 px-4 py-1.5 ring-1 ring-white/10"
        >
            <span><slot /></span>
        </div>
        <span
            class="absolute -bottom-0 left-[1.125rem] h-px w-[calc(100%-2.25rem)] bg-gradient-to-r from-primary-400/0 via-primary-100 to-primary-400/0 transition-opacity duration-500 group-hover:opacity-40"
        ></span>
    </button>
</template>
<script setup lang="ts">
const props = defineProps({
    link: {
        type: String,
        required: false,
        default: "home",
    },
});
</script>
