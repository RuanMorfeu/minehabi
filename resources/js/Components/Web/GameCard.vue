<script setup>
import { Link } from "@inertiajs/vue3";

const props = defineProps({
    game: String,
    thumbnail: String,
    slug: String,
});
</script>
<template>
    <Link class="" :href="route('casino.play', slug)">
        <img
            class="object-cover w-auto hover:animate-pulse cursor-pointer rounded-lg"
            :src="thumbnail"
            :alt="game"
        />
        <div class="py-2 px-2 text-xs uppercase">
            {{ game }}
        </div>
    </Link>
</template>
