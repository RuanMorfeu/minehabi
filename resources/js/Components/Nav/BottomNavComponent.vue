<template>
    <div class="fixed bottom-4 left-4 right-4 z-50 sm:hidden">
        <!-- Barra flutuante com efeito glassmorphism -->
        <div class="flex items-center justify-between px-2 py-3 bg-gray-900/90 backdrop-blur-lg rounded-2xl shadow-2xl border border-gray-700/50">
            
            <!-- Botão Menu (Movido para o início) -->
            <button @click.prevent="toggleMenu" class="flex flex-col items-center justify-center w-full group">
                <div class="p-1 transition-all duration-300 rounded-xl group-hover:bg-white/10 group-active:scale-95">
                    <i :class="isMenuOpen ? 'fa-solid fa-xmark' : 'fa-solid fa-bars'" class="text-xl text-gray-400 group-hover:text-green-500 transition-colors"></i>
                </div>
                <span class="text-[10px] font-medium text-gray-400 group-hover:text-green-500 transition-colors">Menu</span>
            </button>

            <!-- Botão Slots -->
            <button @click="$router.push('/casino/provider/all/category/slots')" class="flex flex-col items-center justify-center w-full group">
                <div class="p-1 transition-all duration-300 rounded-xl group-hover:bg-white/10 group-active:scale-95">
                    <i class="fa-duotone fa-gamepad fa-fw text-xl text-gray-400 group-hover:text-purple-500 transition-colors"></i>
                </div>
                <span class="text-[10px] font-medium text-gray-400 group-hover:text-purple-500 transition-colors">Slots</span>
            </button>

            <!-- Botão Central de Depósito (Destaque) -->
            <button @click.prevent="openDepositModal" class="flex flex-col items-center justify-center w-full group">
                <div class="flex items-center justify-center w-12 h-12 bg-gradient-to-tr from-blue-600 to-purple-600 rounded-full shadow-lg shadow-blue-900/50 transform transition-transform hover:scale-110 active:scale-95">
                    <i class="fa-solid fa-plus text-xl text-white"></i>
                </div>
                <span class="text-[10px] font-medium text-gray-400 group-hover:text-blue-500 transition-colors">Depositar</span>
            </button>

            <!-- Botão Carteira (Substitui Ao Vivo) -->
            <button @click="$router.push('/profile/wallet')" class="flex flex-col items-center justify-center w-full group">
                <div class="p-1 transition-all duration-300 rounded-xl group-hover:bg-white/10 group-active:scale-95">
                    <i class="fa-duotone fa-wallet text-xl text-gray-400 group-hover:text-red-500 transition-colors"></i>
                </div>
                <span class="text-[10px] font-medium text-gray-400 group-hover:text-red-500 transition-colors">Carteira</span>
            </button>

            <!-- Botão Início (Movido para o final) -->
            <button @click="$router.push('/')" class="flex flex-col items-center justify-center w-full group">
                <div class="p-1 transition-all duration-300 rounded-xl group-hover:bg-white/10 group-active:scale-95">
                    <i class="fa-duotone fa-house text-xl text-gray-400 group-hover:text-blue-500 transition-colors"></i>
                </div>
                <span class="text-[10px] font-medium text-gray-400 group-hover:text-blue-500 transition-colors">Início</span>
            </button>
        </div>
    </div>

    <!-- Modal de depósito original (Mantido a lógica, apenas ajustado o z-index se necessário) -->
    <div
        id="modalElDeposit"
        tabindex="-1"
        class="fixed top-0 left-0 right-0 hidden w-full overflow-x-hidden overflow-y-auto md:inset-0"
        style="min-height: 100vh; z-index: 60;"
        aria-modal="true"
    >
        <div class="relative w-full max-w-2xl h-full md:h-auto">
            <div class="flex flex-col px-6 pb-8 my-auto md:justify-between bg-white dark:bg-[#17181b] rounded-lg shadow-lg relative">
                <div class="flex justify-between mt-6 mb-6 modal-header">
                    <div>
                        <h1 class="text-xl font-bold">{{ $t("Deposit") }}</h1>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            <small>{{ $t("Choose your preferred payment method") 
                            }}</small>
                        </p>
                    </div>
                    <!-- Botão de fechar no cabeçalho do modal -->
                    <button 
                        type="button" 
                        @click="closeModal"
                        class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 ms-auto inline-flex justify-center items-center dark:hover:bg-gray-600 dark:hover:text-white"
                        aria-label="Fechar modal"
                        tabindex="0"
                    >
                        <svg class="w-3 h-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6" />
                        </svg>
                        <span class="sr-only">Fechar modal</span>
                    </button>
                </div>

                <CashIn :auth="this.isAuthenticated" @close="closeModal" />
            </div>
        </div>
    </div>
