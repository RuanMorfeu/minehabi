<template>
    <div class="py-2">
        <div class="flex gap-2 items-center mb-4">
            <div class="bg-neutral-800 rounded p-2">
                <img class="h-4" src="/assets/icons/king.svg" alt="" />
            </div>
            <div class="h-6 font-oswald uppercase text-white">
                {{ $t("section.categories") }}
            </div>
        </div>
        <div class="w-full mt-2">
            <MenuCategories :categories="categories" />
        </div>
    </div>
</template>
<script setup>
import MenuCategories from "./MenuCategories.vue";
import { usePage } from "@inertiajs/vue3";

const page = usePage();

const categories = page.props.categories;
</script>
