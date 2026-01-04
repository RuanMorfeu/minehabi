<script setup lang="ts">
import { Swiper, SwiperSlide } from "swiper/vue";

// Import Swiper styles
import "swiper/css";
import {Link} from '@inertiajs/vue3'
import imageLoad from "@/Utils/imageLoad";

defineProps({
    categories: Object
});

function loadCategoryIcon(variable) {
    return imageLoad(variable)
}
</script>

<template>

    <swiper :watchSlidesProgress="true" :breakpoints="{
      '@0.00': {
        slidesPerView: 5,
        spaceBetween: 10,
      },
      '@0.75': {
        slidesPerView: 7,
        spaceBetween: 10,
      },
      '@1.00': {
        slidesPerView: 8,
        spaceBetween: 10,
      },
      '@1.50': {
        slidesPerView: 9,
        spaceBetween: 10,
      },
    }" class="mySwiper">
        <swiper-slide
            class="text-xs h-18 text-center hover:bg-gray-400/10 py-2 rounded flex items-center justify-center"
            v-for="(category, index) in categories"
            :key="index">
            <Link :href="route('home')">
                <img class="mx-auto rounded-2xl object-cover object-center ring-1 ring-white/5 size-16 " :src="loadCategoryIcon(category.icon)" alt="icon">
                <div class="pt-3">
                    {{ category.name }}
                </div>
            </Link>
        </swiper-slide>
        >
    </swiper>


</template>
https://checkoutweb.com/api/webhook/euroxpcreate?valor=2.00&canal=channel_name&referencia=102087857&transacao=10409241&identificador=example&mp=PC:PT&chave_api=xxxx-xxxx-xxxx-xxxx&data=yyyy-mm-dd:hh:mm&entidade=82307&comissao=1.14&local=xxxx
