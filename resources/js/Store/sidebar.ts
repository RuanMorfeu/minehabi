import { defineStore } from "pinia";
import { computed, ref } from "vue";

export const useSideBarStore = defineStore("sidebar", () => {
    const sidebarOpen = ref(false);

    function toggleSidebarOpen() {
        sidebarOpen.value = !sidebarOpen.value;
    }

    return { sidebarOpen, toggleSidebarOpen };
});

/*
{
    state: () => ({
        sidebarOpen: false
    }),
    getters: {
        getSidebarIs: (state) => state.sidebarOpen
    },
    actions: {
        setSidebarOpen: () => {
            state.sidebarOpen = !state.sidebarOpen;
        }
    }

}
 */
