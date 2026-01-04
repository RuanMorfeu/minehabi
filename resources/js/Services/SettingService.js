export default {
    language: navigator.language,
    construct() {
        const localSettings = this.settings();
        console.log(localSettings);
    },

    init() {},

    getLocalData(key) {
        return window.localStorage.getItem(key);
    },

    setLocalData(key, value) {
        window.localStorage.setItem(key, value);
    },

    removeLocalData(key) {
        window.localStorage.removeItem(key);
    },

    settings() {
        let settings = this.getLocalData("settings");
        if (!settings) {
            this.setLocalData("settings", JSON.stringify(this.getSettings()));
            return settings;
        }
        JSON.parse(this.getLocalData("settings"));
    },

    async getSettings() {
        // eslint-disable-next-line no-undef
        await axios.get("/api/v1/settings").then((response) => {
            console.log(response.data);

            return response.data;
        });
    },
};
