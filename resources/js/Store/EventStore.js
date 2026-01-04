import { defineStore } from "pinia";

export const eventStore = defineStore("EventStore", {
    state: () => ({
        eventSidebar: false,
    }),
    getters: {
        getEventSidebar: (state) => state.eventSidebar,
    },
    actions: {
        changeEventSidebar: () => {
            this.eventSidebar = !this.eventSidebar;
        },
    },
});
