<template>
    <div class="mollie-mbway-form">
        <div class="text-white mb-4">
            <h3 class="text-lg font-bold mb-2">Pagamento MB WAY</h3>
            <p class="text-sm text-blue-300">Insira o seu número de telemóvel para receber a notificação de pagamento</p>
        </div>

        <!-- Mostrar valor do pagamento -->
        <div class="bg-blue-800 p-4 rounded-lg mb-4">
            <div class="flex justify-between items-center">
                <span class="text-white">Valor a pagar:</span>
                <span class="text-yellow-500 font-bold text-lg">EUR {{ amount }}</span>
            </div>
            <div v-if="influencerBonus > 0" class="flex justify-between items-center mt-2">
                <span class="text-blue-300 text-sm">Bônus de influencer:</span>
                <span class="text-blue-500 text-sm">+ EUR {{ influencerBonus.toFixed(2) }}</span>
            </div>
        </div>

        <!-- Formulário de telefone -->
        <div class="mb-4">
            <label class="block text-white text-sm font-bold mb-2">
                Número de Telemóvel
            </label>
            <div class="flex">
                <span class="bg-gray-700 text-white px-3 py-2 rounded-l-lg border border-gray-600">+351</span>
                <input
                    v-model="phoneNumber"
                    type="tel"
                    placeholder="9xxxxxxxx"
                    maxlength="9"
                    pattern="[0-9]{9}"
                    class="flex-1 px-3 py-2 bg-gray-700 text-white border border-gray-600 rounded-r-lg focus:outline-none focus:border-blue-500"
                    :disabled="isProcessing"
                />
            </div>
            <p v-if="phoneError" class="text-blue-500 text-xs mt-1">{{ phoneError }}</p>
        </div>

        <!-- Botões -->
        <div class="flex gap-3">
            <button
                @click="processPayment"
                :disabled="isProcessing || !isValidPhone"
                class="flex-1 bg-blue-600 hover:bg-blue-700 disabled:bg-gray-600 text-white font-bold py-3 px-4 rounded-lg transition-colors"
            >
                <span v-if="isProcessing">Processando...</span>
                <span v-else>Pagar com MB WAY</span>
            </button>
            <button
                @click="$emit('back')"
                :disabled="isProcessing"
                class="bg-gray-600 hover:bg-gray-700 disabled:bg-blue-800 text-white font-bold py-3 px-4 rounded-lg transition-colors"
            >
                Voltar
            </button>
        </div>

        <!-- Status do pagamento -->
        <div v-if="paymentStatus" class="mt-4 p-4 rounded-lg" :class="paymentStatusClass">
            <p class="font-bold">{{ paymentStatus }}</p>
            <p v-if="paymentMessage" class="text-sm mt-1">{{ paymentMessage }}</p>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import HttpApi from '@/Services/HttpApi'

// Props
const props = defineProps({
    amount: {
        type: [String, Number],
        required: true
    },
    influencerBonus: {
        type: Number,
        default: 0
    },
    acceptBonus: {
        type: Boolean,
        default: false
    }
})

// Emits
const emit = defineEmits(['success', 'error', 'back', 'payment-initiated'])

// Estado reativo
const phoneNumber = ref('')
const phoneError = ref('')
const isProcessing = ref(false)
const paymentStatus = ref('')
const paymentMessage = ref('')

// Computed
const isValidPhone = computed(() => {
    return /^[0-9]{9}$/.test(phoneNumber.value)
})

const paymentStatusClass = computed(() => {
    if (paymentStatus.value.includes('sucesso')) return 'bg-blue-800 text-blue-200'
    if (paymentStatus.value.includes('erro')) return 'bg-blue-800 text-blue-200'
    return 'bg-blue-800 text-blue-200'
})

// Watchers
watch(phoneNumber, (newValue) => {
    phoneError.value = ''
    // Remover caracteres não numéricos
    phoneNumber.value = newValue.replace(/[^0-9]/g, '')
})

// Métodos
const validatePhone = () => {
    if (!phoneNumber.value) {
        phoneError.value = 'Por favor, insira um número de telemóvel'
        return false
    }
    if (!isValidPhone.value) {
        phoneError.value = 'O número deve ter 9 dígitos'
        return false
    }
    return true
}

const processPayment = async () => {
    if (!validatePhone()) return

    isProcessing.value = true
    paymentStatus.value = ''
    paymentMessage.value = ''

    try {
        console.log('[MBWAY-EMBEDDED] Iniciando pagamento MB WAY:', {
            amount: props.amount,
            phone: phoneNumber.value,
            acceptBonus: props.acceptBonus
        })

        const response = await HttpApi.post('mollie/create-payment', {
            amount: props.amount,
            mollie_method: 'mbway',
            accept_bonus: props.acceptBonus,
            phone: phoneNumber.value
        })


        if (response.data.status) {
            paymentStatus.value = 'Pagamento enviado com sucesso!'
            paymentMessage.value = 'Verifique o seu telemóvel para confirmar o pagamento no app MB WAY'
            
            emit('payment-initiated', {
                payment_id: response.data.payment_id,
                transaction_id: response.data.transaction_id
            })

            // Aguardar confirmação do pagamento (polling ou webhook)
            // Por enquanto, simular sucesso após alguns segundos
            setTimeout(() => {
                emit('success', response.data)
            }, 3000)

        } else {
            throw new Error(response.data.message || 'Erro no processamento do pagamento')
        }

    } catch (error) {
        console.error('[MBWAY-EMBEDDED] Erro no pagamento:', error)
        
        paymentStatus.value = 'Erro no pagamento'
        paymentMessage.value = error.response?.data?.message || error.message || 'Erro desconhecido'
        
        emit('error', paymentMessage.value)
    } finally {
        isProcessing.value = false
    }
}
</script>

<style scoped>
.mollie-mbway-form {
    max-width: 400px;
    margin: 0 auto;
}

input[type="tel"]::-webkit-outer-spin-button,
input[type="tel"]::-webkit-inner-spin-button {
    -webkit-appearance: none;
    margin: 0;
}

input[type="tel"] {
    -moz-appearance: textfield;
    appearance: none;
    appearance: none;
}
</style>
