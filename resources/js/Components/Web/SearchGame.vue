<script setup>
import { useForm } from "laravel-precognition-vue";
import TextInput from "@/Components/TextInput.vue";
import { ref } from "vue";
import GameCard from "@/Components/Web/GameCard.vue";
import pkg from "lodash";
import ThumbPlay from "@/Components/ThumbPlay.vue";

const { debounce } = pkg;

const listGames = ref(null);
const showResult = ref(false);

const form = useForm("post", "/casino/search", {
    query: "",
});

const debounceSubmit = debounce(() => {
    if (form.query.length > 0) {
        form.submit({
            preserveScroll: true,
            onStart: () => (listGames.value = null),
            onSuccess: (a) => {
                listGames.value = a.data.games;
                showResult.value = true;
            },
        });
    }
}, 100);
</script>

<template>
    <form @submit.prevent="debounceSubmit">
        <TextInput
            id="query"
            class="uppercase font-oswald"
            :placeholder="$t('home.input.search_placeholder')"
            v-model="form.query"
            @input="debounceSubmit"
        />

        <div v-if="form.invalid('query')">
            {{ form.errors.query }}
        </div>
    </form>
    <div v-show="form.processing">...</div>
    <div
        class="w-full px-2 grid grid-cols-2 sm:grid-cols-4 mt-4 gap-4"
        v-show="showResult"
    >
        <div class="w-full" v-for="(g, index) in listGames" :key="index">
            <ThumbPlay :data="g" />
        </div>
    </div>
</template>

<style scoped></style>
