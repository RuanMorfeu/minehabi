<template>
    <div class="min-h-screen w-full bg-gray-900 text-gray-50">
        <MegaTopMenu :settings="settings" />
        <div class="mt-2 px-4 container mx-auto">
            <main class="py-10 lg:pl-72">
                <div class="px-4 sm:px-6 lg:px-8">
                    <slot />
                </div>
            </main>
            <Footer />
        </div>
    </div>
</template>
<script setup>
import Footer from "@/Components/Footer.vue";
import MegaTopMenu from "@/Components/MegaTopMenu.vue";
import { usePage } from "@inertiajs/vue3";

const page = usePage();
const settings = page.props.settings;
import BigMenu from "@/Components/Web/BigMenu.vue";
import Sidebar from "@/Pages/Theme/Components/Sidebar.vue";

</script>
