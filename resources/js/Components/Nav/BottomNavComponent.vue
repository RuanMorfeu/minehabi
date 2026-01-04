<template>
    <div class="flex sm:hidden">
        <div class="fixed z-60 w-full navtop-color h-16 -translate-x-1/2 bg-white border-t-1 border-t border-gray-700 bottom-0 left-1/2" style="z-index: 60;">
            <div class="grid h-full grid-cols-5 mx-auto">
                <button @click.prevent="toggleMenu" data-tooltip-target="tooltip-menu" type="button" class="inline-flex flex-col items-center justify-center px-5 rounded-l-full hover:bg-gray-50 dark:hover:bg-gray-800 group">
    <i :class="isMenuOpen ? 'fa-regular fa-circle-xmark mb-1 text-xl' : 'fa-solid fa-bars mb-1 text-xl'" style="color : #2563EB;"></i>
    <span class="text-[12px]">Menu</span>
</button>
                <div id="tooltip-menu" role="tooltip" class="absolute z-10 invisible inline-block px-3 py-2 text-sm font-medium text-white transition-opacity duration-300 bg-gray-900 rounded-lg shadow-sm opacity-0 tooltip dark:bg-gray-700">
                    Menu
                    <div class="tooltip-arrow" data-popper-arrow></div>
                </div>
                <button @click="$router.push('/profile/affiliate')" data-tooltip-target="tooltip-affiliate" type="button" class="inline-flex flex-col items-center justify-center px-5 hover:bg-gray-50 dark:hover:bg-gray-800 group">
                    <i class="fa-duotone fa-users mb-1 text-xl" style="--fa-primary-color: #3B82F6; --fa-secondary-color: #1D4ED8;"></i>
                    <span class="text-[12px]">Afiliados</span>
                </button>
                <div id="tooltip-affiliate" role="tooltip" class="absolute z-10 invisible inline-block px-3 py-2 text-sm font-medium text-white transition-opacity duration-300 bg-gray-900 rounded-lg shadow-sm opacity-0 tooltip dark:bg-gray-700">
                    Afiliados
                    <div class="tooltip-arrow" data-popper-arrow></div>
                </div>
                <div class="flex items-center justify-center">
                    <button @click.prevent="openDepositModal" data-tooltip-target="tooltip-new" type="button" class="inline-flex items-center justify-center w-[50px] h-[50px] font-medium bg-gradient-to-b from-[#3B82F6] to-[#1D4ED8] rounded-lg hover:bg-gradient-to-b from-[#3B82F6] to-[#1D4ED8] group focus:ring-4 focus:ring-blue-300 focus:outline-none dark:focus:ring-blue-800 text-[20px]">
                        <i class="fa-solid fa-euro-sign fa-beat-fade text-white"></i>
                        <span class="sr-only">{{ $t('Deposit') }}</span>
                    </button>
                </div>
                <div id="tooltip-new" role="tooltip" class="absolute z-10 invisible inline-block px-3 py-2 text-sm font-medium text-white transition-opacity duration-300 bg-gray-900 rounded-lg shadow-sm opacity-0 tooltip dark:bg-gray-700">
                    {{ $t('New Deposit') }}
                    <div class="tooltip-arrow" data-popper-arrow></div>
                </div>
                <button @click="$router.push('/casino/provider/all/category/slots')" data-tooltip-target="tooltip-slots" type="button" class="inline-flex flex-col items-center justify-center px-5 rounded-r-full hover:bg-gray-50 dark:hover:bg-gray-800 group">
                    <i class="fa-duotone fa-cherries mb-1 text-xl"style="--fa-primary-color: #3B82F6; --fa-secondary-color: #1D4ED8;"></i>
                    <span class="text-[12px]">Slots</span>
                </button>
                <div id="tooltip-slots" role="tooltip" class="absolute z-10 invisible inline-block px-3 py-2 text-sm font-medium text-white transition-opacity duration-300 bg-gray-900 rounded-lg shadow-sm opacity-0 tooltip dark:bg-gray-700">
                    Slots
                    <div class="tooltip-arrow" data-popper-arrow></div>
                </div>
                <button @click="$router.push('/profile/wallet')" data-tooltip-target="tooltip-wallet" type="button" class="inline-flex flex-col items-center justify-center px-5 hover:bg-gray-50 dark:hover:bg-gray-800 group">
                    <i class="fa-duotone fa-wallet mb-1 text-xl"style="--fa-primary-color: #3B82F6; --fa-secondary-color: #1D4ED8;"></i>
                    <span class="text-[12px]">{{ $t('Wallet') }}</span>
                </button>
                <div id="tooltip-wallet" role="tooltip" class="absolute z-10 invisible inline-block px-3 py-2 text-sm font-medium text-white transition-opacity duration-300 bg-gray-900 rounded-lg shadow-sm opacity-0 tooltip dark:bg-gray-700">
                    {{ $t('Wallet') }}
                    <div class="tooltip-arrow" data-popper-arrow></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de depósito original -->
    <div
        id="modalElDeposit"
        tabindex="-1"
        class="fixed top-0 left-0 right-0 hidden w-full overflow-x-hidden overflow-y-auto md:inset-0"
        style="min-height: 100vh; z-index: 45;"
        aria-modal="true"
    >
        <div class="relative w-full max-w-2xl">
            <div class="flex flex-col px-6 pb-8 my-auto md:justify-between bg-white dark:bg-[#17181b] rounded-lg shadow-lg">
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
            const modalContent = document.querySelector('#modalElDeposit .flex-col');
            const modalBackdrop = document.querySelector('.fixed.inset-0.z-40');
            
            // Se o clique foi no backdrop (fora do conteúdo do modal)
            if (modalBackdrop && event.target === modalBackdrop) {
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
            
            // Configurações do modal com melhorias de acessibilidade
            this.modalDeposit = new Modal($modalElement, {
                placement: 'center',
                backdrop: 'dynamic',
                backdropClasses: "bg-gray-900\/50 dark:bg-gray-900\/80 fixed inset-0 z-[44]",
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

</style>
