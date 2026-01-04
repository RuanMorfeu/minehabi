<script setup>
import Flicking from "@egjs/vue3-flicking";
import "@egjs/vue3-flicking/dist/flicking.css";
import "@egjs/vue3-flicking/dist/flicking-inline.css";

import RowTitle from "@/Components/Web/RowTitle.vue";
import ThumbPlay from "@/Components/ThumbPlay.vue";




defineProps({
    games: {
        type: Object,
        required: true
    }
});
</script>
<template>
    <div>
        <RowTitle>{{ $t("section.casino.featured") }}</RowTitle>
       <div class="mb-6 h-90 w-full">
           <Flicking
               :options="{  circular: true, resizeOnContentsReady: true, nested: true, preventEventsBeforeInit: true }"
               :viewportTag="'div'"
               :cameraTag="'div'"
               :cameraClass="''"
               class="flex gap-2 h-90"
           >
               <ThumbPlay v-for="(slide, index) in games" :key="index" :data="slide" class="max-w-[176px] mr-4">

               </ThumbPlay>



           </Flicking>
       </div>
    </div>
</template>
<style scoped>
.flicking-viewport {
    transition: height 500ms;
}
</style>
