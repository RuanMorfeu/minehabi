<!-- NotificationSystem.vue -->
<template>
    <div>
        <!-- Barra de Indicação -->
        <Transition name="slide">
            <div v-if="isOpen" 
                class="fixed w-full bg-[#2CB17F] text-sm flex items-center justify-between py-1 text-white text-center max-sm:text-xs px-4 z-[60]"
                style="top: 0;">
                <div class="flex-1 text-center">
                    💫 Indique um amigo e ganhe R$ 10,00 de saldo REAL para cada amigo que convidar
                </div>
                <button @click="toggleBar" class="text-white hover:text-gray-200 focus:outline-none ml-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                    </svg>
                </button>
            </div>
        </Transition>

        <!-- Espaçador Dinâmico -->
        <div :style="{ height: spacerHeight + 'px' }" class="transition-all duration-300"></div>
    </div>
</template>

<script>
export default {
    name: 'NotificationSystem',
    data() {
        return {
            isOpen: true,
            barHeight: 28 // Altura aproximada da barra (ajuste conforme necessário)
        }
    },
    computed: {
        spacerHeight() {
            return this.isOpen ? this.barHeight : 0
        }
    },
    methods: {
        toggleBar() {
            this.isOpen = !this.isOpen
            localStorage.setItem('indicationBarState', this.isOpen.toString())
            this.updateLayout()
        },
        updateLayout() {
            // Emite evento para que outros componentes possam se ajustar se necessário
            this.$emit('layout-update', { isOpen: this.isOpen, height: this.barHeight })
        }
    },
    mounted() {
        const savedState = localStorage.getItem('indicationBarState')
        if (savedState !== null) {
            this.isOpen = savedState === 'true'
        }
        this.updateLayout()
    }
}
</script>

<style scoped>
.slide-enter-active,
.slide-leave-active {
    transition: all 0.3s ease;
}

.slide-enter-from,
.slide-leave-to {
    transform: translateY(-100%);
    opacity: 0;
}
</style>
