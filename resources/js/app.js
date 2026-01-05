import './bootstrap';
import '../css/app.css';
import './facebook-tracking';
import './facebook-pixel-events';
import { createApp, markRaw } from 'vue/dist/vue.esm-bundler';
import { createRouter, createWebHistory } from 'vue-router';
import { createPinia } from 'pinia';
import { i18nVue } from 'laravel-vue-i18n';
import { vMaska } from "maska";
import '@fortawesome/fontawesome-free/css/all.min.css';
import { InertiaProgress } from '@inertiajs/progress';

InertiaProgress.init();

/**
 * LIBS
 */
import moment from 'moment';
import "vue-toastification/dist/index.css";
import '@/index.css';

import App from './App.vue';
import {useAuthStore} from "@/Stores/Auth.js";


/**
 * APP
 */
const app = createApp(App);

app.config.globalProperties.state = {
    platformName() {
        return 'GANHOUBET';
    },
    dateFormatServer(date) {
        const currentDate = new Date(date);
        const year = currentDate.getFullYear();
        const month = String(currentDate.getMonth() + 1).padStart(2, '0');
        const day = String(currentDate.getDate()).padStart(2, '0');

        return `${year}-${month}-${day}`; // Remove hífens do final
    },
    formatDate(dateTimeString) {
        const date = new Date(dateTimeString);
        const today = new Date();
        const tomorrow = new Date(today);
        tomorrow.setDate(tomorrow.getDate() + 1);
        const dayAfterTomorrow = new Date(today);
        dayAfterTomorrow.setDate(dayAfterTomorrow.getDate() + 2);

        const options = { hour: '2-digit', minute: '2-digit' };

        if (date.toDateString() === today.toDateString()) {
            return `Hoje às ${date.toLocaleTimeString(document.documentElement.getAttribute("lang"), options)}`;
        } else if (date.toDateString() === tomorrow.toDateString()) {
            return `Amanhã às ${date.toLocaleTimeString(document.documentElement.getAttribute("lang"), options)}`;
        } else if (date.toDateString() === dayAfterTomorrow.toDateString()) {
            const dayOfWeek = new Intl.DateTimeFormat(document.documentElement.getAttribute("lang"), { weekday: 'long' }).format(date);
            return `Na ${dayOfWeek} às ${date.toLocaleTimeString(document.documentElement.getAttribute("lang"), options)}`;
        } else {
            return `${date.toLocaleDateString(document.documentElement.getAttribute("lang"))} às ${date.toLocaleTimeString(document.documentElement.getAttribute("lang"), options)}`;
        }
    },
    generateSlug(text) {
        return text
            .toString()
            .toLowerCase()
            .trim()
            .replace(/\s+/g, '-') // Substitui espaços por hífen
            .replace(/[^\w\-]+/g, '') // Remove caracteres especiais
            .replace(/\-\-+/g, '-') // Substitui múltiplos hífens por um único hífen
            .replace(/^-+/, '') // Remove hífens do início
            .replace(/-+$/, ''); // Remove hífens do final
    },
    timeAgo(value) {
        return moment(value).fromNow();
    },
    currencyFormat(value, currency) {
        if (value === undefined || currency === undefined) {
            currency = 'USD';
        }

        const options = {
            style: 'currency',
            currency: currency
        };

        return value.toLocaleString(document.documentElement.getAttribute("lang"), options);
    },
    currencyUSD(value) {
        if (typeof value === 'number') {
            return new Intl.NumberFormat('en-US',{
                currency: 'USD',
                minimumFractionDigits: 2,
            }).format(value);
        }else{
            return new Intl.NumberFormat('en-US',{
                currency: 'USD',
                minimumFractionDigits: 2,
            }).format(parseFloat(value));
        }
    },
    limitCharacters(value, limite) {
        if (!value) return '';
        if (value.length <= limite) return value;
        return value.slice(0, limite) + '...';
    },
};

/**
 * Axios
 */
import axios from 'axios'
import VueAxios from 'vue-axios'


app.use(VueAxios, axios)
app.provide('axios', app.config.globalProperties.axios)  // provide 'axios'

import VueFullscreen from 'vue-fullscreen'
app.use(VueFullscreen)

/**
 * Toast
 */
import Toast from "vue-toastification";
const optionsToast = {
    position: 'bottom-right',
    timeout: 3800,
    closeOnClick: true,
    pauseOnFocusLoss: true,
    pauseOnHover: true,
    draggable: true,
    draggablePercent: 0.6,
    showCloseButtonOnHover: false,
    hideProgressBar: true,
    closeButton: false,
    icon: false,
    maxToasts: 1,
    newestOnTop: true,
    toastClassName: 'snake-toast',
    bodyClassName: 'snake-toast__body',
}
app.use(Toast, optionsToast)

/**
 * Router
 */
import router from './Router';
import {useSettingStore} from "@/Stores/SettingStore.js";
app.use(router);

/// Directive
app.directive("maska", vMaska)

/**
 * PINIA
 * @type {Pinia}
 */
const pinia = createPinia()
pinia.use(({ store }) => { store.router = markRaw(router)})
app.use(pinia);

app.use(i18nVue, {
    resolve: async lang => {
        try {
            const langs = import.meta.glob('../../lang/*.json', { eager: false });
            const loader = langs[`../../lang/${lang}.json`];
            if (!loader) {
                console.warn(`Language file ${lang}.json not found`);
                return {};
            }
            return await loader();
        } catch (error) {
            console.error(`Error loading language ${lang}:`, error);
            return {};
        }
    }
});

(async () => {
    const setting = useSettingStore();
    try {
        const settingData = await setting.getSetting();
        setting.setSetting(settingData);
    } catch (e) {

    }
})()

if(localStorage.getItem('token')) {
    (async () => {
        const auth = useAuthStore();
        try {
            auth.setIsAuth(true);
            const user = await auth.checkToken();
            if(user !== undefined) {
                auth.setUser(user);
            }

            //auth.initializingEcho();
        } catch (e) {
            auth.setIsAuth(false);
        }
    })()
}

app.mount('#ganhoubet');
