<template>
    <RouterLink :to="getRouterLink()" custom v-slot="{ navigate }">
        <div class="flex text-gray-700 w-full h-auto mr-2 cursor-pointer relative"
             @mouseover="showGameInfo = true"
             @mouseleave="showGameInfo = false"
             @click.prevent="handlePlay(navigate)">
            <div class="relative">
                <img :src="getCover(getGameCover())" alt="" class="rounded-lg lg:w-auto" :style="{ opacity: showGameInfo ? '0.5' : '1' }">

                <span v-if="game.distribution === 'exclusive'" class="absolute top-2 right-2 bg-black bg-opacity-70 text-white text-xs px-2 py-1 rounded">
                    {{ formatDate(game.created_at) }}
                </span>

                <div v-if="hasFreespins" class="absolute top-2 left-2 bg-gradient-to-r from-blue-600 to-blue-500 text-white text-xs px-2 py-1 rounded-md font-bold shadow-md flex items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Giros Grátis
                </div>

                <div v-if="showGameInfo" class="absolute inset-0 flex justify-center items-center rounded-lg backdrop-blur-sm px-3 py-2">
                    <div class="text-center text-white max-w-[90%]">
                        <span class="block truncate text-[12px]">{{ game.game_name }}</span>
                        <div class="flex flex-col">
                            <button type="button" class="ui-button-modal mt-2" @click.prevent.stop="handlePlay(navigate)">
                                <i class="fas fa-play-circle mr-1"></i> Jogar
                            </button>

                            <button v-if="showDemoButton && !isAuthenticated && (game.distribution === 'exclusive' || game.distribution === 'exclusive2')" type="button" class="ui-button-modal mt-2" @click.stop="playDemo">
                                <i class="fas fa-play-circle mr-1"></i> Jogar demo
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </RouterLink>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { useAuthStore } from "@/Stores/Auth.js";
import { useSettingStore } from "@/Stores/SettingStore.js";
import { useUIStore } from '@/Stores/UIStore.js';
import HttpApi from '@/Services/HttpApi.js';
import { RouterLink, useRouter } from "vue-router";

const props = defineProps({
    index: { type: Number },
    game: { type: Object },
    useHomeCover: { type: Boolean, default: false },
    showDemoButton: { type: Boolean, default: true }
});

// Variáveis e funções no script
const showGameInfo = ref(false);
const router = useRouter();

// Detectar se é dispositivo móvel
const isMobile = ref(false);
onMounted(() => {
    isMobile.value = /Android|iPhone|iPad|iPod|Opera Mini|IEMobile|WPDesktop/i.test(navigator.userAgent);
});

const authStore = useAuthStore();
const settingStore = useSettingStore();
const uiStore = useUIStore();
const isAuthenticated = computed(() => authStore.isAuth);

// Verificar se o jogo tem giros grátis ativos
const hasFreespins = computed(() => {
    const settings = settingStore.getSettingData();
    if (!settings) return false;
    
    // Verificar freespins de primeiro depósito
    if (settings.game_free_rounds_active_deposit && 
        settings.game_code_rounds_free_deposit === props.game.game_code) {
        return true;
    }
    
    // Verificar freespins de depósitos subsequentes
    if (settings.game_free_rounds_active_any_deposit && 
        settings.game_code_rounds_free_any_deposit === props.game.game_code) {
        return true;
    }
    
    // Verificar freespins de registro
    if (settings.game_free_rounds_active_register && 
        settings.game_code_rounds_free_register === props.game.game_code) {
        return true;
    }
    
    return false;
});

const checkUserHasDeposits = async () => {
    if (!authStore.isAuth) return false;
    try {
        const response = await HttpApi.get('wallet/deposit/has-deposits');
        return response.data.has_deposits;
    } catch (error) {
        console.error('Erro ao verificar depósitos do usuário via API. Usando fallback.', error);
        return authStore.user?.has_deposit || false;
    }
};

