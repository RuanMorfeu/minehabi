<template>
    <TransitionRoot as="template" :show="popUpOpen">
        <div
            v-show="popUpOpen"
            class="w-full cursor-pointer bg-yellow-400 bg-[url('https://8u111.com/static/images/c2/syscom/lack/download_bg.png')] bg-center bg-no-repeat hover:bg-yellow-500"
        >
            <div class="text-black justify-center bg-center px-4 md:px-6">
                <div class="grid grid-cols-2 items-center gap-2 sm:grid-cols-3">
                    <div class="flex w-full items-center">
                        <div class="w-full py-2.5 leading-4">
                            <h2 class="font-black">1 BITCOIN GRÁTIS</h2>
                            <span
                                class="font-oswald block text-xs font-bold uppercase"
                                >60 giros grátis e concorrer a 1 BTC 100%
                                GRÁTIS!</span
                            >
                        </div>
                    </div>
                    <div
                        class="w-full text-nowrap text-center text-sm uppercase"
                    >

                        para resgatar
                    </div>
                    <div
                        class="hidden w-full items-center justify-center sm:flex"
                    >
                        <img
                            class="h-16 w-full object-cover"
                            src="/assets/img/bg-2-popup.png"
                            alt=""
                        />
                    </div>
                </div>
            </div>
        </div>
    </TransitionRoot>
</template>

<script setup>
import { TransitionRoot } from "@headlessui/vue";
import { ref } from "vue";

const popUpOpen = ref(true);

if (!window.localStorage.getItem("popUpFirstVisit")) {
    window.localStorage.setItem("popUpFirstVisit", "visited");
} else {
    popUpOpen.value = false;
}


</script>
<style scoped></style>
