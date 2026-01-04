<template>
    <div class="mt-2 w-full overflow-hidden">
        <div
            ref="carousel"
            class="w-full text-sm flex items-center gap-3 overflow-x-auto scroll-smooth cursor-grab py-4 px-2"
            @mousedown="startDragging"
            @mousemove="onDragging"
            @mouseup="stopDragging"
            @mouseleave="stopDragging"
            @touchstart="startDragging"
            @touchmove="onDragging"
            @touchend="stopDragging"
        >
            <Link
                v-for="(userNavigation, index) in userNavigations"
                :key="index"
                class="flex text-nowrap items-center gap-4 px-4 py-2 bg-transparent rounded-lg shadow-md transition-transform transform hover:scale-105"
                :href="route(userNavigation.route)"
            >
                <span class="material-symbols-outlined text-lg">
                    {{ userNavigation.icon }}
                </span>
                {{ $t(userNavigation.label) }}
            </Link>
        </div>
    </div>
</template>

<script setup>
import { ref } from "vue";
import { Link } from "@inertiajs/vue3";

const userNavigations = ref([
    {
        label: "user.navigation.my_wallet",
        icon: "wallet",
        route: "wallet.transactions",
    },
    {
        label: "user.navigation.profile",
        icon: "account_circle",
        route: "profile.edit",
    },
    {
        label: "user.navigation.bounty",
        icon: "rewarded_ads",
        route: "bounty",
    },
    {
        label: "user.navigation.affiliate",
        icon: "supervised_user_circle",
        route: "referral",
    },
    {
        label: "user.navigation.withdraw",
        icon: "account_balance",
        route: "wallet.withdraw",
    },
    {
        label: "user.navigation.logout",
        icon: "logout",
        route: "logout",
    },
]);

const carousel = ref(null);
let isDragging = false;
let startX = 0;
let scrollLeft = 0;

const startDragging = (e) => {
    isDragging = true;
    carousel.value.classList.add("dragging");
    startX = e.pageX || e.touches[0].pageX;
    scrollLeft = carousel.value.scrollLeft;
};

const onDragging = (e) => {
    if (!isDragging) return;
    e.preventDefault();
    const x = e.pageX || e.touches[0].pageX;
    const walk = (x - startX) * 1.5; // Ajuste para movimento mais fluido
    carousel.value.scrollLeft = scrollLeft - walk;
};

const stopDragging = () => {
    isDragging = false;
    carousel.value.classList.remove("dragging");
};
</script>

<style scoped>
.dragging {
    cursor: grabbing;
    user-select: none;
}

.material-symbols-outlined {
    font-size: 1.5rem;
}

.carousel-item {
    padding: 16px;
    background-color: #f3f3f3;
    border-radius: 8px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

.hover\:scale-105:hover {
    transform: scale(1.05);
    transition: transform 0.3s ease;
}
</style>
