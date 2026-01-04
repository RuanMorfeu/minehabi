<template>

    <div v-if="!urlGame && isLoadingWallet == false"
        class="bg-cover bg-center w-svw h-svh flex justify-center items-center"
        style="background-image: url('/assets/images/back1.webp');">
        <form v-if="playUri" class="" @submit.prevent="submit">
            <div class="max-w-md w-full mx-auto">
                <div class="glassmorphism w-full py-8 rounded-3xl px-6 md:px-8 shadow-xl border border-white/10 backdrop-blur-lg relative">
                    <!-- Elemento decorativo superior -->
                    <div class="absolute -top-20 -right-20 w-40 h-40 bg-blue-500/20 rounded-full blur-2xl"></div>
                    <div class="absolute -bottom-20 -left-20 w-40 h-40 bg-blue-500/20 rounded-full blur-2xl"></div>
                    
                    <!-- Conteúdo do modal -->
                    <div class="relative z-10">
                        <div class="text-center mb-5">
                            <div class="bg-blue-600 text-white text-sm font-bold py-2 px-4 rounded-full inline-block mb-4">
                                NOVO! Teste Grátis para Novos Jogadores
                            </div>
                            
                            <p class="text-gray-300 mb-1">Pronto para jogar agora mesmo!</p>
                        </div>
                        
                        <div class="text-center mb-5">
                            <p class="text-gray-400 text-sm mb-1">SEU SALDO INICIAL:</p>
                            <p class="text-4xl md:text-5xl font-bold text-yellow-300 mb-3">€10.00</p>
                            <p class="text-lg md:text-xl font-bold text-white mb-2">COMECE A JOGAR</p>
                            <p class="text-gray-300 text-sm mb-3">Divirta-se e lucre com o {{ game }}!</p>
                        </div>
                        
                        <div class="mt-4 w-full">
                            <button 
                                class="custom-button w-full py-4 text-lg font-bold rounded-xl flex items-center justify-center gap-2 pulse-btn" 
                                type="submit" 
                                id="start_game"
                            >
                                <span class="text-xl">▶️</span> Iniciar Jogo!
                            </button>
                        </div>
                        
                        <div class="mt-4 text-center">
                            <p class="text-gray-400 text-xs mt-2">*Oferta válida apenas para novos jogadores.</p>
                        </div>
                    </div>
                </div>
            </div>
        </form>
        <div v-else>
            <div class="bg-zinc-950 px-4 py-4 rounded">
                {{ loaded == false ? 'Aguarde, carregando o game!' : 'Você precisa fazer login ou criar uma conta' }}
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

