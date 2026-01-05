<template>
    <BaseLayout>
        <LoadingComponent :isLoading="isLoading">
            <div class="flex justify-center items-center h-full">
                <a v-if="setting" href="/" class="logo-animation">
                    <!-- Adicionando a classe items-center para centralizar verticalmente -->
                    <img :src="`/storage/` + setting.software_logo_black" alt="" class="h-10 mr-3 block dark:hidden" />
                    <img :src="`/storage/` + setting.software_logo_white" alt="" class="h-10 mr-3 hidden dark:block" />
                </a>
            </div>
        </LoadingComponent>

        <div class="">

            <!-- Banners carousel structure match -->
            <div class="carousel-banners">
            </div>

            <div class="md:w-5/6 2xl:w-5/6 mx-auto p-4">
                <div class="md:mt-5">
                    <div class="rounded w-full overflow-hidden">
                        <a href="#" class="w-full h-full bg-blue-800 rounded">
                            <img :src="'/storage/01JDKT3JG0N7JGQQXMXA9J9QTY.webp'" alt="Banner" class="w-full h-full rounded">
                        </a>
                    </div>
                </div>

                <div class="mt-5 hidden md:block" v-if="bannersHome && bannersHome.length > 0">
                    <Carousel v-bind="settingsRecommended" :breakpoints="breakpointsRecommended"
                        ref="carouselSubBanner">
                        <Slide v-for="(banner, index) in bannersHome" :key="index">
                            <div class="carousel__item h-full rounded w-full mr-4">
                                <a :href="banner.link" class="w-full h-full rounded">
                                    <img :src="banner.image.startsWith('http') ? banner.image : `/storage/` + banner.image" alt="" class="h-full w-full rounded">
                                </a>
                            </div>
                        </Slide>
                    </Carousel>
                </div>
                <br>
                
                <!-- Seção Jogos (Habilidade + Mines) -->
                <div class="flex flex-col lg:flex-row gap-4 mt-4 mb-4">
                    <div class="flex-1">
                        <div class="w-full flex justify-between mb-4">
                            <div class="flex items-center">
                                <h2 class="text-2xl font-bold text-gray-800 dark:text-white flex items-center gap-2">
                                    <i class="fa-solid fa-gamepad text-blue-600"></i> Jogos
                                </h2>
                            </div>
                        </div>

                        <!-- Grid de Jogos de Habilidade + Mines -->
                        <div v-if="allExclusiveGames && allExclusiveGames.length > 0" class="mb-2 bg-white dark:bg-[#1f2937] rounded-lg p-2 shadow-md">
                            <div class="grid grid-cols-3 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-2 sm:gap-3">
                                <!-- Card do Jogo Mines -->
                                <div class="relative group cursor-pointer" @click="goToMines">
                                    <div class="relative overflow-hidden rounded-lg shadow-lg transition-transform duration-300 hover:scale-105">
                                        <img :src="getCover(setting?.mines_cover || 'games/mines/cover.jpg')" alt="Mines" class="w-full h-32 object-cover">
                                        <div class="absolute inset-0 bg-gradient-to-t from-black/70 to-transparent"></div>
                                        <div class="absolute bottom-0 left-0 right-0 p-3">
                                            <h3 class="text-white font-bold text-sm">Mines</h3>
                                            <p class="text-gray-300 text-xs">Encontre as minas e ganhe!</p>
                                        </div>
                                        <div class="absolute top-2 right-2">
                                            <span class="bg-blue-600 text-white text-xs px-2 py-1 rounded-full">Novo</span>
                                        </div>
                                    </div>
                                </div>
                                
                                <CassinoGameCard v-for="(game, index) in allExclusiveGames" 
                                    :key="'exclusive_' + index" 
                                    :index="index" 
                                    :title="game.game_name"
                                    :cover="game.cover" 
                                    :gamecode="game.game_code" 
                                    :type="game.distribution" 
                                    :game="game" 
                                    :useHomeCover="true"
                                    :show-demo-button="false" />
                            </div>
                        </div>
                        <div v-else-if="!isLoading" class="mb-5 p-8 text-center bg-white dark:bg-[#1f2937] rounded-lg shadow-sm">
                            <p class="text-gray-500 dark:text-gray-400 text-lg">Nenhum jogo de habilidade encontrado.</p>
                        </div>

                        <!-- Loading State -->
                        <div v-if="isLoading" class="grid grid-cols-3 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-2 sm:gap-3">
                            <div v-for="i in 5" :key="i" role="status"
                                class="w-full flex items-center justify-center h-48 bg-gray-300 rounded-lg animate-pulse dark:bg-gray-700">
                                <i class="fa-duotone fa-gamepad-modern text-4xl text-gray-400"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <hr class="my-4 border-gray-300 dark:border-gray-700 opacity-50">

                <!-- Seção Slots (PG Soft) -->
                <div class="flex flex-col gap-4 mt-2 pb-10">
                    <div class="w-full flex justify-between mb-4">
                        <div class="flex items-center">
                            <h2 class="text-2xl font-bold text-gray-800 dark:text-white flex items-center gap-2">
                                <i class="fa-solid fa-slot-machine text-purple-600"></i> Slots
                            </h2>
                        </div>
                    </div>

                    <div v-if="pgSlots && pgSlots.length > 0" class="bg-white dark:bg-[#1f2937] rounded-lg p-2 shadow-md">
                        <div class="grid grid-cols-3 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-2 sm:gap-3">
                            <CassinoGameCard v-for="(game, index) in pgSlots" 
                                :key="'pg_slot_' + index" 
                                :index="index" 
                                :title="game.game_name"
                                :cover="game.cover" 
                                :gamecode="game.game_code" 
                                :type="game.distribution" 
                                :game="game" 
                                :useHomeCover="true" />
                        </div>
                    </div>
                    <div v-else-if="!isLoading" class="mb-5 p-8 text-center bg-white dark:bg-[#1f2937] rounded-lg shadow-sm">
                        <p class="text-gray-500 dark:text-gray-400 text-lg">Nenhum slot encontrado.</p>
                    </div>
                    
                    <!-- Loading State para Slots -->
                    <div v-if="isLoading" class="grid grid-cols-3 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-2 sm:gap-3">
                        <div v-for="i in 5" :key="i" role="status"
                            class="w-full flex items-center justify-center h-48 bg-gray-300 rounded-lg animate-pulse dark:bg-gray-700">
                            <i class="fa-duotone fa-gamepad-modern text-4xl text-gray-400"></i>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </BaseLayout>