</template>

<script>

import { useAuthStore } from "@/Stores/Auth.js";
import { sidebarStore } from "@/Stores/SideBarStore.js";
import CashIn from "@/Components/Modal/CashIn.vue";
import { Modal } from "flowbite";

export default {
    props: [],
    components: { CashIn },
    data() {
        return {
            isLoading: false,
            isMenuOpen: false, // Controle do estado do menu
            paymentType: null,
            modalDeposit: null,
        }
    },
    setup(props) {
        return {};
    },
    computed: {
        isAuthenticated() {
            const authStore = useAuthStore();
            return authStore.isAuth;
        },
        sidebarMenuStore() {
            return sidebarStore();
        },
    },
    mounted() {
        // Inicializa o modal de depósito usando Flowbite com opções melhoradas
        this.initModal();
    },
    methods: {
        toggleModalDeposit: function () {
            // Verifica se o usuário está autenticado
            if (this.isAuthenticated) {
                // Se estiver autenticado, abre o modal de depósito
                this.modalDeposit.toggle();
            } else {
                // Se não estiver autenticado, redireciona para a página de login
                this.$router.push('/login');
            }
        },
        toggleMenu() {
            this.isMenuOpen = !this.isMenuOpen; // Alterna o estado do menu
            this.sidebarMenuStore.setSidebarToogle(); // Chama a função para alternar o estado do sidebar
        },
        closeModal() {
            // Esconde o modal de forma simples e direta
            this.modalDeposit.hide();
            
            // Remove apenas o backdrop específico do Flowbite
            const backdrop = document.querySelector('.fixed.inset-0.z-40');
            if (backdrop) {
                backdrop.remove();
            }
            
            // Remove o listener de clique fora do modal
            document.removeEventListener('click', this.handleOutsideClick);
            
            // Restaura o scroll do body
            document.body.style.overflow = 'auto';
            document.body.classList.remove('overflow-hidden');
        },
        openDepositModal() {
            this.toggleModalDeposit();
        },
        
        // Método para adicionar listener de clique fora do modal
        addClickOutsideListener() {
            // Remove qualquer listener anterior para evitar duplicação
            document.removeEventListener('click', this.handleOutsideClick);
            
            // Adiciona o listener para detectar cliques fora do modal
            setTimeout(() => {
                document.addEventListener('click', this.handleOutsideClick);
            }, 100);
        },
        
        // Manipulador de clique fora do modal
        handleOutsideClick(event) {
            // Verifica se o modal está visível antes de tentar fechar
            const modalElement = document.querySelector('#modalElDeposit');
            if (!modalElement || modalElement.classList.contains('hidden')) {
                return;
            }

            const modalContent = document.querySelector('#modalElDeposit .relative.w-full');
            
            // Se o clique foi fora do conteúdo do modal
            if (modalContent && !modalContent.contains(event.target)) {
                // Fecha o modal
                this.closeModal();
                
                // Remove o listener após fechar o modal
                document.removeEventListener('click', this.handleOutsideClick);
            }
        },
        
        // Método para inicializar o modal com configurações de acessibilidade
        initModal() {
            // Inicializa o modal de depósito usando Flowbite
            const $modalElement = document.querySelector('#modalElDeposit');
            
            if (!$modalElement) return;

            // Configurações do modal com melhorias de acessibilidade
            this.modalDeposit = new Modal($modalElement, {
                placement: 'center',
                backdrop: 'dynamic',
                backdropClasses: "bg-gray-900/50 dark:bg-gray-900/80 fixed inset-0 z-[55]",
                closable: true,
                onHide: () => {
                    this.paymentType = null;
                },
                onShow: () => {
                    // Garante que o modal não tenha aria-hidden quando aberto
                    $modalElement.removeAttribute('aria-hidden');
                    
                    // Adiciona evento para fechar o modal quando clicar fora dele
                    this.addClickOutsideListener();
                }
            });
        }
    },
    watch: {

    },
};
</script>

<style scoped>
/* Animação suave para os ícones */
i {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

button:active i {
    transform: scale(0.9);
}

/* Ajuste para o botão central flutuante */
.fa-plus {
    filter: drop-shadow(0 2px 4px rgba(0,0,0,0.2));
}
</style>