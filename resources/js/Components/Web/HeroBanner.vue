<template>
    <div>
        <swiper :navigation="true" :modules="modules" :autoplay="true" class="mySwiper">
            <swiper-slide v-for="(banner, index) in banners" :key="index">
                <img class="w-full rounded" :src="banner.src" alt="">
            </swiper-slide>
        </swiper>
    </div>
</template>

<script setup>
import { Swiper, SwiperSlide } from "swiper/vue";
import { Navigation } from 'swiper/modules';
// Import Swiper styles
import "swiper/css";
import 'swiper/css/navigation';
import {ref} from "vue";

const modules = ref([
    Navigation
])
defineProps({
    banners: Object
});
</script>