</template>

<script>
import { Carousel, Navigation, Pagination, Slide } from 'vue3-carousel';
import { onMounted, ref, h } from "vue";
import { useToast } from "vue-toastification";

import BaseLayout from "@/Layouts/BaseLayout.vue";
import MakeDeposit from "@/Components/UI/MakeDeposit.vue";
import { RouterLink, useRoute } from "vue-router";
import { useAuthStore } from "@/Stores/Auth.js";
import LanguageSelector from "@/Components/UI/LanguageSelector.vue";
import CassinoGameCard from "@/Pages/Cassino/Components/CassinoGameCard.vue";
import MinesGameCard from "@/Components/Games/Mines/MinesGameCard.vue";
import HttpApi from "@/Services/HttpApi.js";
import ShowCarousel from "@/Pages/Home/Components/ShowCarousel.vue";
import { useSettingStore } from "@/Stores/SettingStore.js";
import LoadingComponent from "@/Components/UI/LoadingComponent.vue";
import { searchGameStore } from "@/Stores/SearchGameStore.js";
import CustomPagination from "@/Components/UI/CustomPagination.vue";

const CACHE_KEYS = {
    HOME_PAGE: 'home_page_cache_v2',
    BANNERS: 'home_banners_cache_v2',
    ALL_GAMES: 'home_all_games_cache_v2',
    SLOT_GAMES: 'home_slot_games_cache_v2',
};

