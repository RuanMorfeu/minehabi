<template>
    <BaseLayout>
        <div class="min-h-screen bg-blue-50 dark:bg-blue-600 py-8">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6 mb-8">
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">
                        Verificação de Documentos
                    </h1>
                    <p class="text-blue-600 dark:text-blue-400">
                        Para garantir a segurança da sua conta, precisamos verificar seus documentos.
                    </p>
                </div>

                <!-- Status da Verificação -->
                <div v-if="verificationStatus" class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6 mb-8">
                    <div class="flex items-center justify-between">
                        <div>
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                                Status da Verificação
                            </h2>
                            <p class="text-sm text-blue-600 dark:text-blue-400 mt-1">
                                {{ getStatusMessage() }}
                            </p>
                        </div>
                        <div class="flex items-center">
                            <span :class="getStatusBadgeClass()" class="px-3 py-1 rounded-full text-sm font-medium">
                                {{ getStatusLabel() }}
                            </span>
                        </div>
                    </div>
                    
                    <!-- Motivo da Rejeição -->
                    <div v-if="verificationStatus.verification_status === 'rejected' && verificationStatus.rejection_reason" 
                         class="mt-4 p-4 bg-blue-50 dark:bg-blue-900/20 border border-red-200 dark:border-red-800 rounded-lg">
                        <h3 class="text-sm font-medium text-blue-800 dark:text-red-200 mb-2">
                            Motivo da Rejeição:
                        </h3>
                        <p class="text-sm text-red-700 dark:text-blue-300">
                            {{ verificationStatus.rejection_reason }}
                        </p>
                    </div>
                    
                    <!-- Informações de Tentativas -->
                    <div v-if="verificationStatus.verification_status === 'rejected' && verificationStatus.submission_attempts > 0" 
                         class="mt-4 p-4 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-sm font-medium text-yellow-800 dark:text-yellow-200">
                                    Tentativas de Envio
                                </h3>
                                <p class="text-sm text-yellow-700 dark:text-yellow-300 mt-1">
                                    {{ verificationStatus.submission_attempts }} de {{ verificationStatus.max_attempts }} tentativas utilizadas
                                </p>
                            </div>
                            <div v-if="verificationStatus.cooldown_hours > 0" class="text-right">
                                <span class="text-sm font-medium text-yellow-800 dark:text-yellow-200">
                                    Cooldown: {{ verificationStatus.cooldown_hours }}h
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Informações de Limite de Tentativas -->
                <div v-if="verificationStatus && verificationStatus.verification_status === 'rejected' && !verificationStatus.can_resubmit && verificationStatus.submission_attempts >= verificationStatus.max_attempts" 
                     class="bg-blue-50 dark:bg-blue-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4 mb-8">
                    <h3 class="text-sm font-medium text-blue-800 dark:text-red-200 mb-2">
                        🚫 Limite de Tentativas Excedido
                    </h3>
                    <div class="text-sm text-red-700 dark:text-blue-300">
                        <p class="mb-2">
                            Você atingiu o limite máximo de <strong>{{ verificationStatus.max_attempts }} tentativas</strong> de envio de documentos.
                        </p>
                        <p class="text-xs">
                            Entre em contato com o suporte para mais informações.
                        </p>
                    </div>
                </div>

                <!-- Formulário de Upload -->
                <div v-if="canShowUploadForm" 
                     class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                    
                    <!-- Informações Pessoais -->
                    <div class="mb-8">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                            Informações Pessoais
                        </h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Nome Completo *
                                </label>
                                <input 
                                    v-model="personalInfo.full_name"
                                    type="text" 
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
                                    placeholder="Digite seu nome completo"
                                    required
                                />
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    NIF *
                                </label>
                                <input 
                                    v-model="personalInfo.document_number"
                                    type="text" 
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
                                    :class="{'border-red-500 focus:border-blue-500 focus:ring-blue-500': nifError}"
                                    placeholder="Digite o seu NIF"
                                    @input="validateNIFInput"
                                    required
                                />
                                <div v-if="nifError" class="mt-1 text-sm text-red-600">
                                    {{ nifError }}
                                </div>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Data de Nascimento *
                                </label>
                                <input 
                                    v-model="personalInfo.birth_date"
                                    type="date" 
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
                                    required
                                />
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Telemóvel
                                </label>
                                <input 
                                    v-model="personalInfo.phone"
                                    type="tel" 
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
                                    placeholder="+351 9XXXXXXXX"
                                />
                            </div>

                        </div>
                    </div>

                    <!-- Tipo de Documento -->
                    <div class="mb-8">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                            Tipo de Documento
                        </h2>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            <label v-for="type in documentTypes" :key="type.value" 
                                   class="relative flex items-center justify-center p-4 border-2 rounded-lg cursor-pointer transition-colors"
                                   :class="documentData.document_type === type.value ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20' : 'border-gray-300 dark:border-gray-600 hover:border-blue-400'">
                                <input 
                                    v-model="documentData.document_type"
                                    :value="type.value"
                                    type="radio" 
                                    class="sr-only"
                                />
                                <div class="text-center">
                                    <i :class="type.icon" class="text-2xl mb-2 text-blue-600 dark:text-blue-400"></i>
                                    <span class="block text-sm font-medium text-gray-900 dark:text-white">
                                        {{ type.label }}
                                    </span>
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- Upload de Documentos -->
                    <div class="space-y-6">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                            Upload de Documentos
                        </h2>

                        <!-- Documento Frente -->
                        <div class="border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg p-6">
                            <div class="text-center">
                                <i class="fas fa-id-card text-4xl text-gray-400 mb-4"></i>
                                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">
                                    Documento - Frente *
                                </h3>
                                <p class="text-sm text-blue-600 dark:text-blue-400 mb-4">
                                    Envie uma foto clara da frente do seu documento
                                </p>
                                <input 
                                    ref="documentFrontInput"
                                    type="file" 
                                    accept="image/*"
                                    @change="handleFileUpload('document_front', $event)"
                                    class="hidden"
                                />
                                <button 
                                    @click="$refs.documentFrontInput.click()"
                                    type="button"
                                    class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md transition-colors"
                                >
                                    Selecionar Arquivo
                                </button>
                                <div v-if="documentData.document_front" class="mt-4">
                                    <img :src="getPreviewUrl('document_front')" alt="Preview" class="mx-auto max-w-xs rounded-lg shadow-md">
                                    <p class="text-sm text-blue-600 dark:text-blue-400 mt-2">
                                        Arquivo selecionado: {{ documentData.document_front.name }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Documento Verso -->
                        <div class="border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg p-6">
                            <div class="text-center">
                                <i class="fas fa-id-card text-4xl text-gray-400 mb-4"></i>
                                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">
                                    Documento - Verso *
                                </h3>
                                <p class="text-sm text-blue-600 dark:text-blue-400 mb-4">
                                    Envie uma foto clara do verso do seu documento
                                </p>
                                <input 
                                    ref="documentBackInput"
                                    type="file" 
                                    accept="image/*"
                                    @change="handleFileUpload('document_back', $event)"
                                    class="hidden"
                                />
                                <button 
                                    @click="$refs.documentBackInput.click()"
                                    type="button"
                                    class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md transition-colors"
                                >
                                    Selecionar Arquivo
                                </button>
                                <div v-if="documentData.document_back" class="mt-4">
                                    <img :src="getPreviewUrl('document_back')" alt="Preview" class="mx-auto max-w-xs rounded-lg shadow-md">
                                    <p class="text-sm text-blue-600 dark:text-blue-400 mt-2">
                                        Arquivo selecionado: {{ documentData.document_back.name }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Selfie -->
                        <div class="border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg p-6">
                            <div class="text-center">
                                <i class="fas fa-camera text-4xl text-gray-400 mb-4"></i>
                                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">
                                    Selfie com Documento *
                                </h3>
                                <p class="text-sm text-blue-600 dark:text-blue-400 mb-4">
                                    Tire uma selfie segurando seu documento ao lado do rosto
                                </p>
                                <input 
                                    ref="selfieInput"
                                    type="file" 
                                    accept="image/*"
                                    @change="handleFileUpload('selfie', $event)"
                                    class="hidden"
                                />
                                <button 
                                    @click="$refs.selfieInput.click()"
                                    type="button"
                                    class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md transition-colors"
                                >
                                    Selecionar Arquivo
                                </button>
                                <div v-if="documentData.selfie" class="mt-4">
                                    <img :src="getPreviewUrl('selfie')" alt="Preview" class="mx-auto max-w-xs rounded-lg shadow-md">
                                    <p class="text-sm text-blue-600 dark:text-blue-400 mt-2">
                                        Arquivo selecionado: {{ documentData.selfie.name }}
                                    </p>
                                </div>
                            </div>
                        </div>


                    </div>

                    <!-- Botão de Envio -->
                    <div class="mt-8 flex justify-end">
                        <button 
                            @click="submitVerification"
                            :disabled="isLoading || !isFormValid"
                            class="bg-blue-600 hover:bg-blue-700 disabled:bg-gray-400 text-white px-8 py-3 rounded-md font-medium transition-colors flex items-center"
                        >
                            <i v-if="isLoading" class="fas fa-spinner fa-spin mr-2"></i>
                            {{ isLoading ? 'Enviando...' : 'Enviar Documentos' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </BaseLayout>
</template>

<script>
import BaseLayout from "@/Layouts/BaseLayout.vue";
import { useToast } from "vue-toastification";
import { validateNIF } from 'validate-nif'; // Importando a biblioteca de validação de NIF
import { useAuthStore } from "@/Stores/Auth.js";

export default {
    name: 'VerificationPage',
    components: { BaseLayout },
    data() {
        return {
            isLoading: false,
            verificationStatus: null,
            nifError: '',
            personalInfo: {
                full_name: '',
                document_number: '',
                birth_date: '',
                phone: '',
                country: 'PT'
            },
            documentData: {
                document_type: 'cc',
                document_front: null,
                document_back: null,
                selfie: null
            },
            documentTypes: [
                { value: 'cc', label: 'Cartão de Cidadão', icon: 'fas fa-id-card' },
                { value: 'passport', label: 'Passaporte', icon: 'fas fa-passport' },
                { value: 'carta_conducao', label: 'Carta de Condução', icon: 'fas fa-car' }
            ]
        }
    },
    computed: {
        isFormValid() {
            const personalValid = this.personalInfo.full_name && 
                                this.personalInfo.document_number && 
                                this.personalInfo.birth_date && 
                                this.personalInfo.phone &&
                                !this.nifError; // Incluir validação de NIF
            
            const documentsValid = this.documentData.document_front && 
                                 this.documentData.document_back && 
                                 this.documentData.selfie;
            
            return personalValid && documentsValid;
        },
        
        canShowUploadForm() {
            // Se não há status de verificação, pode mostrar o formulário
            if (!this.verificationStatus) {
                return true;
            }
            
            const status = this.verificationStatus.verification_status;
            const canResubmit = this.verificationStatus.can_resubmit;
            
            // Se já foi aprovado, não mostra o formulário
            if (status === 'approved') {
                return false;
            }
            
            // Se está pendente, não pode reenviar
            if (status === 'pending') {
                return false;
            }
            
            // Se foi rejeitado, verifica se pode reenviar
            if (status === 'rejected') {
                return canResubmit === true;
            }
            
            // Para outros casos, permite mostrar o formulário
            return true;
        }
    },
    async mounted() {
        await this.loadVerificationStatus();
        await this.loadUserProfile();
    },
    methods: {
        async loadVerificationStatus() {
            try {
                // Adiciona timestamp para evitar cache
                const timestamp = new Date().getTime();
                const response = await this.axios.get(`/api/profile/verification?t=${timestamp}`, {
                    headers: {
                        'Authorization': `Bearer ${localStorage.getItem('token')}`
                    }
                });
                
                if (response.data.status) {
                    this.verificationStatus = response.data.data;
                }
            } catch (error) {
                console.error('Erro ao carregar status da verificação:', error);
            }
        },
        
        async refreshStatus() {
            // Método para forçar atualização dos dados
            await this.loadVerificationStatus();
        },
        
        async loadUserProfile() {
            try {
                const authStore = useAuthStore();
                
                // Se já temos dados do usuário na store, usar esses dados
                if (authStore.user && authStore.user.phone) {
                    this.personalInfo.phone = authStore.user.phone;
                    console.log('Telemóvel pré-preenchido da store:', authStore.user.phone);
                    return;
                }
                
                // Caso contrário, buscar dados atualizados da API
                const response = await this.axios.get('/api/profile/', {
                    headers: {
                        'Authorization': `Bearer ${localStorage.getItem('token')}`
                    }
                });
                
                if (response.data.status && response.data.user) {
                    const userData = response.data.user;
                    
                    // Pré-preencher telemóvel se disponível
                    if (userData.phone && !this.personalInfo.phone) {
                        this.personalInfo.phone = userData.phone;
                        console.log('Telemóvel pré-preenchido da API:', userData.phone);
                    }
                    
                    // Pré-preencher nome se disponível
                    if (userData.name && !this.personalInfo.full_name) {
                        this.personalInfo.full_name = userData.name;
                        console.log('Nome pré-preenchido da API:', userData.name);
                    }
                }
            } catch (error) {
                console.error('Erro ao carregar dados do perfil:', error);
                // Não mostrar erro para o usuário, pois é apenas pré-preenchimento
            }
        },
        
        validateNIFInput() {
            const nif = this.personalInfo.document_number.trim();
            
            // Se o campo estiver vazio, não validamos
            if (!nif) {
                this.nifError = '';
                return true;
            }
            
            // Usar a biblioteca validate-nif para validar o NIF
            try {
                const isValid = validateNIF(nif);
                
                if (!isValid) {
                    this.nifError = 'O NIF fornecido não é válido';
                    return false;
                }
                
                // Limpar erro se a validação passar
                this.nifError = '';
                return true;
            } catch (error) {
                this.nifError = 'O NIF fornecido não é válido';
                return false;
            }
        },
        
        handleFileUpload(field, event) {
            const file = event.target.files[0];
            if (file) {
                // Validar tipo de arquivo
                if (!file.type.startsWith('image/')) {
                    useToast().error('Por favor, selecione apenas arquivos de imagem');
                    return;
                }
                
                // Validar tamanho (máximo 5MB)
                if (file.size > 5 * 1024 * 1024) {
                    useToast().error('O arquivo deve ter no máximo 5MB');
                    return;
                }
                
                this.documentData[field] = file;
            }
        },
        
        getPreviewUrl(field) {
            const file = this.documentData[field];
            if (file) {
                return URL.createObjectURL(file);
            }
            return null;
        },
        
        async submitVerification() {
            // Validar NIF antes do envio
            if (!this.validateNIFInput()) {
                useToast().error('Por favor, corrija o NIF antes de continuar');
                return;
            }
            
            if (!this.isFormValid) {
                useToast().error('Por favor, preencha todos os campos obrigatórios');
                return;
            }
            
            this.isLoading = true;
            
            try {
                // Enviar tudo em uma única chamada atômica
                const formData = new FormData();
                
                // Dados pessoais
                formData.append('full_name', this.personalInfo.full_name);
                formData.append('birth_date', this.personalInfo.birth_date);
                formData.append('document_number', this.personalInfo.document_number);
                formData.append('phone', this.personalInfo.phone);
                formData.append('address', this.personalInfo.address);
                formData.append('city', this.personalInfo.city);
                formData.append('postal_code', this.personalInfo.postal_code);
                formData.append('country', this.personalInfo.country);
                
                // Documentos
                formData.append('document_type', this.documentData.document_type);
                formData.append('document_front', this.documentData.document_front);
                formData.append('document_back', this.documentData.document_back);
                formData.append('selfie', this.documentData.selfie);
                
                const response = await this.axios.post('/api/profile/verification/upload', formData, {
                    headers: {
                        'Authorization': `Bearer ${localStorage.getItem('token')}`,
                        'Content-Type': 'multipart/form-data'
                    }
                });
                
                if (response.data.status) {
                    useToast().success('Documentos enviados com sucesso! Aguarde a análise.');
                    await this.loadVerificationStatus();
                } else {
                    throw new Error(response.data.message || 'Erro ao enviar documentos');
                }
                
            } catch (error) {
                console.error('Erro ao enviar verificação:', error);
                useToast().error(error.response?.data?.message || error.message || 'Erro ao enviar documentos');
            } finally {
                this.isLoading = false;
            }
        },
        
        getStatusLabel() {
            if (!this.verificationStatus) return 'Não enviado';
            
            switch (this.verificationStatus.verification_status) {
                case 'pending': return 'Pendente';
                case 'approved': return 'Aprovado';
                case 'rejected': return 'Rejeitado';
                default: return 'Não informado';
            }
        },
        
        getStatusMessage() {
            if (!this.verificationStatus) return 'Nenhum documento foi enviado ainda.';
            
            switch (this.verificationStatus.verification_status) {
                case 'pending': return 'Seus documentos estão sendo analisados. Aguarde o resultado.';
                case 'approved': return 'Seus documentos foram aprovados com sucesso!';
                case 'rejected': {
                    if (this.verificationStatus.can_resubmit === false) {
                        if (this.verificationStatus.submission_attempts >= this.verificationStatus.max_attempts) {
                            return 'Seus documentos foram rejeitados. Limite de tentativas excedido.';
                        } else if (this.verificationStatus.cooldown_hours > 0) {
                            return `Seus documentos foram rejeitados. Aguarde ${this.verificationStatus.cooldown_hours} horas para reenviar.`;
                        } else {
                            return 'Seus documentos foram rejeitados. Reenvio não permitido.';
                        }
                    }
                    return 'Seus documentos foram rejeitados. Veja o motivo abaixo e envie novamente.';
                }
                default: return 'Status não informado.';
            }
        },
        
        getStatusBadgeClass() {
            if (!this.verificationStatus) return 'bg-blue-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300';
            
            switch (this.verificationStatus.verification_status) {
                case 'pending': return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-300';
                case 'approved': return 'bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-300';
                case 'rejected': return 'bg-red-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-300';
                default: return 'bg-blue-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300';
            }
        }
    }
};
</script>

<style scoped>
/* Estilos específicos para a página de verificação */
</style>
