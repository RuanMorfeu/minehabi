<template>
    <WebLayout>
        <WebContent>
            <div>
                <div class="py-3 text-xl font-oswald uppercase font-black">
                    {{ content.content }}
                </div>
                <div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <Link
                            class="w-full"
                            v-for="(block, index) in content.content_blocks"
                            :key="index"
                            :href="route('promo.block.view', block.id)"
                        >
                            <div>
                                <img
                                    class="w-full rounded-t object-cover h-40"
                                    :src="'https://cdn.xpdom.com/' + block?.image"
                                    alt="Content"
                                />
                            </div>
                            <div
                                class="py-4 rounded-b font-oswald px-4 bg-zinc-900 text-lg"
                            >
                                {{ block.title }}
                            </div>
                        </Link>
                    </div>
                </div>
            </div>
        </WebContent>
    </WebLayout>
</template>
<script setup>
import WebLayout from "@/Pages/Theme/Layout/WebLayout.vue";
import WebContent from "@/Pages/Theme/Components/WebContent.vue";
import { Link } from "@inertiajs/vue3";

defineProps({
    content: {
        type: Object,
        required: false,
        default: () => {},
    },
});
</script>
