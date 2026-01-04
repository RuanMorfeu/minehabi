<template>
    <Link
        :href="route(link)"
        class="group relative inline-block cursor-pointer rounded bg-primary-600 p-px leading-6 text-white no-underline shadow-2xl shadow-primary-700"
    >
        <span class="absolute inset-0 overflow-hidden rounded">
            <span
                class="absolute inset-0 rounded bg-[image:radial-gradient(75%_100%_at_50%_0%,rgba(163,230,55,0.6)_0%,rgba(56,189,248,0)_75%)] opacity-0 transition-opacity duration-500 group-hover:opacity-100"
            ></span
            ></span>
        <div
            class="relative z-10 flex font-oswald justify-center text-xs sm:text-sm items-center space-x-2 uppercase rounded bg-primary-950/50 px-4 py-1.5 ring-1 ring-white/10"
        >
            <span><slot /></span>
        </div>
        <span
            class="absolute -bottom-0 left-[1.125rem] h-px w-[calc(100%-2.25rem)] bg-gradient-to-r from-primary-400/0 via-primary-100 to-primary-400/0 transition-opacity duration-500 group-hover:opacity-40"
        ></span>
    </Link>
</template>
<script setup lang="ts">
import { Link } from "@inertiajs/vue3";
import { route } from "ziggy-js";

const props = defineProps({
    link: {
        type: String,
        required: false
    }
});
</script>
<style scoped>
.teste:before {
    background-color: #fff3;
    bottom: 0;
    content: "";
    position: absolute;
    top: 0;
    transform: skew(-30deg);
    width: 30px;
    will-change: left;
    z-index: 1;
    animation: flare 5s infinite ease;
    animation-duration: 5s;
    animation-timing-function: ease;
    animation-delay: 0s;
    animation-iteration-count: infinite;
    animation-direction: normal;
    animation-fill-mode: none;
    animation-play-state: running;
    animation-name: flare;
    animation-timeline: auto;
    animation-range-start: normal;
    animation-range-end: normal;
}
</style>
