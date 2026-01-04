<script setup lang="ts">
import { useSideBarStore } from "@/Store/sidebar";
import { Link } from "@inertiajs/vue3";
import SidebarMobile from "@/Components/SidebarMobile.vue";
import Footer from "@/Components/Footer.vue";

const sidebarStore = useSideBarStore();
defineProps({
    menus: Object
})

</script>

<template>

        <SidebarMobile :menus="menus" />

    <Footer />


    <div
        class="fixed block md:hidden bottom-0 left-0 z-20 w-full h-14 bg-zinc-950 backdrop-blur"
    >

        <div class="grid h-full max-w-lg grid-cols-4 mx-auto">
            <button
                type="button"
                class="inline-flex flex-col items-center justify-center px-5 hover:bg-blue-50 dark:hover:bg-blue-950/40 group"
                @click="sidebarStore.toggleSidebarOpen"
            >
                <img src="/assets/icons/menu/menu.svg"
                     class="w-5 text-gray-500 dark:text-gray-400 group-hover:text-blue-600 dark:group-hover:text-blue-500"
                     alt="menu">
                <span
                    class="text-xs pt-1 text-gray-500 dark:text-gray-400 group-hover:text-blue-600 dark:group-hover:text-blue-500"
                >{{ $t("menu") }}</span
                >
            </button>
            <Link
                as="button"
                :href="route('casino.index')"
                class="inline-flex flex-col items-center justify-center px-5 hover:bg-blue-50 dark:hover:bg-blue-950/40 group"
            >
                <img src="/assets/icons/casino.svg"
                     class="w-4 h-4 text-gray-500 dark:text-gray-400 group-hover:text-blue-600 dark:group-hover:text-blue-500"
                     alt="menu">
                <span
                    class="text-xs pt-1 text-gray-500 dark:text-gray-400 group-hover:text-blue-600 dark:group-hover:text-blue-500"
                >{{ $t("casino") }}</span
                >
            </Link>
            <Link
                as="button"
                :href="route('sports.index')"
                class="inline-flex flex-col items-center justify-center px-5 hover:bg-blue-50 dark:hover:bg-blue-950/40 group"
            >
                <img src="/assets/icons/carteira.svg"
                     class="w-4 h-4 text-gray-500 dark:text-gray-400 group-hover:text-blue-600 dark:group-hover:text-blue-500"
                     alt="menu">
                <span
                    class="text-xs pt-1 text-gray-500 dark:text-gray-400 group-hover:text-blue-600 dark:group-hover:text-blue-500"
                >{{ $t("wallet") }}</span
                >
            </Link>
            <Link
                as="button"
                :href="route('wallet.transactions')"
                class="inline-flex flex-col items-center justify-center px-5 hover:bg-blue-50 dark:hover:bg-blue-950/40 group"
            >
                <img src="/assets/icons/roleta.svg"
                     class="w-4 h-4 text-gray-500 dark:text-gray-400 group-hover:text-blue-600 dark:group-hover:text-blue-500"
                     alt="menu">
                <span
                    class="text-xs pt-1 text-gray-500 dark:text-gray-400 group-hover:text-blue-600 dark:group-hover:text-blue-500"
                >{{ $t("live") }}</span
                >
            </Link>
        </div>
    </div>
</template>

<style scoped></style>
