<template>
    <div class="mollie-googlepay-form">
        <div v-if="!isGooglePayAvailable" class="text-red-500 text-sm mb-4">
            Google Pay não está disponível neste dispositivo ou navegador.
        </div>
        
        <div v-else>
            <div id="google-pay-button" class="google-pay-button-container">
                <!-- O botão será inserido aqui pelo Google Pay API -->
            </div>
            
            <div v-if="error" class="text-red-500 text-sm mt-2">
                {{ error }}
            </div>
            
            <div v-if="loading" class="text-white text-sm mt-2">
                Processando pagamento Google Pay...
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

const isGooglePayAvailable = ref(false)
const loading = ref(false)
const error = ref('')
let paymentsClient = null

onMounted(async () => {
    console.log('[GOOGLE-PAY] Verificando disponibilidade do Google Pay')
    
    // Carregar Google Pay API
    await loadGooglePayAPI()
    
    // Verificar se Google Pay está disponível
    if (window.google && window.google.payments) {
        try {
            paymentsClient = new google.payments.api.PaymentsClient({
                environment: 'TEST' // Mudar para 'PRODUCTION' em produção
            })
            
            const isReadyToPayRequest = {
                apiVersion: 2,
                apiVersionMinor: 0,
                allowedPaymentMethods: [{
                    type: 'CARD',
                    parameters: {
                        allowedAuthMethods: ['PAN_ONLY', 'CRYPTOGRAM_3DS'],
                        allowedCardNetworks: ['AMEX', 'MASTERCARD', 'VISA']
                    }
                }]
            }
            
            const response = await paymentsClient.isReadyToPay(isReadyToPayRequest)
            
            if (response.result) {
                isGooglePayAvailable.value = true
                console.log('[GOOGLE-PAY] Google Pay disponível')
                createGooglePayButton()
            } else {
                console.log('[GOOGLE-PAY] Google Pay não disponível')
                isGooglePayAvailable.value = false
            }
        } catch (error) {
            console.error('[GOOGLE-PAY] Erro ao verificar disponibilidade:', error)
            isGooglePayAvailable.value = false
        }
    } else {
        console.log('[GOOGLE-PAY] Google Pay API não carregada')
        isGooglePayAvailable.value = false
    }
})

const loadGooglePayAPI = () => {
    return new Promise((resolve, reject) => {
        if (window.google && window.google.payments) {
            resolve()
            return
        }
        
        const script = document.createElement('script')
        script.src = 'https://pay.google.com/gp/p/js/pay.js'
        script.onload = resolve
        script.onerror = reject
        document.head.appendChild(script)
    })
}

const createGooglePayButton = () => {
    const button = paymentsClient.createButton({
        onClick: initiateGooglePay,
        buttonColor: 'black',
        buttonType: 'pay',
        buttonSizeMode: 'fill'
    })
    
    const container = document.getElementById('google-pay-button')
    if (container) {
        container.appendChild(button)
    }
}

const initiateGooglePay = async () => {
    if (!isGooglePayAvailable.value || loading.value) return
    
    loading.value = true
    error.value = ''
    
    try {
        console.log('[GOOGLE-PAY] Iniciando pagamento Google Pay')
        emit('payment-initiated')
        
        const paymentDataRequest = {
            apiVersion: 2,
            apiVersionMinor: 0,
            allowedPaymentMethods: [{
                type: 'CARD',
                parameters: {
                    allowedAuthMethods: ['PAN_ONLY', 'CRYPTOGRAM_3DS'],
                    allowedCardNetworks: ['AMEX', 'MASTERCARD', 'VISA']
                },
                tokenizationSpecification: {
                    type: 'PAYMENT_GATEWAY',
                    parameters: {
                        gateway: 'mollie',
                        gatewayMerchantId: props.profileId
                    }
                }
            }],
            transactionInfo: {
                totalPriceStatus: 'FINAL',
                totalPrice: props.amount.toString(),
                currencyCode: 'EUR'
            },
            merchantInfo: {
                merchantName: 'Dei.bet'
            }
        }
        
        const paymentData = await paymentsClient.loadPaymentData(paymentDataRequest)
        console.log('[GOOGLE-PAY] Dados de pagamento recebidos')
        
        // Enviar token para o backend
        const response = await HttpApi.post('mollie/create-payment', {
            amount: props.amount,
            mollie_method: 'googlepay',
            googlePayPaymentToken: JSON.stringify(paymentData.paymentMethodData.tokenizationData.token),
            accept_bonus: true
        })
        
        if (response.data.status) {
            emit('payment-success', response.data)
        } else {
            handleError(response.data.message || 'Erro no pagamento')
        }
        
    } catch (error) {
        console.error('[GOOGLE-PAY] Erro no pagamento:', error)
        if (error.statusCode === 'CANCELED') {
            console.log('[GOOGLE-PAY] Pagamento cancelado pelo usuário')
        } else {
            handleError('Erro ao processar pagamento Google Pay')
        }
    } finally {
        loading.value = false
    }
}

const handleError = (message) => {
    error.value = message
    loading.value = false
    emit('payment-error', message)
}
</script>

<style scoped>
.mollie-googlepay-form {
    width: 100%;
}

.google-pay-button-container {
    width: 100%;
    height: 50px;
}
</style>
