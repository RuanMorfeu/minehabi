import { defineStore } from "pinia";

export const useCounterStore = defineStore({
    id: "counter",
    state: () => ({
        counter: false,
    }),
    getters: {
        count: (state) => state.counter,
    },
    actions: {
        increment() {
            this.counter = !this.counter;
        },
    },
});
