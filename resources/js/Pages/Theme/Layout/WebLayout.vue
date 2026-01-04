<template>
    <div class="w-full min-h-screen">
        <MegaTopMenu />

        <div class="flex max-w-full gap-2">
            <div
                class="min-h-screen w-2/12 hidden md:block border-r border-zinc-800"
            >
                <div class="px-4">
                    <div v-show="false" class="mb-2">
                        <Link :href="route('ref.earn')">
                            <img
                                class="object-contain w-full mx-auto"
                                src="https://cdn.dei.bet/indique_e_ganhe_v2.webp"
                                alt=""
                            />
                        </Link>
                    </div>
                    <ActionPrimary :href="route('ref.earn')" class="w-full">
                        {{ $t("sidebar.action.referral_promo") }}
                    </ActionPrimary>
                </div>
                <div class="py-2">
                    <ul>
                        <li
                            class="px-4 py-2"
                            v-for="(menu, index) in menus"
                            :key="index"
                        >
                            <Link
                                class="uppercase flex items-center gap-2 font-oswald"
                                :href="route(menu.route)"
                            >
                                {{ $t(menu.name) }}
                            </Link>
                            <ul class="mt-2 pt-2">
                                <li
                                    class="py-1"
                                    v-for="(sub, index) in menu?.menus"
                                    :key="index"
                                >
                                    <Link
                                        class="flex items-center gap-2 text-sm"
                                        :href="
                                            route(sub.uri.name, sub.uri.param)
                                        "
                                    >
                                        <img
                                            class="size-6 object-cover object-center rounded"
                                            :src="loadIconMenu(sub.icon)"
                                            alt="Menu"
                                        />
                                        {{ sub.name }}
                                    </Link>
                                </li>
                            </ul>
                        </li>
                    </ul>
                </div>
            </div>
            <div class="px-4 w-full md:w-10/12 mt-2">
                <slot />
            </div>
        </div>
    </div>

    <NavigationFooter :menus="menus" />
</template>
<script setup lang="ts">
import MegaTopMenu from "@/Components/MegaTopMenu.vue";

import { Link, usePage } from "@inertiajs/vue3";

import ActionPrimary from "@/Components/Web/ActionPrimary.vue";
import CloudFlareService from "@/Services/CloudFlareService.js";

import NavigationFooter from "@/Components/Nav/NavigationFooter.vue";
import imageLoad from "@/Utils/imageLoad";
import { route } from "ziggy-js";

const page = usePage();
const menus = page.props.menus;

CloudFlareService.then((response) => {
    console.log(response);
});

function loadIconMenu(variable) {
    return imageLoad(variable);
}
</script>
<style>
.ps {
    position: relative;
    max-height: 100vh;
}
</style>
