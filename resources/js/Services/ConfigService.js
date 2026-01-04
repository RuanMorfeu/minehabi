import { usePage } from "@inertiajs/vue3";

export default {
    get(path) {
        if (typeof usePage().props.settings !== "undefined") {
            const obj = usePage().props.settings;
            const keys = path.split(".");
            let value = obj;
            for (let key of keys) {
                if (value && typeof value === "object" && key in value) {
                    value = value[key];
                } else {
                    return false;
                }
            }
            return value;
        }
    },
};
