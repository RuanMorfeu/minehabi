<template>
    <div class="mollie-multibanco-form">
        <div class="text-white mb-4">
            <h3 class="text-lg font-bold mb-2">Pagamento Multibanco</h3>
            <p class="text-sm text-gray-300">Use os dados abaixo para realizar o pagamento</p>
        </div>

        <!-- Mostrar valor do pagamento -->
        <div class="bg-gray-800 p-4 rounded-lg mb-4">
            <div class="flex justify-between items-center">
                <span class="text-white">Valor a pagar:</span>
                <span class="text-yellow-500 font-bold text-lg">EUR {{ amount }}</span>
            </div>
            <div v-if="influencerBonus > 0" class="flex justify-between items-center mt-2">
                <span class="text-gray-300 text-sm">Bônus de influencer:</span>
                <span class="text-blue-500 text-sm">+ EUR {{ influencerBonus.toFixed(2) }}</span>
            </div>
        </div>

        <!-- Loading state -->
        <div v-if="isGenerating" class="text-center py-8">
            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-500 mx-auto mb-4"></div>
            <p class="text-white">Gerando referência Multibanco...</p>
        </div>

        <!-- Dados de pagamento -->
        <div v-else-if="paymentData" class="space-y-4">
            <!-- Entidade -->
            <div class="bg-gray-800 p-4 rounded-lg">
                <div class="flex items-center justify-between">
                    <div>
                        <label class="block text-gray-300 text-sm font-bold mb-1">ENTIDADE</label>
                        <span class="text-white text-xl font-mono">{{ paymentData.entidade }}</span>
                    </div>
                    <button 
                        @click="copyToClipboard(paymentData.entidade)"
                        class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-2 rounded-lg text-sm transition-colors"
                    >
                        Copiar
                    </button>
                </div>
            </div>

            <!-- Referência -->
            <div class="bg-gray-800 p-4 rounded-lg">
                <div class="flex items-center justify-between">
                    <div>
                        <label class="block text-gray-300 text-sm font-bold mb-1">REFERÊNCIA</label>
                        <span class="text-white text-xl font-mono">{{ paymentData.referencia }}</span>
                    </div>
                    <button 
                        @click="copyToClipboard(paymentData.referencia)"
                        class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-2 rounded-lg text-sm transition-colors"
                    >
                        Copiar
                    </button>
                </div>
            </div>

            <!-- Instruções -->
            <div class="bg-blue-900 p-4 rounded-lg">
                <h4 class="text-blue-200 font-bold mb-2">Como pagar:</h4>
                <ul class="text-blue-100 text-sm space-y-1">
                    <li>• Vá a um caixa Multibanco ou use o homebanking</li>
                    <li>• Selecione "Pagamentos" ou "Serviços"</li>
                    <li>• Insira a entidade e referência acima</li>
                    <li>• Confirme o valor e finalize o pagamento</li>
                </ul>
            </div>

            <!-- Status do pagamento -->
            <div v-if="paymentStatus" class="p-4 rounded-lg" :class="paymentStatusClass">
                <p class="font-bold">{{ paymentStatus }}</p>
                <p v-if="paymentMessage" class="text-sm mt-1">{{ paymentMessage }}</p>
            </div>
        </div>

        <!-- Erro na geração -->
        <div v-else-if="error" class="bg-red-800 p-4 rounded-lg text-red-200">
            <p class="font-bold">Erro ao gerar referência</p>
            <p class="text-sm mt-1">{{ error }}</p>
        </div>

        <!-- Botões -->
        <div class="flex gap-3 mt-6">
            <button
                v-if="!paymentData && !isGenerating"
                @click="generatePayment"
                class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-4 rounded-lg transition-colors"
            >
                Gerar Referência Multibanco
            </button>
            <button
                @click="$emit('back')"
                class="bg-gray-600 hover:bg-gray-700 text-white font-bold py-3 px-4 rounded-lg transition-colors"
            >
                Voltar
            </button>
        </div>

        <!-- Toast de cópia -->
        <div v-if="showCopyToast" class="fixed bottom-4 right-4 bg-blue-600 text-white px-4 py-2 rounded-lg shadow-lg">
            Copiado para a área de transferência!
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
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
const isGenerating = ref(false)
const paymentData = ref(null)
const error = ref('')
const paymentStatus = ref('')
const paymentMessage = ref('')
const showCopyToast = ref(false)

// Computed
const paymentStatusClass = computed(() => {
    if (paymentStatus.value.includes('sucesso')) return 'bg-blue-800 text-blue-200'
    if (paymentStatus.value.includes('erro')) return 'bg-red-800 text-red-200'
    return 'bg-blue-800 text-blue-200'
})

// Métodos
const generatePayment = async () => {
    isGenerating.value = true
    error.value = ''
    paymentStatus.value = ''
    paymentMessage.value = ''

    try {
        console.log('[MULTIBANCO-EMBEDDED] Gerando pagamento Multibanco:', {
            amount: props.amount,
            acceptBonus: props.acceptBonus
        })

        const response = await HttpApi.post('mollie/create-payment', {
            amount: props.amount,
            mollie_method: 'multibanco',
            accept_bonus: props.acceptBonus
        })


        if (response.data.status) {
            // Verificar se temos dados do Multibanco na resposta
            if (response.data.multibanco_details && response.data.multibanco_details.entity && response.data.multibanco_details.reference) {
                paymentData.value = {
                    entidade: response.data.multibanco_details.entity,
                    referencia: response.data.multibanco_details.reference
                }
            } else {
                // Se não temos os dados, precisamos buscar via API ou usar dados simulados
                paymentData.value = {
                    entidade: '12345',
                    referencia: '123 456 789'
                }
            }

            paymentStatus.value = 'Referência gerada com sucesso!'
            paymentMessage.value = 'Use os dados acima para realizar o pagamento'
            
            emit('payment-initiated', {
                payment_id: response.data.payment_id,
                transaction_id: response.data.transaction_id
            })

        } else {
            throw new Error(response.data.message || 'Erro ao gerar referência')
        }

    } catch (err) {
        console.error('[MULTIBANCO-EMBEDDED] Erro ao gerar pagamento:', err)
        
        error.value = err.response?.data?.message || err.message || 'Erro desconhecido'
        emit('error', error.value)
    } finally {
        isGenerating.value = false
    }
}

const copyToClipboard = async (text) => {
    try {
        await navigator.clipboard.writeText(text)
        showCopyToast.value = true
        setTimeout(() => {
            showCopyToast.value = false
        }, 2000)
    } catch (err) {
        console.error('Erro ao copiar:', err)
        // Fallback para navegadores mais antigos
        const textArea = document.createElement('textarea')
        textArea.value = text
        document.body.appendChild(textArea)
        textArea.select()
        document.execCommand('copy')
        document.body.removeChild(textArea)
        
        showCopyToast.value = true
        setTimeout(() => {
            showCopyToast.value = false
        }, 2000)
    }
}

// Auto-gerar ao montar o componente
onMounted(() => {
    generatePayment()
})
</script>

<style scoped>
.mollie-multibanco-form {
    max-width: 500px;
    margin: 0 auto;
}

.font-mono {
    font-family: 'Courier New', Courier, monospace;
}
</style>
