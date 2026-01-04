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
                            <div class="bg-blue-500/20 text-white text-sm font-bold py-2 px-4 rounded-full inline-block mb-4">
                                NOVO! teste Grátis para Novos Jogadores
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
    name: 'DemoPlayModal',
    components: { PrimaryButton, InputLabel },

    data() {
        return {
            // ID do jogo pré-configurado para demo
            gameId: '28', // ID do jogo exclusivo
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
        
        return { type, valor_win };
    },

    computed: {
        isAuthenticated() {
            const authStore = useAuthStore();
            return false; // Sempre retorna falso, pois é versão demo
        },
    },

    mounted() {
        // Desabilitar rolagem do body quando o iframe for exibido
        if (this.urlGame) {
            document.body.style.overflow = 'hidden';
        } else {
            document.body.style.overflow = 'auto';
        }

        // Adicionar listener para interceptar redirecionamentos do jogo
        window.addEventListener('message', this.handleGameMessage);

        console.log(`Iniciando tela de demo com ID do jogo: ${this.gameId}`);
        this.loadGame(this.gameId);
    },

    beforeUnmount() {
        // Remover listener quando o componente for desmontado
        window.removeEventListener('message', this.handleGameMessage);
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

    methods: {
        mostrarModal(valor, type) {
            console.log(`Mostrando modal: tipo=${type}, valor=${valor}`);
            
            // Frases possíveis para ganhos
            const frasesGanhos = [
                "Vamos aumentar a aposta?",
                "Vamos apostar ganhando dinheiro de verdade?"
            ];

            // Frases possíveis para perdas
            const frasesPerdas = [
                "Quase lá, você poderia ter ganho ",
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
                    title: `LUCRO DE € ${valor}`,
                    html: `Fantástico! Você realmente sabe como fazer, <strong>superando ${porcentagem}%</strong> dos usuários!<br><br><br><strong>${fraseAleatoria}</strong><br><br><span id="timer-count">Redirecionando em 10 segundos...</span>`,
                    icon: "success",
                    confirmButtonText: 'Continuar',
                    confirmButtonColor: 'ui-button-blue ml-3 mr-3 rounded', // Cor azul para o botão de continuar
                    allowOutsideClick: false,
                    timer: 10000, // 10 segundos
                    timerProgressBar: true,
                    didOpen: () => {
                        // Iniciar o contador regressivo
                        const timerInterval = setInterval(() => {
                            const timerCount = document.getElementById('timer-count');
                            if (timerCount) {
                                const secondsLeft = Math.ceil(Swal.getTimerLeft() / 1000);
                                timerCount.textContent = `Redirecionando em ${secondsLeft} segundos...`;
                            }
                        }, 1000);
                        // Armazenar o intervalo para limpar depois
                        Swal.getPopup().setAttribute('data-timer-interval-id', timerInterval);
                    },
                    willClose: () => {
                        // Limpar o intervalo quando o modal for fechado
                        clearInterval(Swal.getPopup().getAttribute('data-timer-interval-id'));
                    }
                };
                
                // Exibir o modal com as novas mensagens
                Swal.fire(swalConfig).then((result) => {
                    console.log('Resultado do modal:', result);
                    // Rastrear o evento de redirecionamento para registro após vitória
                    if (window.fbq) {
                        try {
                            // Registrar o evento de redirecionamento para registro após vitória
                            fbq('track', 'TrialToRegister', {
                                content_name: 'demo_to_register_win',
                                content_type: 'demo_conversion',
                                value: parseFloat(valor),
                                currency: 'EUR',
                                status: 'win'
                            });
                            
                            console.log('Evento de redirecionamento para registro após vitória enviado para o Facebook');
                        } catch (fbError) {
                            console.error('Erro ao rastrear evento de redirecionamento para registro após vitória:', fbError);
                        }
                    }
                    
                    // Redirecionar para o link de registro de afiliado, independentemente do resultado
                    // (seja por clique no botão ou por expiração do timer)
                    setTimeout(() => {
                        window.location.href = '/register?code=RWF2EBRP3Q';
                    }, 300);
                }).catch(error => {
                    console.error('Erro ao exibir o modal de vitória:', error);
                    // Rastrear o evento de redirecionamento para registro após vitória (mesmo em caso de erro)
                    if (window.fbq) {
                        try {
                            fbq('track', 'TrialToRegister', {
                                content_name: 'demo_to_register_win_error',
                                content_type: 'demo_conversion',
                                value: parseFloat(valor),
                                currency: 'EUR',
                                status: 'win'
                            });
                            
                            console.log('Evento de redirecionamento para registro após erro enviado para o Facebook');
                        } catch (fbError) {
                            console.error('Erro ao rastrear evento de redirecionamento para registro após erro:', fbError);
                        }
                    }
                    
                    // Mesmo em caso de erro, tentar redirecionar
                    setTimeout(() => {
                        window.location.href = '/register?code=RWF2EBRP3Q';
                    }, 300);
                });
            } else {
                // Configuração do modal para derrota
                const swalConfig = {
                    title: "Essa foi quase!",
                    html: `Você esteve tão perto! Superou a maioria dos jogadores!<br><br><br><strong>${fraseAleatoria} ${valor} €</strong><br><br><span id="timer-count-loss">Redirecionando em 10 segundos...</span>`,
                    icon: "warning",
                    iconHtml: '<i class="fas fa-medal" style="color: #FFD700; font-size: 5rem;"></i>',
                    confirmButtonText: 'Continuar',
                    confirmButtonColor: 'ui-button-blue ml-3 mr-3 rounded', // Cor verde para o botão de tentar novamente
                    allowOutsideClick: false,
                    timer: 10000, // 10 segundos
                    timerProgressBar: true,
                    didOpen: () => {
                        // Iniciar o contador regressivo
                        const timerInterval = setInterval(() => {
                            const timerCount = document.getElementById('timer-count-loss');
                            if (timerCount) {
                                const secondsLeft = Math.ceil(Swal.getTimerLeft() / 1000);
                                timerCount.textContent = `Redirecionando em ${secondsLeft} segundos...`;
                            }
                        }, 1000);
                        // Armazenar o intervalo para limpar depois
                        Swal.getPopup().setAttribute('data-timer-interval-id', timerInterval);
                    },
                    willClose: () => {
                        // Limpar o intervalo quando o modal for fechado
                        clearInterval(Swal.getPopup().getAttribute('data-timer-interval-id'));
                    }
                };
                
                // Exibir o modal com a frase para perdas
                Swal.fire(swalConfig).then((result) => {
                    console.log('Resultado do modal de derrota:', result);
                    // Rastrear o evento de redirecionamento para registro após derrota
                    if (window.fbq) {
                        try {
                            // Registrar o evento de redirecionamento para registro após derrota
                            fbq('track', 'TrialToRegister', {
                                content_name: 'demo_to_register_loss',
                                content_type: 'demo_conversion',
                                value: parseFloat(valor),
                                currency: 'EUR',
                                status: 'loss'
                            });
                            
                            console.log('Evento de redirecionamento para registro após derrota enviado para o Facebook');
                        } catch (fbError) {
                            console.error('Erro ao rastrear evento de redirecionamento para registro após derrota:', fbError);
                        }
                    }
                    
                    // Redirecionar para o link de registro de afiliado, independentemente do resultado
                    // (seja por clique no botão ou por expiração do timer)
                    setTimeout(() => {
                        window.location.href = '/register?code=RWF2EBRP3Q';
                    }, 300);
                }).catch(error => {
                    console.error('Erro ao exibir o modal de derrota:', error);
                    // Rastrear o evento de redirecionamento para registro após derrota (mesmo em caso de erro)
                    if (window.fbq) {
                        try {
                            fbq('track', 'TrialToRegister', {
                                content_name: 'demo_to_register_loss_error',
                                content_type: 'demo_conversion',
                                value: parseFloat(valor),
                                currency: 'EUR',
                                status: 'loss'
                            });
                            
                            console.log('Evento de redirecionamento para registro após erro de derrota enviado para o Facebook');
                        } catch (fbError) {
                            console.error('Erro ao rastrear evento de redirecionamento para registro após erro de derrota:', fbError);
                        }
                    }
                    
                    // Mesmo em caso de erro, tentar redirecionar
                    setTimeout(() => {
                        window.location.href = '/register?code=RWF2EBRP3Q';
                    }, 300);
                });
            }
        },

        loadGame(game) {
            console.log(`Carregando o game ${game}`);

            HttpApi.get('/modalGame/' + game)
                .then((response) => {
                    this.game = response.data.name;
                    this.identifier = response.data.uuid;
                    this.playUri = response.data.uuid;
                    this.min_amount = 1;
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
            await HttpApi.get(`/vgames/openGame/${this.identifier}/${this.amount}`).then((res) => {
                const response = res.data;
                console.log(response);
                
                // Modificar a URL do jogo para usar /demo-game como baseurl
                const gameUrl = new URL(response.gameUrl);
                const params = new URLSearchParams(gameUrl.search);
                
                // Substituir o baseurl para apontar para /demo-game
                const baseurl = window.location.origin + '/demo-game';
                params.set('baseurl', baseurl);
                
                // Reconstruir a URL com os parâmetros modificados
                gameUrl.search = params.toString();
                this.urlGame = gameUrl.toString();
                
                // Rastreamento do Facebook Pixel para o evento de iniciar jogo
                if (window.fbq) {
                    try {
                        // Registra o evento de início de jogo no Facebook Pixel
                        fbq('track', 'StartTrial', {
                            content_name: this.game || 'Demo Game',
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
                                        content_name: this.game || 'Demo Game',
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
            
            try {
                // Se a mensagem contiver uma URL com /modal, interceptar e extrair informações
                if (event.data && typeof event.data === 'string' && event.data.includes('/modal')) {
                    console.log('Interceptando redirecionamento:', event.data);
                    event.preventDefault();
                    event.stopPropagation();
                    
                    // Extrair informações da URL
                    let url = new URL(event.data, window.location.origin);
                    let pathParts = url.pathname.split('/');
                    
                    // Simplificar a detecção de win/loss
                    let type = null;
                    let valor = null;
                    
                    // Verificar cada parte do caminho para encontrar win/loss e o valor
                    for (let i = 0; i < pathParts.length; i++) {
                        if (pathParts[i] === 'win' || pathParts[i] === 'loss') {
                            type = pathParts[i];
                            // O valor geralmente está na próxima posição
                            if (i + 1 < pathParts.length && !isNaN(pathParts[i + 1])) {
                                valor = pathParts[i + 1];
                            }
                            break;
                        }
                    }
                    
                    console.log(`Detectado: tipo=${type}, valor=${valor}`);
                    
                    if (type) {
                        // Valor padrão se não for encontrado
                        const valorFinal = valor || '10';
                        
                        // Construir a nova URL e redirecionar
                        window.location.href = `/demo-game/${type}/${valorFinal}`;
                        return false;
                    } else {
                        // Se não encontrar tipo, voltar para a página inicial do demo
                        window.location.href = '/demo-game';
                        return false;
                    }
                }
            } catch (error) {
                console.error('Erro ao processar mensagem do jogo:', error);
            }
            
            return false;
        },
    },

    created() {
        console.log(`Componente Demo criado: type=${this.type}, valor_win=${this.valor_win}`);
        
        // Aguardar um pequeno intervalo para garantir que o DOM esteja pronto
        setTimeout(() => {
            if(this.type == 'win' && this.valor_win > 0){
                console.log('Exibindo modal de vitória');
                this.mostrarModal(this.valor_win, 'win');
            } else if(this.type == 'loss' && this.valor_win > 0){
                console.log('Exibindo modal de derrota');
                this.mostrarModal(this.valor_win, 'loss');
            } else {
                console.log('Modo demo iniciado sem modal');
            }
        }, 500);
        
        this.isLoadingWallet = false;
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

/* Efeito de vidro (glassmorphism) */
.glassmorphism {
    background: rgba(20, 20, 20, 0.7);
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.1);
    box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.37);
}

/* Animação de pulsação para o botão */
@keyframes pulse {
    0% {
        transform: scale(1);
    }
    50% {
        transform: scale(1.08);
    }
    100% {
        transform: scale(1);
    }
}

.pulse-btn {
    animation: pulse 1.5s ease-in-out infinite;
}

/* Botão personalizado com a aparência do ui-button-blue */
.custom-button {
    border-radius: 15px;
    border: 1px solid #2563EB;
    font-weight: 600;
    color: #000000;
    background: linear-gradient(#2563eb, #1d4ed8);
    transition: 0.3s ease all;
    box-shadow: 0 4px 15px rgba(37, 99, 235, 0.4);
}

.custom-button:hover {
    background: linear-gradient(#2563eb, #1d4ed8);
    box-shadow: 0 4px 20px rgba(37, 99, 235, 0.6);
}
</style>
