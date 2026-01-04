import { defineStore } from 'pinia';

export const useUIStore = defineStore('ui', {
    state: () => ({
        // State for the exclusive game popup
        showExclusiveGamePopup: false,
        exclusiveGame: null,

        // State for the provider-specific game popup
        showProviderGamePopup: false,
        providerGame: null,
    }),

    actions: {
        /**
         * Controls the visibility of the exclusive game popup.
         * @param {boolean} status - True to show, false to hide.
         * @param {object|null} game - The game data to display.
         */
        setExclusiveGamePopup(status, game = null) {
            this.exclusiveGame = game;
            this.showExclusiveGamePopup = status;
        },

        /**
         * Controls the visibility of the provider-specific game popup.
         * @param {boolean} status - True to show, false to hide.
         * @param {object|null} game - The game data to display.
         */
        setProviderGamePopup(status, game = null) {
            this.providerGame = game;
            this.showProviderGamePopup = status;
        },
    },
});