const handlePlay = async (navigate) => {
    // console.log('--- handlePlay CALLED ---', { 
    //     game: props.game.game_name, 
    //     provider: props.game.provider?.name, 
    //     distribution: props.game.distribution,
    //     game_id: props.game.id,
    //     game_obj: props.game
    // });
    
    // Verificar se é um jogo de provedor alvo ou exclusivo
    const providerName = props.game.provider ? props.game.provider.name : null;
    const distribution = props.game.distribution;
    const targetProviders = ['Evolution', 'Sagaming'];
    const isExclusive = distribution === 'exclusive';
    const isExclusive2 = distribution === 'exclusive2';
    const isCreedzGame = providerName === 'creedz'; // Verifica pelo nome do provedor, não pela distribuição
    const isProviderTarget = targetProviders.includes(providerName) || isCreedzGame;
    
    // console.log('Dados do jogo detalhados:', {
    //     providerName,
    //     distribution,
    //     isExclusive,
    //     isCreedzGame,
    //     isProviderTarget,
    //     'targetProviders.includes(providerName)': targetProviders.includes(providerName),
    //     'distribution === creedz': distribution === 'creedz',
    //     'props.game.distribution': props.game.distribution,
    //     'typeof props.game.distribution': typeof props.game.distribution
    // });
    
    // Verificar se o usuário está autenticado usando a propriedade correta do authStore
    // console.log('Estado de autenticação:', { isAuth: authStore.isAuth, user: !!authStore.user });
    
    // Se o usuário não estiver logado
    if (!authStore.isAuth) {
        // console.log('Usuário não está logado');
        if (isProviderTarget) {
            // Redirecionar para o login em vez de mostrar popup
            router.push('/login');
        } else {
            // console.log('Usuário não logado, navegando normalmente');
            navigate();
        }
        return;
    }

    // Usuário está logado, verificar se é influencer ou se possui depósito
    try {
        // Verificar se o usuário é um influencer (is_demo_agent)
        const isInfluencer = authStore.user?.is_demo_agent === 1 || authStore.user?.is_demo_agent === true;
        
        // Se for influencer, permite acesso direto sem verificar depósito
        if (isInfluencer) {
            // console.log('Usuário é influencer, navegando normalmente sem verificar depósito');
            navigate();
            return;
        }
        
        // console.log('Usuário logado, verificando depósito...');
        const response = await HttpApi.get('wallet/deposit/has-deposits');
        const hasDeposit = response.data.has_deposits;
        // console.log('Resposta da API de depósitos:', hasDeposit);

        if (hasDeposit) {
            // console.log('Usuário tem depósito, navegando normalmente');
            navigate();
        } else {
            // console.log('Usuário SEM depósito, verificando condições para popup');
            
            if (isExclusive) {
                // console.log('Exibindo popup de jogo exclusivo');
                uiStore.setExclusiveGamePopup(true, props.game);
            } else if (isProviderTarget) {
                // console.log('Exibindo popup de provedor');
                // console.log('Estado antes:', { showPopup: uiStore.showProviderGamePopup, game: uiStore.providerGame });
                uiStore.setProviderGamePopup(true, props.game);
                // console.log('Estado depois:', { showPopup: uiStore.showProviderGamePopup, game: uiStore.providerGame });
            } else {
                // console.log('Jogo padrão, navegando normalmente');
                navigate();
            }
        }
    } catch (error) {
        console.error('Erro ao verificar depósito:', error);
        navigate();
    }
};

const getRouterLink = () => {
    if (props.game.distribution === 'exclusive') {
        return { name: 'playModal', params: { slug: props.game.id } };
    } else if (props.game.distribution === 'exclusive2') {
        return { name: 'playModal2', params: { slug: props.game.game_code } };
    } else {
        return { name: 'casinoPlayPage', params: { id: props.game.id, slug: props.game.game_code }};
    }
};

function playDemo() {
    if (props.game.distribution === 'exclusive2') {
        // Jogos exclusive2 usam playModal2 com game_code (UUID)
        router.push({ name: 'playModal2', params: { slug: props.game.game_code } });
    } else {
        // Jogos exclusivos originais usam playModal com ID
        router.push({ name: 'playModal', params: { slug: props.game.id } });
    }
}

function getGameCover() {
    // Se useHomeCover for true e o jogo tiver uma capa específica para a home, use-a
    if (props.useHomeCover && props.game.home_cover && props.game.home_cover !== null && props.game.home_cover !== '') {
        return props.game.home_cover;
    }
    
    // Caso contrário, use a capa padrão
    return props.game.cover;
}

function getCover(slug) {
    if (!slug) {
        return '';
    }
    
    if (slug.startsWith('http')) {
        return slug;
    }
    return '/storage/' + slug;
}

function formatDate(date) {
    return ''; // Ou null se preferir
}
</script>


<style scoped>
/* Seus estilos existentes */
</style>
