<template>
    <div
        class="fixed inline-flex bottom-0 transform mx-auto bg-darkboss justify-between w-full"
    >
        <button
            aria-current="page"
            class="inline-flex flex-col items-center text-xs font-medium py-3 px-4 text-white flex-grow"
            @click="countStore.increment"
        >
            <Bars3Icon class="w-7 h-7" />
            <span class="sr-only">Home</span>
        </button>
        <Link
            class="inline-flex flex-col items-center text-xs font-medium text-blue-400 py-3 px-4 flex-grow"
            href="#"
        >
            <GiftIcon class="w-7 h-7" />
        </Link>
        <span class="sr-only">Upload</span>
        <button
            class="relative inline-flex flex-col items-center text-xs font-medium text-white py-3 px-6 flex-grow"
        >
            <div
                class="absolute bottom-5 p-3 rounded-full border-4 border-transparent bg-secondary-600"
            >
                <span class="material-symbols-outlined"> wallet </span>
            </div>
            <span class="sr-only">Chat</span>
        </button>
        <Link
            class="inline-flex flex-col items-center text-xs font-medium text-blue-400 py-3 px-4 flex-grow"
            href="#"
        >
            <BellIcon class="w-7 h-7" />
            <span class="sr-only">Search</span>
        </Link>
        <Link
            class="inline-flex flex-col items-center text-xs font-medium text-primary-200 py-3 px-4 flex-grow"
            href="#"
        >
            <UserIcon class="w-7 h-7" />
            <span class="sr-only">Profile</span>
        </Link>
    </div>
</template>
<script setup>
import {
    UserIcon,
    GiftIcon,
    BellIcon,
    Bars3Icon,
} from "@heroicons/vue/24/solid/index.js";
import { Link } from "@inertiajs/vue3";
import { useCounterStore } from "@/Store/Counter.js";

const countStore = useCounterStore();
</script>
