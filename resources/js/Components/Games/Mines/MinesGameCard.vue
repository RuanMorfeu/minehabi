<template>
    <div class="flex text-gray-700 w-full h-auto cursor-pointer relative"
         @mouseover="showGameInfo = true"
         @mouseleave="showGameInfo = false"
         @click.prevent="$emit('click')">
        <div class="relative">
            <img :src="getCover(cover)" alt="Mines" class="rounded-lg lg:w-auto" :style="{ opacity: showGameInfo ? '0.5' : '1' }">

            <div v-if="showGameInfo" class="absolute inset-0 flex justify-center items-center rounded-lg backdrop-blur-sm px-3 py-2">
                <div class="text-center text-white max-w-[90%]">
                    <span class="block truncate text-[12px]">Mines</span>
                    <div class="flex flex-col">
                        <button type="button" class="ui-button-modal mt-2" @click.prevent.stop="$emit('click')">
                            <i class="fas fa-play-circle mr-1"></i> Jogar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue';

defineProps({
    cover: { type: String, required: true }
});

defineEmits(['click']);

const showGameInfo = ref(false);

function getCover(slug) {
    if (!slug) {
        return '';
    }
    
    if (slug.startsWith('http')) {
        return slug;
    }
    return '/storage/' + slug;
}
</script>
