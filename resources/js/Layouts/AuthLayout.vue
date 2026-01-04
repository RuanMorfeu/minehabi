<template>
    <NavTopComponent :simple="simple ?? true" />

    <SideBarComponent v-once />

    <div class="sm:ml-64 mt-16">
        <div class="relative">
            <slot></slot>

        </div>
    </div>
</template>

<script>
import { initFlowbite } from 'flowbite';
import { onMounted, watch } from "vue";
import NavTopComponent from "@/Components/Nav/NavTopComponent.vue";
import SideBarComponent from "@/Components/Nav/SideBarComponent.vue";
import FooterComponent from "@/Components/UI/FooterComponent.vue";
import BottomNavComponent from "@/Components/Nav/BottomNavComponent.vue";
import CookiesComponent from "@/Components/UI/CookiesComponent.vue";
import { useRoute } from "vue-router";

export default {
    props: ['simple'],
    components: {CookiesComponent, BottomNavComponent, FooterComponent, SideBarComponent, NavTopComponent },
    data() {
        return {
            logo: '/assets/images/logo_white.png',
            isLoading: false,
        }
    },
    setup(props) {
        const route = useRoute();
        
        // Função para capturar e salvar o código do influencer (para popups)
        function captureInfluencerCode() {
            // Verifica se há código na URL atual (prioridade máxima)
            const urlParams = new URLSearchParams(window.location.search);
            const codeFromUrl = urlParams.get('ref') || urlParams.get('influencer') || urlParams.get('aff');
            
            // Se encontrou na URL, salva no localStorage e retorna
            if (codeFromUrl) {

                localStorage.setItem('influencer_code', codeFromUrl);
                return codeFromUrl;
            }
            
            // Se não encontrou na URL, tenta obter do localStorage
            const savedCode = localStorage.getItem('influencer_code');
            if (savedCode) {

                return savedCode;
            }
            
            // Retorna null se não encontrou nenhum código
            return null;
        }
        
        // Função para capturar e salvar o código do influencer para bônus
        function captureBonusInfluencerCode() {
            // Verifica se há código na URL atual (prioridade máxima)
            const urlParams = new URLSearchParams(window.location.search);
            const codeFromUrl = urlParams.get('bonus');
            
            // Se encontrou na URL, salva no localStorage e retorna
            if (codeFromUrl) {

                localStorage.setItem('bonus_influencer_code', codeFromUrl);
                return codeFromUrl;
            }
            
            // Se não encontrou na URL, tenta obter do localStorage
            const savedCode = localStorage.getItem('bonus_influencer_code');
            if (savedCode) {

                return savedCode;
            }
            
            // Retorna null se não encontrou nenhum código
            return null;
        }
        
        onMounted(() => {
            initFlowbite();
            // Captura o código do influencer ao montar o componente
            captureInfluencerCode();
            // Captura o código do influencer para bônus
            captureBonusInfluencerCode();
        });
        
        // Observa mudanças na rota para capturar o código do influencer em navegações
        watch(() => route.fullPath, () => {
            captureInfluencerCode();
            captureBonusInfluencerCode();
        });

        return {};
    },
    computed: {

    },
    mounted() {


        // setTimeout(() => {
        //     this.isLoading = false
        // }, 3000)
    },
    methods: {

    },
    watch: {

    },
};
</script>
