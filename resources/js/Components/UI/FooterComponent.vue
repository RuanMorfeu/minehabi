<template>
    <div class="footer pb-32 md:pb-5 mt-5 footer-color p-4 md:p-8 border-t border-gray-200 dark:border-gray-700">
        <div class="md:w-4/6 2xl:w-4/6 mx-auto p-4">
            <div class="flex flex-col items-center justify-center">
                <!-- Logo -->
                <div class="mb-8">
                    <img
                        :src="`/storage/${setting.software_logo_black}`"
                        :alt="setting.software_name"
                        class="w-48 opacity-90 hover:opacity-100 transition-opacity block dark:hidden"
                    />
                    <img
                        :src="`/storage/${setting.software_logo_white}`"
                        :alt="setting.software_name"
                        class="w-48 opacity-90 hover:opacity-100 transition-opacity hidden dark:block"
                    />
                </div>

                <!-- Links Section -->
                <div class="flex flex-wrap justify-center gap-4 md:gap-8 mb-8 text-sm font-medium text-black">
                    <router-link to="/terms/service" class="hover:text-blue-600 transition-colors uppercase tracking-wider text-xs">
                        {{ $t('Termos de Serviço') }}
                    </router-link>
                    <span class="text-black hidden md:block">•</span>
                    <router-link to="/terms/privacy-policy" class="hover:text-blue-600 transition-colors uppercase tracking-wider text-xs">
                        {{ $t('Política de Privacidade') }}
                    </router-link>
                    <span class="text-black hidden md:block">•</span>
                    <router-link to="/terms/bonus" class="hover:text-blue-600 transition-colors uppercase tracking-wider text-xs">
                        {{ $t('Termos de Bônus') }}
                    </router-link>
                    <span class="text-black hidden md:block">•</span>
                    <router-link to="/support" class="hover:text-blue-600 transition-colors uppercase tracking-wider text-xs">
                        {{ $t('Suporte') }}
                    </router-link>
                </div>

                 <div class="text-center w-full border-t border-gray-200 dark:border-gray-700 pt-8">
                    <!-- Security Badges -->
                    <div class="flex justify-center items-center gap-6 mb-6 grayscale hover:grayscale-0 transition-all duration-300">
                        <img :src="p18Img" alt="+18" class="h-8" />
                        <img :src="siteSeguroImg" alt="Site Seguro" class="h-8" />
                    </div>

                    <!-- Copyright -->
                    <div class="text-[11px] text-black">
                        <p>
                            &copy; {{ year }} <span class="font-bold text-black">{{ setting.software_name }}</span> - {{ $t("Todos os direitos reservados") }}.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { useSettingStore } from "@/Stores/SettingStore.js";
import p18Img from '../../../images/p18.svg';
import siteSeguroImg from '../../../images/Site_Seguro_Selos.png';

export default {
    data() {
        return {
            setting: null,
            year: new Date().getFullYear(),
            p18Img,
            siteSeguroImg,
        };
    },
    methods: {
        getSetting() {
            const settingStore = useSettingStore();
            const settingData = settingStore.setting;
            if (settingData) {
                this.setting = settingData;
            }
        },
    },
    created() {
        this.getSetting();
    },
};
</script>

<style scoped>
.dark\:text-gray-400:is(.dark *) {
    --tw-text-opacity: 1;
    color: rgb(255 255 255);
}
.container {
    display: flex;
    /* Usa flexbox para alinhar os elementos */
    justify-content: center;
    /* Centraliza os botões horizontalmente */
    align-items: center;
    /* Centraliza os botões verticalmente */
}

.small-btn {
    margin: 5px;
    /* Espaço entre os botões */
    padding: 0;
    /* Remove o padding padrão */
}

.small-btn img {
    width: 100px;
    /* Define a largura das imagens */
    height: auto;
    /* Mantém a proporção das imagens */
}

.text-\[12px\] {
    font-size: 12px;
    color: #fff;
}
</style>