export default {
    props: [],
    components: {
        CustomPagination,
        Pagination,
        LoadingComponent,
        ShowCarousel,
        CassinoGameCard,
        MinesGameCard,
        Carousel,
        Navigation,
        Slide,
        LanguageSelector,
        MakeDeposit,
        BaseLayout,
        RouterLink
    },
    data() {
        return {
            isLoading: true,

            lastWinnersToastTimer: null,
            lastWinnersToastIndex: 0,

            /// banners settings
            settings: {
                itemsToShow: 1,
                snapAlign: 'center',
                autoplay: 6000,
                wrapAround: true
            },
            breakpoints: {
                700: {
                    itemsToShow: 1,
                    snapAlign: 'center',
                },
                1024: {
                    itemsToShow: 1,
                    snapAlign: 'center',
                },
            },

            settingsRecommended: {
                itemsToShow: 2,
                snapAlign: 'start',
            },
            breakpointsRecommended: {
                700: {
                    itemsToShow: 3,
                    snapAlign: 'center',
                },
                1024: {
                    itemsToShow: 3,
                    snapAlign: 'start',
                },
            },

            settingsGames: {
                itemsToShow: 2.5,
                snapAlign: 'start',
            },
            breakpointsGames: {
                700: {
                    itemsToShow: 3.5,
                    snapAlign: 'center',
                },
                1024: {
                    itemsToShow: 4.5,
                    snapAlign: 'start',
                },
            },

            banners: [],
            bannersHome: [],

            exclusive_games: [],
            exclusive2_games: [],
            slot_games: [], // Para armazenar jogos marcados como slot
            providers: [], // Para armazenar todos os provedores e filtrar PG
        }
    },
    setup(props) {
        const ckCarouselOriginals = ref(null)
        const fgCarousel = ref(null)
        const fgExclusive = ref(null)
        const fgExclusive2 = ref(null)
        const fgJogosNew = ref(null)

        const toast = useToast();

        onMounted(() => {

        });

        return {
            ckCarouselOriginals,
            fgCarousel,
            fgExclusive,
            fgExclusive2,
            fgJogosNew,
            toast
        };
    },
    computed: {
        searchGameStore() {
            return searchGameStore();
        },
        // Identifica o ID do provedor PG Soft
        pgProviderId() {
            if (!this.providers) return null;
            const p = this.providers.find(p => 
                p.name && (p.name.toLowerCase().includes('pg') || 
                p.name.toLowerCase().includes('pocket games') || 
                p.name.toLowerCase().includes('pgsoft'))
            );
            return p ? p.id : null;
        },
        // Combina jogos exclusivos e exclusive2 em uma única lista para "Jogos de habilidade"
        allExclusiveGames() {
            console.log('--- Computando allExclusiveGames ---');
            console.log('exclusive_games:', this.exclusive_games ? this.exclusive_games.length : 0);
            console.log('exclusive2_games:', this.exclusive2_games ? this.exclusive2_games.length : 0);
            
            const games = [];
            
            // Adiciona jogos exclusivos originais
            if (this.exclusive_games && this.exclusive_games.length > 0) {
                console.log('Adicionando exclusive_games:', this.exclusive_games.map(g => g.game_name));
                games.push(...this.exclusive_games);
            }
            
            // Adiciona jogos exclusive2
            if (this.exclusive2_games && this.exclusive2_games.length > 0) {
                console.log('Adicionando exclusive2_games:', this.exclusive2_games.map(g => g.game_name));
                games.push(...this.exclusive2_games);
            }
            
            console.log('Total jogos combinados:', games.length);
            
            // Ordena por visualizações (mais vistos primeiro) com tratamento robusto
            return games.sort((a, b) => {
                const viewsA = parseInt(a.views) || 0;
                const viewsB = parseInt(b.views) || 0;
                return viewsB - viewsA;
            });
        },
        // Filtra jogos marcados como Slot
        pgSlots() {
            console.log('--- Retornando slot_games da API ---');
            console.log('Total jogos Slots:', this.slot_games ? this.slot_games.length : 0);
            return this.slot_games || [];
        },
        userData() {
            const authStore = useAuthStore();
            return authStore.user;
        },
        isAuthenticated() {
            const authStore = useAuthStore();
            return authStore.isAuth;
        },
        setting() {
            const settingStore = useSettingStore();
            return settingStore.setting;
        }
    },
    methods: {
        goToMines() {
            this.$router.push({ name: 'games.mines' });
        },
        checkPaymentStatus() {
            const urlParams = new URLSearchParams(window.location.search);
            const paymentStatus = urlParams.get('payment_status');
            const paymentId = urlParams.get('payment_id');
            
            if (paymentStatus === 'success' && paymentId) {
                // Mostrar notificação de sucesso
                if (this.$toast && this.$toast.success) {
                    this.$toast.success('Pagamento realizado com sucesso!', {
                        duration: 5000,
                        position: 'top-right'
                    });
                }
                
                // Limpar parâmetros da URL sem recarregar a página
                const newUrl = window.location.pathname;
                window.history.replaceState({}, document.title, newUrl);
                
                // Opcional: Atualizar saldo do usuário
                if (this.isAuthenticated) {
                    // Remover chamada para store inexistente
                    // this.$store.dispatch('wallet/getWallet');
                }
            } else if (urlParams.get('payment') === 'error') {
                // Mostrar notificação de erro
                if (this.$toast && this.$toast.error) {
                    this.$toast.error('Erro no processamento do pagamento. Tente novamente.', {
                        duration: 5000,
                        position: 'top-right'
                    });
                }
                
                // Limpar parâmetros da URL
                const newUrl = window.location.pathname;
                window.history.replaceState({}, document.title, newUrl);
            }
        },

        startLastWinnersToasts() {
            if (this.lastWinnersToastTimer) return;
            if (!this.toast) return;

            const show = () => {
                const image = this.getRandomImage();
                if (!image) return;

                const amount = String(this.getRandomNumber(20, 1000)).replace(".", ",");
                const name = `${this.generateRandomString(1)}****`;

                const content = h('div', { class: 'snake-win-toast--mini' }, [
                    h('span', { class: 'snake-win-toast--mini__name' }, name),
                    h('span', { class: 'snake-win-toast--mini__text' }, ' ganhou '),
                    h('span', { class: 'snake-win-toast--mini__amount' }, `€ ${amount}`),
                    h('span', { class: 'snake-win-toast--mini__sep' }, ' • '),
                    h('span', { class: 'snake-win-toast--mini__game' }, image.name),
                ]);

                this.toast.clear();
                this.toast.success(content, {
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
                    rtl: false,
                    position: 'bottom-right'
                });
            };

            window.setTimeout(() => {
                show();
                this.lastWinnersToastTimer = window.setInterval(show, 25000);
            }, 6000);
        },

        stopLastWinnersToasts() {
            if (!this.lastWinnersToastTimer) return;
            window.clearInterval(this.lastWinnersToastTimer);
            this.lastWinnersToastTimer = null;
        },

        getCover(slug) {
            if (!slug) {
                return '';
            }
            
            if (slug.startsWith('http')) {
                return slug;
            }
            return '/storage/' + slug;
        },

        getRandomNumber(min, max) {
            return (Math.random() * (max - min) + min).toFixed(2);
        },
        
        getCache(key) {
            const cached = localStorage.getItem(key);
            if (!cached) return null;

            const { data, version } = JSON.parse(cached);
            const localCacheVersion = localStorage.getItem('global_cache_version') || '0';
            
            // Verifica apenas se a versão do cache local é a mesma da versão global
            if (version !== localCacheVersion) {
                localStorage.removeItem(key);
                return null;
            }

            return data;
        },

        setCache(key, data) {
            const localCacheVersion = localStorage.getItem('global_cache_version') || '0';
            const cacheData = {
                data,
                version: localCacheVersion
            };
            localStorage.setItem(key, JSON.stringify(cacheData));
        },
        
        // Método para verificar a versão global do cache no servidor
        async checkGlobalCacheVersion() {
            try {

                const response = await HttpApi.get('cache/version');
                const serverVersion = response.data.version;
                const localVersion = localStorage.getItem('global_cache_version') || '0';
                

                
                // Sempre atualiza a versão local para garantir sincronização
                localStorage.setItem('global_cache_version', serverVersion);
                
                // Se a versão do servidor for diferente da local, limpa o cache
                if (serverVersion !== localVersion) {

                    this.clearHomeCache();
                    return true; // Versão mudou
                }
                

                return false; // Versão não mudou
            } catch (error) {
                return false;
            }
        },

        clearHomeCache() {
            localStorage.removeItem(CACHE_KEYS.HOME_PAGE);
            localStorage.removeItem(CACHE_KEYS.BANNERS);
            localStorage.removeItem(CACHE_KEYS.ALL_GAMES);
            localStorage.removeItem(CACHE_KEYS.SLOT_GAMES);
        },

        saveFullPageCache() {
            // Salva o estado completo da página
            const pageData = {
                banners: this.banners,
                bannersHome: this.bannersHome,
                exclusive_games: this.exclusive_games,
                exclusive2_games: this.exclusive2_games,
                slot_games: this.slot_games,
                providers: this.providers,
            };
            this.setCache(CACHE_KEYS.HOME_PAGE, pageData);
        },

        loadFullPageCache() {
            const cachedPage = this.getCache(CACHE_KEYS.HOME_PAGE);
            if (cachedPage) {
                // Restaura o estado completo da página
                this.banners = cachedPage.banners;
                this.bannersHome = cachedPage.bannersHome;
                this.exclusive_games = cachedPage.exclusive_games;
                this.exclusive2_games = cachedPage.exclusive2_games;
                this.slot_games = cachedPage.slot_games;
                this.providers = cachedPage.providers;
                this.isLoading = false;
                return true;
            }
            return false;
        },

        initializeMethods: async function () {
            // Limpa o cache se solicitado
            if (this.$route.query.clearCache) {
                this.clearHomeCache();
            }
            
            // Força limpeza do cache temporariamente para testar
            this.clearHomeCache();
            
            // Verifica a versão global do cache
            await this.checkGlobalCacheVersion();


            
            // Tenta carregar do cache primeiro
            if (this.loadFullPageCache()) {

                return;
            }


            
            // Se não tem cache, carrega tudo normalmente - executa cada função independentemente
            try {
                // Executa cada função com tratamento de erro individual
                const loadFunctions = [
                    { name: 'getBanners', fn: this.getBanners },
                    { name: 'getExclusiveGames', fn: this.getExclusiveGames },
                    { name: 'getExclusive2Games', fn: this.getExclusive2Games },
                    { name: 'getSlotGames', fn: this.getSlotGames },
                    { name: 'getAllGames', fn: this.getAllGames }
                ];

                for (const { name, fn } of loadFunctions) {
                    try {
                        await fn();
                    } catch (error) {
                        // Continua executando as outras funções mesmo se uma falhar
                    }
                }

                // Salva tudo no cache após carregar
                this.saveFullPageCache();

            } catch (error) {
                // Erro geral ao carregar a página
            } finally {
                this.isLoading = false;
            }
        },
        generateRandomString(length) {
            const names = [
                "Lucas", "Mateus", "Gabriel", "Rafael", "João", "Pedro",
                "Gustavo", "Felipe", "Leonardo", "Enzo", "Miguel", "Arthur",
                "Davi", "Vinícius", "Bruno", "Thiago", "Eduardo", "Diego",
                "Marcos", "André", "Carlos", "Ricardo", "Daniel", "Vitor",
                "Paulo", "Alexandre", "Rodrigo", "Henrique", "Samuel",
                "Isabela", "Ana", "Maria", "Beatriz", "Larissa", "Mariana",
                "Camila", "Fernanda", "Júlia", "Letícia", "Carolina",
                "Gabriela", "Sofia", "Luiza", "Lara", "Natália", "Nicole",
                "Ruy", "Mafalda", "Afonso", "Duarte", "Gonçalo", "Vasco",
                "Tomás", "Salvador", "Bernardo", "Manuel", "Lourenço", "Sebastião",
                "Martim", "Hugo", "Frederico", "Nuno", "Álvaro", "Rui",
                "Valentim", "António", "Simão", "Jorge", "Fernando", "Guilherme",
                "Jaime", "Álvares", "Nicolau", "Heitor", "Olavo", "Cristóvão",
                "Estêvão", "Constança", "Amélia", "Leonor", "Matilde", "Inês",
                "Fátima", "Margarida", "Joana", "Vitória", "Constância", "Cecília",
                "Benedita", "Francisca", "Rosália", "Clarisse", "Vera", "Alzira",
                "Guiomar", "Luzia", "Filipa", "Rita", "Alice", "Anselmo",
                "Benjamim", "Caio", "Damião", "Edgar", "Flávio", "Gaspar",
                "Horácio", "Ivo", "Joaquim", "Leopoldo"
            ];
            let result = "";
            for (let i = 0; i < length; i++) {
                result += names[Math.floor(Math.random() * names.length)];
                if (i < length - 1) result += ", "; // Adiciona vírgula e espaço entre os nomes
            }
            return result;
        },
        getRandomImage() {
            // Se não tiver jogos de habilidade carregados, retorna null
            if (!this.allExclusiveGames || this.allExclusiveGames.length === 0) {
                return null;
            }

            // Pega um jogo aleatório da lista de jogos de habilidade
            const randomIndex = Math.floor(Math.random() * this.allExclusiveGames.length);
            const game = this.allExclusiveGames[randomIndex];

            return {
                url: game.cover || 'https://assets.dei.bet/default-game.png',
                name: game.game_name || 'Jogo de Habilidade'
            };
        },
        async getBanners() {
            // Banner fixo solicitado pelo usuário
            this.banners = [
                {
                    id: 1,
                    image: '01JDKT3JG0N7JGQQXMXA9J9QTY.webp',
                    link: '#',
                    type: 'carousel'
                }
            ];
            this.bannersHome = [];
        },
        async getExclusiveGames() {
            const _this = this;
            
            return await HttpApi.get('exclusive/games')
                .then(async response => {
                    if (response.data.exclusive_games && response.data.exclusive_games.length > 0) {
                        _this.exclusive_games = response.data.exclusive_games.sort((a, b) => (b.views || 0) - (a.views || 0));
                    } else {
                        _this.exclusive_games = [];
                    }
                    
                    _this.isLoading = false;
                })
                .catch(error => {
                    _this.exclusive_games = [];
                    _this.isLoading = false;
                });
        },
        async getExclusive2Games() {
            const _this = this;
            
            return await HttpApi.get('exclusive2/games')
                .then(async response => {
                    if (response.data.exclusive2_games && response.data.exclusive2_games.length > 0) {
                        _this.exclusive2_games = response.data.exclusive2_games.sort((a, b) => (b.views || 0) - (a.views || 0));
                    } else {
                        _this.exclusive2_games = [];
                    }
                    
                    _this.isLoading = false;
                })
                .catch(error => {
                    _this.exclusive2_games = [];
                    _this.isLoading = false;
                });
        },
        async getSlotGames() {
            const _this = this;
            
            // Tenta pegar do cache primeiro
            const cachedSlots = this.getCache(CACHE_KEYS.SLOT_GAMES);
            if (cachedSlots) {
                _this.slot_games = cachedSlots.sort((a, b) => (b.views || 0) - (a.views || 0));
                return;
            }

            return await HttpApi.get('slots/games')
                .then(async response => {
                    if (response.data.slot_games && response.data.slot_games.length > 0) {
                        _this.slot_games = response.data.slot_games.sort((a, b) => (b.views || 0) - (a.views || 0));
                        
                        // Salva no cache
                        this.setCache(CACHE_KEYS.SLOT_GAMES, _this.slot_games);
                    } else {
                        _this.slot_games = [];
                    }
                    
                    _this.isLoading = false;
                })
                .catch(error => {
                    _this.slot_games = [];
                    _this.isLoading = false;
                });
        },
        async getAllGames() {
            const _this = this;
            
            // Tenta pegar do cache primeiro
            const cachedGames = this.getCache(CACHE_KEYS.ALL_GAMES);
            if (cachedGames) {
                _this.providers = cachedGames;
                return;
            }

            try {
                const response = await HttpApi.get('games/all');
                _this.providers = response.data.providers;

                // Salva no cache
                this.setCache(CACHE_KEYS.ALL_GAMES, _this.providers);
            } catch (error) {
                // Erro ao carregar jogos
                console.error('Erro ao carregar todos os jogos:', error);
            }
        },
        onCarouselInit(index) {
            // Handle carousel initialization if needed

        },
        onSlideStart(index) {
            // Handle slide start if needed

        },
    },
    async created() {
        await this.initializeMethods();
    },
    watch: {

    },
    beforeDestroy() {
        if (this.$route.query.error) {
            this.clearHomeCache();
        }
    }
};
</script>
