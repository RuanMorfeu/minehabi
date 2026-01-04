<template>
    <!-- Backdrop para o modal de depósito com evento de clique para fechar o modal -->
    <div id="modalDepositBackdrop" @click="closeModal" class="fixed inset-0 bg-black bg-opacity-70 backdrop-blur-sm hidden" style="position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; z-index: 99990; cursor: pointer;"></div>
    
    <button
        @click.prevent="toggleModalDeposit"
        type="button"
        :class="[
            showMobile === false ? 'hidden md:block' : '',
            isFull ? 'w-full' : '',
        ]"
        class="mr-3 rounded ui-button-blue"
    >
        {{ title }}
    </button>

    <div
        id="modalElDeposit"
        tabindex="-1"
        aria-hidden="true"
        class="fixed top-0 left-0 right-0 bottom-0 z-[99999] hidden overflow-y-auto overflow-x-hidden flex items-center justify-center p-4"
    >
        <div class="relative w-[95%] md:w-[500px] max-w-lg mx-auto max-h-[90vh] bg-base rounded-lg shadow-lg overflow-auto">
            <div class="flex flex-col px-2 pb-4 my-auto md:justify-between bg-white dark:bg-gray-800 rounded-lg shadow-lg">
                <div class="flex justify-between mt-3 mb-3 modal-header px-2">
                    <div>
                        <h1 class="text-xl font-bold">{{ $t("Deposit") }}</h1>
                       
                    </div>
                    <!-- Botão de fechar no estilo do Deposit.vue -->
                    <button 
                        type="button" 
                        @click="closeModal"
                        class="text-white bg-gray-600 rounded-lg text-sm w-8 h-8 ms-auto inline-flex justify-center items-center dark:bg-gray-700"
                    >
                        <svg class="w-3 h-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6" />
                        </svg>
                        <span class="sr-only">Fechar modal</span>
                    </button>
                </div>

                <!-- Banner de promoção de primeiro depósito -->
                <div v-if="depositPromoBanner" class="mb-4 rounded-lg overflow-hidden shadow-lg">
                    <div>
                        <img :src="depositPromoBanner.image.startsWith('http') ? depositPromoBanner.image : '/storage/' + depositPromoBanner.image" :alt="depositPromoBanner.description" class="w-full h-auto">
                    </div>
                </div>
                
                <CashIn :auth="this.isAuthenticated" @close="closeModal" />
            </div>
        </div>
    </div>
</template>

<script>
import { useAuthStore } from "@/Stores/Auth.js";
import DepositWidget from "@/Components/Widgets/DepositWidget.vue";
import CashIn from "../Modal/CashIn.vue";
import { onMounted, ref } from "vue";
import { initFlowbite } from "flowbite";
import HttpApi from "@/Services/HttpApi.js";

export default {
    props: ["showMobile", "title", "isFull"],
    components: { DepositWidget, CashIn },
    data() {
        return {
            isLoading: false,
            modalDeposit: null,
            depositPromoBanner: null,
        };
    },
    setup(props) {
        onMounted(() => {
            initFlowbite();
        });

        return {};
    },
    computed: {
        isAuthenticated() {
            const authStore = useAuthStore();
            return authStore.isAuth;
        },
    },
    mounted() {
        // Inicializar o modal com configurações corretas
        const $modalElement = document.getElementById("modalElDeposit");
        
        this.modalDeposit = new Modal(
            $modalElement,
            {
                placement: "center",
                backdrop: false, // Desativar backdrop nativo do Flowbite
                closable: true,
                accessibility: {
                    hidden: false
                },
                onHide: () => {
                    this.paymentType = null;
                },
                onShow: () => {
                    // Garantir que o modal não tenha aria-hidden quando aberto
                    $modalElement.removeAttribute('aria-hidden');
                },
                onToggle: () => {},
            }
        );
    },
    beforeUnmount() {},
    methods: {
        toggleModalDeposit: function () {
            // Verificar se o usuário está autenticado antes de abrir o modal
            if (!this.isAuthenticated) {
                // Redirecionar para login se não estiver autenticado
                this.$router.push('/login');
                return;
            }
            
            // Mostrar o backdrop personalizado antes de abrir o modal
            const backdrop = document.getElementById('modalDepositBackdrop');
            if (backdrop) {
                backdrop.classList.remove('hidden');
            }
            
            // Abrir o modal
            this.modalDeposit.show();
            
            // Garantir que o body não possa ser rolado enquanto o modal estiver aberto
            document.body.style.overflow = 'hidden';
            
            // Adicionar evento para fechar o modal quando clicar fora dele
            document.addEventListener('click', this.handleOutsideClick);
            
            // Buscar o banner de promoção de depósito quando o modal for aberto
            if (!this.depositPromoBanner) {
                this.fetchDepositPromoBanner();
            }
        },
        
        // Manipulador de clique fora do modal
        handleOutsideClick: function(event) {
            const backdrop = document.getElementById('modalDepositBackdrop');
            
            // Se o clique foi no backdrop (fora do conteúdo do modal)
            if (backdrop && event.target === backdrop) {
                // Fecha o modal
                this.closeModal();
            }
        },
        closeModal: function() {
            // Esconde o modal
            this.modalDeposit.hide();
            
            // Esconde o backdrop personalizado
            const backdrop = document.getElementById('modalDepositBackdrop');
            if (backdrop) {
                backdrop.classList.add('hidden');
            }
            
            // Restaurar o scroll do body
            document.body.style.overflow = 'auto';
            
            // Remover o listener de clique fora do modal
            document.removeEventListener('click', this.handleOutsideClick);
            
            // Remove manualmente qualquer backdrop que possa ter ficado
            setTimeout(() => {
                // Remover todos os backdrops que possam ter sido criados pelo Flowbite
                const backdrops = document.querySelectorAll('.fixed.inset-0');
                backdrops.forEach(el => {
                    if (el.id !== 'modalDepositBackdrop' && 
                        el.id !== 'modalRegisterBackdrop' && 
                        el.id !== 'modalAuthBackdrop' && 
                        el.id !== 'modalProfileBackdrop') {
                        el.remove();
                    }
                });
            }, 100);
        },
        fetchDepositPromoBanner: function() {
            HttpApi.get('banners/deposit-promo')
                .then(response => {
                    if (response.data.success) {
                        this.depositPromoBanner = response.data.banner;
                    }
                })
                .catch(error => {
                    console.error('Erro ao buscar banner de promoção de depósito:', error);
                });
        }
    },
    created() {},
    watch: {},
};
</script>

<style scoped>
/* Garantir que o modal de depósito fique acima do BottomNav */
#modalElDeposit {
    z-index: 99999 !important;
    position: fixed;
}

#modalDepositBackdrop {
    z-index: 99990 !important;
    position: fixed;
}
</style>
