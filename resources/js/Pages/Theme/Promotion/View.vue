<template>
    <WebLayout>
        <WebContent>
            <div class="mb-2">
                <Link
                    :href="route('promo.promotions')"
                    class="uppercase flex items-center gap-2 font-oswald text-sm hover:text-primary-500"
                >
                    <ArrowLeftIcon class="h-4" />
                    Voltar
                </Link>
            </div>
            <div>
                <div class="w-full">
                    <img
                        class="w-full object-cover max-h-[300px]"
                        :src="'https://cdn.xpdom.com/' +block.image"
                        alt=""
                    />
                </div>
                <div class="py-3">
                    <h1
                        class="text-2xl font-bold font-oswald uppercase py-2 text-primary-500"
                    >
                        {{ block.title }}
                    </h1>
                    <p>{{ block.subtitle }}</p>
                    <div class="mt-2" v-html="block.block"></div>
                </div>
            </div>
        </WebContent>
    </WebLayout>
</template>
<script setup>
import WebContent from "@/Pages/Theme/Components/WebContent.vue";
import WebLayout from "@/Pages/Theme/Layout/WebLayout.vue";
import { Link } from "@inertiajs/vue3";
import { ArrowLeftIcon } from "@heroicons/vue/16/solid/index.js";

const props = defineProps({
    block: {
        type: Object,
        required: false,
    },
});
</script>
