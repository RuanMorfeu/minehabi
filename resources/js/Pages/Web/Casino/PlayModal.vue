<template>
    <a href="/">
        <div class="snake-play-back">
            <img :src="`/storage/back.png`" width="50" height="50" alt="">
        </div>
    </a>

    <div v-if="!urlGame && isLoadingWallet == false"
        class="bg-[url(https://assets.dei.bet/background.webp)] bg-cover bg-center w-svw h-svh flex justify-center items-center">

        <div class="snake-play-modal-overlay">
            <div class="snake-play-modal">
                <form v-if="playUri" class="snake-play-modal-form" @submit.prevent="submit">
                    <div class="snake-play-card">
                        <div class="snake-play-wallet">
                            <span class="snake-play-wallet-icon" aria-hidden="true">
                                <i class="fa-solid fa-wallet"></i>
                            </span>
                            <span class="snake-play-wallet-text">
                                Carteira: €{{ (wallet && wallet.total_balance != null) ? Number(wallet.total_balance).toFixed(2) : '0.00' }}
                            </span>
                        </div>

                        <div class="snake-play-bet-row">
                            <input
                                type="number"
                                name="amount"
                                id="amount"
                                v-model="amount"
                                placeholder="Valor da Aposta"
                                class="snake-play-input"
                            />

                            <button type="button" class="snake-play-mult" @click.prevent="setHalfAmount">½</button>
                            <button type="button" class="snake-play-mult" @click.prevent="setDoubleAmount">2X</button>
                        </div>

                        <button class="snake-play-cta" type="submit" id="start_game">
                            JOGAR AGORA
                        </button>

                        <p class="snake-play-hint">Clique no botão acima pra começar o jogo!</p>

                        <div v-if="!this.isAuthenticated" class="mt-4 w-full">
                            <a href="/register" class="w-full">
                                <PrimaryButton class="w-full" type="button">
                                    Criar conta
                                </PrimaryButton>
                            </a>
                        </div>
                    </div>
                </form>
                <div v-else>
                    <div class="bg-zinc-950 px-4 py-4 rounded">
                        {{ loaded == false ? 'Aguarde, carregando o game!' : 'Você precisa fazer login ou criar uma conta' }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div v-if="urlGame" class="bg-zinc-800 mb-6 px-1 rounded mx-auto w-svw h-svh">
        <iframe class="aspect-video w-svw h-svh aspect-square" :src="urlGame"></iframe>
    </div>

</template>

<script>
import { ref, onMounted, watch } from 'vue';
import PrimaryButton from "@/Components/PrimaryButton.vue";
import { useRoute } from "vue-router";
import HttpApi from "@/Services/HttpApi.js";
import InputLabel from "@/Components/InputLabel.vue";
import { useAuthStore } from "@/Stores/Auth.js";
import Swal from 'sweetalert2/dist/sweetalert2.js'
import 'sweetalert2/dist/sweetalert2.css'
import { useToast } from "vue-toastification"

export default {
    name: 'PlayModal',
    components: { PrimaryButton, InputLabel },

    data() {
        return {
            playUri: null,
            amount: null,
            identifier: null,
            game: null,
            id: null,
            loaded: false,
            urlGame: null,
            min_amount: null,
            max_amount: null,
            game_max_amount: null,
            wallet: null,
            isLoadingWallet: true,
            valor_win: 0,
        };
    },

    setup() {
        const route = useRoute();
        const id = ref(route.params.slug);
        const valor_win = ref(route.params.valor);
        const type = ref(route.params.type);
        const toast = useToast();
        
        return { id, valor_win, type, toast };
    },

    mounted() {
        // Desabilitar rolagem do body quando o iframe for exibido
        if (this.urlGame) {
            document.body.style.overflow = 'hidden';
        } else {
            document.body.style.overflow = 'auto';
        }

        // Listener para postMessage dos jogos (fallback CORS)
        window.addEventListener('message', (event) => {
            if (event.data && event.data.type === 'gameWin') {
                console.log('🎮 Recebido postMessage de vitória:', event.data);
                window.location.href = event.data.redirectUrl;
            }
        });

        this.id = this.id;
        this.loadGame(this.id);
    },

    watch: {
        urlGame(newValue) {
            if (newValue) {
                document.body.style.overflow = 'hidden'; // Desabilita rolagem
            } else {
                document.body.style.overflow = 'auto'; // Habilita rolagem novamente
            }
        }
    },

    computed: {
        isAuthenticated() {
            const authStore = useAuthStore();
            return authStore.isAuth;
        },
    },
    methods: {
    setHalfAmount() {
        const current = parseFloat(this.amount);
        const base = Number.isFinite(current) && current > 0 ? current : (this.min_amount ?? 1);
        let next = base / 2;

        if (this.min_amount != null && next < parseFloat(this.min_amount)) {
            next = parseFloat(this.min_amount);
        }
        if (this.isAuthenticated && this.max_amount != null && next > parseFloat(this.max_amount)) {
            next = parseFloat(this.max_amount);
        }

        this.amount = Number(next.toFixed(2));
    },

    setDoubleAmount() {
        const current = parseFloat(this.amount);
        const base = Number.isFinite(current) && current > 0 ? current : (this.min_amount ?? 1);
        let next = base * 2;

        if (this.isAuthenticated && this.max_amount != null && next > parseFloat(this.max_amount)) {
            next = parseFloat(this.max_amount);
        }

        this.amount = Number(next.toFixed(2));
    },

    mostrarModal(valor, type) {

        
        // Frases possíveis para ganhos
        const frasesGanhos = [
            "Vamos aumentar a aposta?",
            "Vamos apostar ganhando dinheiro de verdade?"
        ];

        // Frases possíveis para perdas
        const frasesPerdas = [
            "Quase lá, continue tentando!",
            "Não desista, a próxima pode ser sua!",
            "Que tal tentar novamente?",
            "A vitória está logo ali, tente mais uma vez!"
        ];

        // Selecionar uma frase aleatória para o tipo de modal
        const fraseAleatoria = (type === 'win') 
            ? frasesGanhos[Math.floor(Math.random() * frasesGanhos.length)] 
            : frasesPerdas[Math.floor(Math.random() * frasesPerdas.length)];

        if (type === 'win') {
            // Gerar a porcentagem aleatória entre 75% e 92%
            const porcentagem = Math.floor(Math.random() * (92 - 75 + 1)) + 75;
            
            // Configuração do modal para vitória
            const swalConfig = {
                title: `LUCRO DE € ${parseFloat(valor).toFixed(2)}`,
                html: `Fantástico! Você realmente sabe como fazer, <strong>superando ${porcentagem}%</strong> dos usuários!<br><br><br><strong>${fraseAleatoria}</strong>`,
                icon: "success",
                confirmButtonText: 'Continuar',
                allowOutsideClick: false
            };
            
            // Exibir o modal com as novas mensagens
            Swal.fire(swalConfig).then((result) => {

                if (result.isConfirmed) {
                    // Redirecionar para a rota /register após clicar no botão "OK"
                    window.location.href = '/register';
                }
            }).catch(error => {
                console.error('Erro ao exibir o modal de vitória:', error);
            });
        } else {
            // Configuração do modal para derrota
            const swalConfig = {
                title: "Essa foi quase!",
                html: `Você esteve tão perto! Superou a maioria dos jogadores!<br><br><br><strong>${fraseAleatoria}</strong>`,
                icon: "error",
                confirmButtonText: 'Tentar novamente',
                allowOutsideClick: false
            };
            
            // Exibir o modal com a frase para perdas
            Swal.fire(swalConfig).catch(error => {
                console.error('Erro ao exibir o modal de derrota:', error);
            });
        }
    },

    getWallet: async function() {
        const _this = this;

        await HttpApi.get('profile/wallet')
            .then(response => {
                _this.wallet = response.data.wallet;
                _this.max_amount = _this.wallet.total_balance;
                _this.isLoadingWallet = false;
            })
            .catch(error => {
                Object.entries(JSON.parse(error.request.responseText)).forEach(([key, value]) => {
                    if(value == 'unauthenticated') {
                        localStorage.clear();
                        clearInterval(this.processInterval);
                    }
                });

                _this.isLoadingWallet = false;
            });
    },

    loadGame(game) {

        
        // Detecta se é um jogo exclusive2 baseado na rota atual
        const isExclusive2 = this.$route.name === 'playModal2';
        const apiEndpoint = isExclusive2 ? '/vgames2/modal/' + game : '/modalGame/' + game;
        





        HttpApi.get(apiEndpoint)
            .then((response) => {

                
                // Para jogos exclusive2, a estrutura é diferente
                if (isExclusive2 && response.data.game) {
                    this.game = response.data.game.name;
                    this.identifier = response.data.game.uuid;
                    this.playUri = response.data.game.uuid;
                } else {
                    this.game = response.data.name;
                    this.identifier = response.data.uuid;
                    this.playUri = response.data.uuid;
                }
                // Não definir max_amount aqui, pois deve ser o saldo da carteira
                this.min_amount = 1;

                if(this.isAuthenticated){
                    let gameData = isExclusive2 && response.data.game ? response.data.game : response.data;
                    if(gameData.min_amount != undefined || gameData.min_amount != null){
                        this.min_amount = gameData.min_amount;
                    }
                    // Carregar limite máximo de aposta do jogo
                    if(gameData.max_amount != undefined && gameData.max_amount != null){
                        this.game_max_amount = gameData.max_amount;
                    }
                }
                
                this.loaded = true;
            })
            .catch(error => {
                console.error("Erro ao carregar o game:", error);
            });
    },

    async submit() {


        if(this.isAuthenticated && parseFloat(this.amount) > parseFloat(this.max_amount)){

            this.toast.error(`Saldo insuficiente. Seu saldo atual é de ${this.max_amount}. Faça uma recarga para jogar com este valor.`, {
                timeout: 5000,
                closeOnClick: true,
                pauseOnFocusLoss: true,
                pauseOnHover: true,
                draggable: true,
                draggablePercent: 0.6,
                showCloseButtonOnHover: false,
                hideProgressBar: false,
                closeButton: "button",
                icon: true,
                rtl: false,
                onClick: () => window.location.href = '/deposit'
            });
            return;
        }

        if (this.amount <= 0) {
            alert('O valor deve ser maior que zero!');
            return;
        }

        if(this.amount < this.min_amount)
        {
            alert(`O valor minimo para o jogo é ${this.min_amount} !`);
            return;
        }

        // Validação do limite máximo de aposta do jogo
        if(this.isAuthenticated && this.game_max_amount && parseFloat(this.amount) > parseFloat(this.game_max_amount)){
            alert(`O valor máximo de aposta para este jogo é ${this.game_max_amount} !`);
            return;
        }

        document.querySelector('#start_game').disabled = true;
        
        // Detecta se é um jogo exclusive2 baseado na rota atual
        const isExclusive2 = this.$route.name === 'playModal2';
        const openGameEndpoint = isExclusive2 ? `/vgames2/show/${this.identifier}/${this.amount}` : `/vgames/openGame/${this.identifier}/${this.amount}`;
        

        
        await HttpApi.get(openGameEndpoint).then((res) => {
            const response = res.data;

            this.urlGame = response.gameUrl;
            document.querySelector('#start_game').disabled = false;
        }).catch(error => { 
            document.querySelector('#start_game').disabled = false;
            
            // Tratar erros específicos do backend
            if (error.response && error.response.data && error.response.data.error) {
                const errorMessage = error.response.data.error;
                
                // Verificar se é erro de aposta máxima
                if (errorMessage.includes('Valor máximo para apostas')) {
                    this.toast.error(errorMessage, {
                        timeout: 5000,
                        closeOnClick: true,
                        pauseOnFocusLoss: true,
                        pauseOnHover: true,
                        draggable: true,
                        draggablePercent: 0.6,
                        showCloseButtonOnHover: false,
                        hideProgressBar: false,
                        closeButton: "button",
                        icon: true,
                        rtl: false
                    });
                } else {
                    // Outros erros (saldo insuficiente, valor mínimo, etc.)
                    this.toast.error(errorMessage, {
                        timeout: 5000,
                        closeOnClick: true,
                        pauseOnFocusLoss: true,
                        pauseOnHover: true,
                        draggable: true,
                        draggablePercent: 0.6,
                        showCloseButtonOnHover: false,
                        hideProgressBar: false,
                        closeButton: "button",
                        icon: true,
                        rtl: false
                    });
                }
            } else {
                // Erro genérico
                this.toast.error('Erro ao iniciar o jogo. Tente novamente.', {
                    timeout: 5000,
                    closeOnClick: true,
                    pauseOnFocusLoss: true,
                    pauseOnHover: true,
                    draggable: true,
                    draggablePercent: 0.6,
                    showCloseButtonOnHover: false,
                    hideProgressBar: false,
                    closeButton: "button",
                    icon: true,
                    rtl: false
                });
            }
        })
    },
},

async created() {


    // Aguardar um pequeno intervalo para garantir que o DOM esteja pronto
    setTimeout(() => {
        if(this.type == 'win' && this.valor_win > 0){

            this.mostrarModal(this.valor_win, 'win');
        } else if(this.type == 'loss' && this.valor_win > 0){

            this.mostrarModal(this.valor_win, 'loss');
        } else {

        }
    }, 500);

    if(this.isAuthenticated){
        await this.getWallet();
    }else{
        this.isLoadingWallet = false;
    }
}
};
</script>

<style scoped>
/* Desabilitar a rolagem da página enquanto o iframe estiver visível */
body {
    overflow: hidden; /* Desabilita rolagem */
}

/* Certifique-se de que o contêiner do iframe ocupe toda a tela */
.bg-zinc-800 {
    display: flex;
    justify-content: center;
    align-items: center;
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    z-index: 9999; /* Para garantir que fique na frente de outros elementos */
    overflow: hidden; /* Impede a rolagem */
}

/* Tornando o iframe responsivo e fixo na tela */
iframe {
    width: 100%;
    height: 100%;
    border: none; /* Remove a borda do iframe */
    object-fit: cover; /* Faz o conteúdo do iframe se ajustar sem distorções */
}
</style>