export default {
    name: 'DemoPlayModal2',
    components: { PrimaryButton, InputLabel },

    data() {
        return {
            // ID do jogo será definido pelos parâmetros da rota
            playUri: null,
            amount: 10, // Valor padrão pré-configurado
            identifier: null,
            game: null,
            loaded: false,
            urlGame: null,
            min_amount: 1,
            max_amount: 1000,
            isLoadingWallet: false,
            valor_win: 0,
            type: null, // Para armazenar o tipo (win/loss)
        };
    },
    
    setup() {
        const route = useRoute();
        const type = ref(route.params.type);
        const valor_win = ref(route.params.valor);
        const gameId = ref(route.params.gameId);
        
        return { type, valor_win, gameId };
    },

    computed: {
        
    },

    mounted() {
        // Verificar se há parâmetros de vitória na URL
        if (this.type && this.valor_win) {
            this.mostrarModal(this.valor_win, this.type);
        }
        
        // Carregar o jogo automaticamente usando o gameId dos parâmetros da rota
        const gameIdToLoad = this.gameId || '1'; // Fallback para o primeiro jogo se não houver gameId
        this.loadGame(gameIdToLoad);
        
        // Adicionar listener para mensagens do iframe
        window.addEventListener('message', this.handleGameMessage);
    },

    beforeUnmount() {
        // Remover listener quando o componente for desmontado
        window.removeEventListener('message', this.handleGameMessage);
    },

    watch: {
        urlGame(newValue) {
            if (newValue) {
                // Desabilitar a rolagem da página quando o iframe estiver visível
                document.body.style.overflow = 'hidden';
            } else {
                // Reabilitar a rolagem quando o iframe não estiver visível
                document.body.style.overflow = 'auto';
            }
        }
    },

    methods: {
        mostrarModal(valor, type) {
            let title, text, icon;
            
            if (type === 'win') {
                title = '🎉 Parabéns!';
                text = `Você ganhou €${parseFloat(valor).toFixed(2)}!`;
                icon = 'success';
            } else {
                title = '😔 Que pena!';
                text = 'Não foi desta vez, mas continue tentando!';
                icon = 'info';
            }

            Swal.fire({
                title: title,
                text: text,
                icon: icon,
                confirmButtonText: 'Jogar Novamente',
                cancelButtonText: 'Sair',
                showCancelButton: true,
                customClass: {
                    popup: 'swal-popup-custom',
                    title: 'swal-title-custom',
                    content: 'swal-content-custom',
                    confirmButton: 'swal-confirm-custom',
                    cancelButton: 'swal-cancel-custom'
                },
                background: 'linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%)',
                color: '#ffffff',
                backdrop: `
                    rgba(0,0,0,0.8)
                    url("/assets/images/confetti.gif")
                    center center
                    no-repeat
                `,
                showClass: {
                    popup: 'animate__animated animate__bounceIn'
                },
                hideClass: {
                    popup: 'animate__animated animate__bounceOut'
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    // Recarregar a página para jogar novamente
                    window.location.reload();
                } else {
                    // Redirecionar para a página inicial
                    window.location.href = '/';
                }
            });

            // Adicionar estilos CSS personalizados
            const style = document.createElement('style');
            style.textContent = `
                .swal-popup-custom {
                    border-radius: 20px !important;
                    border: 2px solid rgba(255, 255, 255, 0.1) !important;
                    box-shadow: 0 25px 50px rgba(0, 0, 0, 0.5) !important;
                }
                .swal-title-custom {
                    font-size: 2rem !important;
                    font-weight: bold !important;
                    margin-bottom: 1rem !important;
                }
                .swal-content-custom {
                    font-size: 1.2rem !important;
                    margin-bottom: 2rem !important;
                }
                .swal-confirm-custom {
                    background: linear-gradient(45deg, #4CAF50, #45a049) !important;
                    border: none !important;
                    border-radius: 25px !important;
                    padding: 12px 30px !important;
                    font-size: 1.1rem !important;
                    font-weight: bold !important;
                    margin: 0 10px !important;
                    transition: all 0.3s ease !important;
                }
                .swal-confirm-custom:hover {
                    transform: translateY(-2px) !important;
                    box-shadow: 0 5px 15px rgba(76, 175, 80, 0.4) !important;
                }
                .swal-cancel-custom {
                    background: linear-gradient(45deg, #f44336, #da190b) !important;
                    border: none !important;
                    border-radius: 25px !important;
                    padding: 12px 30px !important;
                    font-size: 1.1rem !important;
                    font-weight: bold !important;
                    margin: 0 10px !important;
                    transition: all 0.3s ease !important;
                }
                .swal-cancel-custom:hover {
                    transform: translateY(-2px) !important;
                    box-shadow: 0 5px 15px rgba(244, 67, 54, 0.4) !important;
                }
                @keyframes pulse {
                    0% { transform: scale(1); }
                    50% { transform: scale(1.05); }
                    100% { transform: scale(1); }
                }
                .swal-confirm-custom {
                    animation: pulse 2s infinite !important;
                }
            `;
            document.head.appendChild(style);

            // Remover o estilo após 10 segundos para não acumular
            setTimeout(() => {
                if (style.parentNode) {
                    style.parentNode.removeChild(style);
                }
            }, 10000);
        },

        loadGame(game) {
            console.log(`Carregando o game ${game}`);

            HttpApi.get('/vgames2/modal/' + game)
                .then((response) => {
                    this.game = response.data.game.name;
                    this.identifier = response.data.game.uuid;
                    this.playUri = response.data.game.uuid;
                    this.min_amount = response.data.game.min_amount || 1;
                    this.loaded = true;
                })
                .catch(error => {
                    console.error("Erro ao carregar o game:", error);
                });
        },

        async submit() {
            // Definir um valor padrão para a aposta
            this.amount = 10; // Valor fixo para a versão demo

            document.querySelector('#start_game').disabled = true;
            await HttpApi.get(`/vgames2/show/${this.identifier}/${this.amount}`).then((res) => {
                const response = res.data;
                console.log(response);
                
                // Modificar a URL do jogo para usar /demo-game2 como baseurl
                const gameUrl = new URL(response.gameUrl);
                const params = new URLSearchParams(gameUrl.search);
                
                // Substituir o baseurl para apontar para /demo-game2
                const baseurl = window.location.origin + '/demo-game2';
                params.set('baseurl', baseurl);
                
                // Reconstruir a URL com os parâmetros modificados
                gameUrl.search = params.toString();
                this.urlGame = gameUrl.toString();
                
                // Rastreamento do Facebook Pixel para o evento de iniciar jogo
                if (window.fbq) {
                    try {
                        // Registra o evento de início de jogo no Facebook Pixel
                        fbq('track', 'StartTrial', {
                            content_name: this.game || 'Demo Game 2',
                            content_ids: [this.gameId],
                            content_type: 'game_start',
                            value: this.amount,
                            currency: 'EUR'
                        });
                        
                        // Envio para a API de Conversões do Facebook
                        // Obter o Access Token da variável global definida no layout principal
                        const accessToken = window.facebookAccessToken || 'EAAO9hYqUMOYBO428jfPpkLxvSrapZAfFeFkunEg23z7e5GmAHt3LX386zZCDvxdxXpf4M41KnwuXl9kZCqSW6sShtD5vrcZCRYxzBKQv4ba8g65yE0ll9zh5D2ZASZABb1BkWhl0qXi5ZAbQalxbtWhVH3LsrzTZBKomAFolxzvb1MClKULBBwwHLM3YJPXhcyVftQZDZD';
                        // Obter o Pixel ID da variável global definida no layout principal
                        const pixelId = window.facebookPixelId || '641305108716070';
                        
                        // Envio da conversão para a API do Facebook
                        fetch(`https://graph.facebook.com/v18.0/${pixelId}/events?access_token=${accessToken}`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify({
                                data: [{
                                    event_name: 'StartTrial',
                                    event_time: Math.floor(Date.now() / 1000),
                                    action_source: 'website',
                                    event_source_url: window.location.href,
                                    user_data: {
                                        client_ip_address: '{{_server.REMOTE_ADDR}}',
                                        client_user_agent: navigator.userAgent
                                    },
                                    custom_data: {
                                        content_name: this.game || 'Demo Game 2',
                                        content_ids: [this.gameId],
                                        content_type: 'game_start',
                                        value: this.amount,
                                        currency: 'EUR'
                                    }
                                }]
                            })
                        }).catch(err => {
                            console.error('Erro ao enviar conversão para o Facebook:', err);
                        });
                        
                        console.log('Evento de início de jogo enviado para o Facebook');
                    } catch (fbError) {
                        console.error('Erro ao rastrear evento de início de jogo no Facebook:', fbError);
                    }
                }
                
                document.querySelector('#start_game').disabled = false;
            }).catch(error => { 
                document.querySelector('#start_game').disabled = false; 
            })
        },
        
        handleGameMessage(event) {
            // Verificar se a mensagem é do iframe do jogo
            console.log('Mensagem recebida:', event.data);
            
            // Verificar se a mensagem contém informações de vitória/derrota
            if (event.data && typeof event.data === 'object') {
                if (event.data.type === 'gameResult') {
                    const { result, amount } = event.data;
                    
                    // Mostrar modal baseado no resultado
                    if (result === 'win') {
                        this.mostrarModal(amount, 'win');
                    } else if (result === 'loss') {
                        this.mostrarModal(0, 'loss');
                    }
                }
            }
        },
    },

    created() {
        const authStore = useAuthStore();
        
        // Verificar se o usuário está autenticado
        if (authStore.user) {
            // Se estiver autenticado, redirecionar para o modal normal
            this.$router.push('/modal2/' + this.gameId);
            return;
        }
        
        // Verificar se há parâmetros de vitória na URL
        const urlParams = new URLSearchParams(window.location.search);
        const winAmount = urlParams.get('win_amount');
        const gameResult = urlParams.get('result');
        
        if (winAmount !== null || gameResult) {
            const type = gameResult || (parseFloat(winAmount) > 0 ? 'win' : 'loss');
            this.mostrarModal(winAmount || 0, type);
        }
    },
};
</script>

<style scoped>
/* Desabilitar a rolagem da página enquanto o iframe estiver visível */
body.iframe-active {
    overflow: hidden;
}

.glassmorphism {
    background: rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.2);
}

.custom-button {
    background: linear-gradient(45deg, #4CAF50, #45a049);
    color: white;
    border: none;
    transition: all 0.3s ease;
}

.custom-button:hover {
    background: linear-gradient(45deg, #45a049, #4CAF50);
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(76, 175, 80, 0.4);
}

.pulse-btn {
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0% {
        transform: scale(1);
    }
    50% {
        transform: scale(1.05);
    }
    100% {
        transform: scale(1);
    }
}

/* Animações para entrada do modal */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.glassmorphism {
    animation: fadeInUp 0.6s ease-out;
}
</style>
