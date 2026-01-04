<template>
    <div class="relative z-30">
        <div class="w-full relative">
            <div class="flex items-center">
                <input
                    ref="searchInput"
                    v-model="searchTerm"
                    class="w-full bg-transparent text-white outline-none placeholder-zinc-400"
                    type="text"
                    placeholder="Buscar jogos..."
                />
                <button class="px-4 text-zinc-400 hover:text-blue-400 transition-colors">
                    <i class="fa-solid" :class="isLoading ? 'fa-spinner fa-spin' : 'fa-magnifying-glass'"></i>
                </button>
                <button 
                    v-show="searchTerm || games.length > 0"
                    @click="closeSearch"
                    class="px-4 text-zinc-400 hover:text-red-400 transition-colors"
                >
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            
            <!-- Results Section -->
            <div v-if="games.length > 0" class="absolute w-full mt-2 bg-zinc-800 rounded-lg border border-zinc-700 shadow-xl max-h-[70vh] overflow-y-auto">
                <div class="w-full grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4 p-4">
                    <div v-for="(game, index) in games" :key="index" class="transition-transform hover:scale-105">
                        <CassinoGameCard
                            :index="index"
                            :title="game.game_name"
                            :cover="game.cover"
                            :gamecode="game.game_code"
                            :type="game.distribution"
                            :game="game"
                        />
                    </div>
                </div>
            </div>

            <!-- No Results Message -->
            <div v-if="noResults" class="absolute w-full mt-2 bg-zinc-800 rounded-lg border border-zinc-700 p-4 text-center text-zinc-400">
                Nenhum jogo encontrado
            </div>

            <!-- Error Message -->
            <div v-if="error" class="absolute w-full mt-2 bg-red-900/50 rounded-lg border border-red-700 p-4 text-center text-red-200">
                {{ error }}
            </div>
        </div>
    </div>
</template>

<script>
import { ref, watch } from 'vue';
import HttpApi from "@/Services/HttpApi.js";
import CassinoGameCard from "@/Pages/Cassino/Components/CassinoGameCard.vue";

export default {
    name: 'SearchBarComponent',
    components: {
        CassinoGameCard
    },
    data() {
        return {
            searchTerm: '',
            games: [],
            isLoading: false,
            error: '',
            noResults: false
        }
    },
    methods: {
        closeSearch() {
            this.searchTerm = '';
            this.games = [];
            this.noResults = false;
        },
        debounce(fn, delay) {
            let timeout;
            return function() {
                const args = arguments;
                clearTimeout(timeout);
                timeout = setTimeout(function() {
                    fn.apply(null, args);
                }, delay);
            };
        },
        async loadSearchBar(term) {
            if (!term || term.length < 2) {
                this.games = [];
                this.noResults = false;
                this.error = '';
                return;
            }

            this.isLoading = true;
            this.error = '';

            try {
                const response = await HttpApi.get(`/search/games?searchTerm=${encodeURIComponent(term)}`);
                
                if (this.searchTerm === term) {
                    this.games = response.data.games.data || [];
                    this.noResults = this.games.length === 0;
                }
            } catch (e) {
                if (this.searchTerm === term) {
                    this.error = 'Ocorreu um erro ao buscar os jogos';
                    this.games = [];
                }
            } finally {
                if (this.searchTerm === term) {
                    this.isLoading = false;
                }
            }
        }
    },
    created() {
        this.debouncedSearch = this.debounce(this.loadSearchBar, 300);
    },
    watch: {
        searchTerm(newValue) {
            if (!newValue) {
                this.games = [];
                this.noResults = false;
                this.error = '';
                return;
            }
            this.debouncedSearch(newValue);
        }
    }
};
</script>
