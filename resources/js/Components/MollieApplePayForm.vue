<template>
    <div class="mollie-applepay-form">
        <div v-if="!isApplePayAvailable" class="text-red-500 text-sm mb-4">
            Apple Pay não está disponível neste dispositivo ou navegador.
        </div>
        
        <div v-else>
            <div id="apple-pay-button" 
                 class="apple-pay-button apple-pay-button-black"
                 style="width: 100%; height: 50px; cursor: pointer; border-radius: 8px;"
                 @click="initiateApplePay">
            </div>
            
            <div v-if="error" class="text-red-500 text-sm mt-2">
                {{ error }}
            </div>
            
            <div v-if="loading" class="text-white text-sm mt-2">
                Processando pagamento Apple Pay...
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, defineEmits } from 'vue'
import HttpApi from '@/Services/HttpApi.js'

const props = defineProps({
    amount: {
        type: [String, Number],
        required: true
    },
    profileId: {
        type: String,
        required: true
    }
})

const emit = defineEmits(['payment-success', 'payment-error', 'payment-initiated'])

const isApplePayAvailable = ref(false)
const loading = ref(false)
const error = ref('')

onMounted(async () => {
    console.log('[APPLE-PAY] Verificando disponibilidade do Apple Pay')
    
    // Verificar se Apple Pay está disponível
    if (window.ApplePaySession && ApplePaySession.canMakePayments()) {
        isApplePayAvailable.value = true
        console.log('[APPLE-PAY] Apple Pay disponível')
        
        // Adicionar estilos CSS para o botão Apple Pay
        addApplePayStyles()
    } else {
        console.log('[APPLE-PAY] Apple Pay não disponível')
        isApplePayAvailable.value = false
    }
})

const addApplePayStyles = () => {
    if (document.getElementById('apple-pay-styles')) return
    
    const style = document.createElement('style')
    style.id = 'apple-pay-styles'
    style.textContent = `
        .apple-pay-button {
            display: inline-block;
            -webkit-appearance: -apple-pay-button;
            -apple-pay-button-type: pay;
        }
        .apple-pay-button-black {
            -apple-pay-button-style: black;
        }
    `
    document.head.appendChild(style)
}

const initiateApplePay = async () => {
    if (!isApplePayAvailable.value || loading.value) return
    
    loading.value = true
    error.value = ''
    
    try {
        console.log('[APPLE-PAY] Iniciando pagamento Apple Pay via redirect')
        emit('payment-initiated')
        
        // Criar pagamento Apple Pay via API do Mollie com redirect
        const response = await HttpApi.post('mollie/create-payment', {
            amount: props.amount,
            mollie_method: 'applepay',
            accept_bonus: true
        })
        
        if (response.data.status && response.data.checkout_url) {
            console.log('[APPLE-PAY] Redirecionando para checkout Mollie')
            // Redirecionar para a página de checkout do Mollie
            window.location.href = response.data.checkout_url
        } else {
            handleError(response.data.message || 'Erro ao criar pagamento Apple Pay')
        }
        
    } catch (error) {
        console.error('[APPLE-PAY] Erro ao iniciar Apple Pay:', error)
        handleError('Erro ao iniciar Apple Pay')
    }
}

const handleError = (message) => {
    error.value = message
    loading.value = false
    emit('payment-error', message)
}
</script>

<style scoped>
.mollie-applepay-form {
    width: 100%;
}

.apple-pay-button {
    background: #000;
    border-radius: 8px;
    border: none;
    outline: none;
}

.apple-pay-button:hover {
    opacity: 0.8;
}
</style>
