import { defineStore } from "pinia";
import { ref } from "vue";
import HttpApi from "@/Services/HttpApi.js";

export const usePopupStore = defineStore("popup", () => {
    // Constantes para os contextos de pop-up
    const POPUP_CONTEXTS = {
        ALL_ACTIVE: 'all-active',
        LOGIN: 'login',
        REGISTER: 'register',
        WITH_DEPOSIT: 'with-deposit',
        WITHOUT_DEPOSIT: 'without-deposit',
        BY_DEPOSIT_STATUS: 'by-deposit-status'
    };
    // Estado do pop-up
    const showAuthPopup = ref(false);
    const popupTitle = ref('Bem-vindo!');
    const popupMessage = ref('Obrigado por fazer login. Aproveite nossa plataforma!');
    const popupImage = ref(null);
    const popupButtonText = ref('Entendi');
    const redirectUrl = ref(null);
    const showOnlyOnce = ref(false);
    const requireRedemption = ref(false);
    const browserPersistent = ref(false); // Novo estado para persistência por navegador
    const conditionFunction = ref(null);
    const isLoading = ref(false);
    const currentPopupId = ref(null);
    // Propriedades de freespin
    const gameFreespinActive = ref(false);
    const gameCodeFreespin = ref(null);
    const gameNameFreespin = ref(null);
    const roundsFreespin = ref(0);

    /**
     * Mostrar o pop-up com configurações personalizadas
     * @param {Object} config - Configurações do pop-up
     */
    function showPopup(config = {}) {
        if (config.title) popupTitle.value = config.title;
        if (config.message) popupMessage.value = config.message;
        if (config.image) popupImage.value = config.image;
        if (config.buttonText) popupButtonText.value = config.buttonText;
        if (config.redirectUrl) redirectUrl.value = config.redirectUrl;
        if (config.showOnlyOnce !== undefined) showOnlyOnce.value = config.showOnlyOnce;
        if (config.requireRedemption !== undefined) requireRedemption.value = config.requireRedemption;
        if (config.browser_persistent !== undefined) browserPersistent.value = config.browser_persistent; // Atribuir o valor de browser_persistent
        if (config.condition) conditionFunction.value = config.condition;
        if (config.id) currentPopupId.value = config.id;
        
        // Processar propriedades de freespin
        if (config.game_free_rounds_active_popup !== undefined) gameFreespinActive.value = config.game_free_rounds_active_popup;
        if (config.game_code_rounds_free_popup !== undefined) gameCodeFreespin.value = config.game_code_rounds_free_popup;
        if (config.game_name_rounds_free_popup !== undefined) gameNameFreespin.value = config.game_name_rounds_free_popup;
        if (config.rounds_free_popup !== undefined) roundsFreespin.value = config.rounds_free_popup;
        
        showAuthPopup.value = true;
    }

    /**
     * Reseta o estado do pop-up para os valores padrão
     */
    function resetPopupState() {
        popupTitle.value = 'Bem-vindo!';
        popupMessage.value = 'Obrigado por fazer login. Aproveite nossa plataforma!';
        popupImage.value = null;
        popupButtonText.value = 'Entendi';
        redirectUrl.value = null;
        showOnlyOnce.value = false;
        requireRedemption.value = false;
        browserPersistent.value = false;
        conditionFunction.value = null;
        currentPopupId.value = null;
        gameFreespinActive.value = false;
        gameCodeFreespin.value = null;
        gameNameFreespin.value = null;
        roundsFreespin.value = 0;
    }

    /**
     * Esconder o pop-up
     */
    function hidePopup() {
        showAuthPopup.value = false;
        // Resetar o estado para evitar que dados antigos afetem o próximo pop-up
        resetPopupState();
    }

    /**
     * Buscar pop-up de login do servidor
     */
    async function fetchLoginPopup() {
        isLoading.value = true;
        try {
            // console.log('Buscando pop-up de login do servidor...');
            const response = await HttpApi.get('popups/login');
            // console.log('Resposta do servidor (login):', response.data);
            
            if (response.data.success && response.data.popup) {
                const popup = response.data.popup;
                // console.log('Pop-up de login encontrado:', popup);
                showPopup({
                    title: popup.title,
                    message: popup.message,
                    image: popup.image,
                    buttonText: popup.button_text,
                    showOnlyOnce: popup.show_only_once
                });
                return true;
            }
            console.log('Nenhum pop-up de login encontrado no servidor');
            return false;
        } catch (error) {
            console.error('Erro ao buscar pop-up de login:', error);
            return false;
        } finally {
            isLoading.value = false;
        }
    }

    /**
     * Buscar pop-up de registro do servidor
     */
    async function fetchRegisterPopup() {
        isLoading.value = true;
        try {
            // console.log('Buscando pop-up de registro do servidor...');
            const response = await HttpApi.get('popups/register');
            // console.log('Resposta do servidor (registro):', response.data);
            
            if (response.data.success && response.data.popup) {
                const popup = response.data.popup;
                // console.log('Pop-up de registro encontrado:', popup);
                showPopup({
                    title: popup.title,
                    message: popup.message,
                    image: popup.image,
                    buttonText: popup.button_text,
                    showOnlyOnce: popup.show_only_once
                });
                return true;
            }
            // console.log('Nenhum pop-up de registro encontrado no servidor');
            return false;
        } catch (error) {
            // console.error('Erro ao buscar pop-up de registro:', error);
            return false;
        } finally {
            isLoading.value = false;
        }
    }
    
    /**
     * Buscar pop-up para usuários com depósito realizado
     */
    async function fetchWithDepositPopup() {
        isLoading.value = true;
        try {
            // console.log('Buscando pop-up para usuários com depósito...');
            const response = await HttpApi.get('popups/with-deposit');
            // console.log('Resposta do servidor (com depósito):', response.data);
            
            if (response.data.success && response.data.popup) {
                const popup = response.data.popup;
                // console.log('Pop-up para usuários com depósito encontrado:', popup);
                showPopup({
                    title: popup.title,
                    message: popup.message,
                    image: popup.image,
                    buttonText: popup.button_text,
                    showOnlyOnce: popup.show_only_once
                });
                return true;
            }
            // console.log('Nenhum pop-up para usuários com depósito encontrado no servidor');
            return false;
        } catch (error) {
            // console.error('Erro ao buscar pop-up para usuários com depósito:', error);
            return false;
        } finally {
            isLoading.value = false;
        }
    }
    
    /**
     * Buscar pop-up para usuários sem depósito realizado
     */
    async function fetchWithoutDepositPopup() {
        isLoading.value = true;
        try {
            // console.log('Buscando pop-up para usuários sem depósito...');
            const response = await HttpApi.get('popups/without-deposit');
            // console.log('Resposta do servidor (sem depósito):', response.data);
            
            if (response.data.success && response.data.popup) {
                const popup = response.data.popup;
                // console.log('Pop-up para usuários sem depósito encontrado:', popup);
                showPopup({
                    title: popup.title,
                    message: popup.message,
                    image: popup.image,
                    buttonText: popup.button_text,
                    showOnlyOnce: popup.show_only_once
                });
                return true;
            }
            // console.log('Nenhum pop-up para usuários sem depósito encontrado no servidor');
            return false;
        } catch (error) {
            // console.error('Erro ao buscar pop-up para usuários sem depósito:', error);
            return false;
        } finally {
            isLoading.value = false;
        }
    }
    
    /**
     * Buscar pop-up com base no status de depósito do usuário autenticado
     * O backend determina automaticamente se o usuário tem ou não depósito
     */
    async function fetchPopupByDepositStatus() {
        isLoading.value = true;
        try {
            // console.log('Buscando pop-up com base no status de depósito...');
            const response = await HttpApi.get('popups/by-deposit-status');
            // console.log('Resposta do servidor (status de depósito):', response.data);
            
            if (response.data.success && response.data.popup) {
                const popup = response.data.popup;
                // console.log('Pop-up baseado no status de depósito encontrado:', popup);
                showPopup({
                    title: popup.title,
                    message: popup.message,
                    image: popup.image,
                    buttonText: popup.button_text,
                    showOnlyOnce: popup.show_only_once
                });
                return true;
            }
            // console.log('Nenhum pop-up baseado no status de depósito encontrado no servidor');
            return false;
        } catch (error) {
            // console.error('Erro ao buscar pop-up baseado no status de depósito:', error);
            return false;
        } finally {
            isLoading.value = false;
        }
    }

    /**
     * @deprecated Use fetchLoginPopup() diretamente
     * Esta função é mantida apenas para compatibilidade com código existente
     */
    async function showWelcomePopup(username) {
        console.log('showWelcomePopup está obsoleto, use fetchLoginPopup() diretamente');
        return await fetchLoginPopup();
    }

    /**
     * Define uma condição personalizada para exibição do pop-up
     * @param {Function} condition - Função que retorna true/false
     */
    function setCondition(condition) {
        conditionFunction.value = condition;
    }

    /**
     * Mostrar pop-up com base em uma condição específica
     * @param {Object} config - Configurações do pop-up
     * @param {Function} condition - Função que retorna true/false
     */
    function showPopupWithCondition(config = {}, condition) {
        setCondition(condition);
        showPopup(config);
    }

    /**
     * @deprecated Use fetchRegisterPopup() diretamente
     * Esta função é mantida apenas para compatibilidade com código existente
     */
    async function showRegisterPopup(username) {
        console.log('showRegisterPopup está obsoleto, use fetchRegisterPopup() diretamente');
        return await fetchRegisterPopup();
    }

    /**
     * Função geral para buscar qualquer tipo de pop-up com base no contexto
     * @param {string} context - O contexto para buscar o pop-up (login, register, with-deposit, without-deposit, by-deposit-status)
     * @param {Object} options - Opções adicionais para a busca
     * @returns {Promise<boolean>} - true se um pop-up foi encontrado e exibido, false caso contrário
     */
    async function fetchPopup(context, options = {}) {
        isLoading.value = true;
        try {
            // console.log(`Buscando pop-up para contexto: ${context}...`);
            const response = await HttpApi.get(`popups/${context}`);
            // console.log(`Resposta do servidor (${context}):`, response.data);
            
            if (response.data.success && response.data.popup) {
                const popup = response.data.popup;
                // console.log(`Pop-up para contexto ${context} encontrado:`, popup);
                showPopup({ ...popup, ...options }); // Passa o objeto popup inteiro
                return true;
            }
            // console.log(`Nenhum pop-up para contexto ${context} encontrado no servidor`);
            return false;
        } catch (error) {
            // console.error(`Erro ao buscar pop-up para contexto ${context}:`, error);
            return false;
        } finally {
            isLoading.value = false;
        }
    }

    // Redefine as funções específicas para usar a função geral
    async function fetchLoginPopup(options = {}) {
        return await fetchPopup(POPUP_CONTEXTS.LOGIN, options);
    }

    async function fetchRegisterPopup(options = {}) {
        return await fetchPopup(POPUP_CONTEXTS.REGISTER, options);
    }

    async function fetchWithDepositPopup(options = {}) {
        return await fetchPopup(POPUP_CONTEXTS.WITH_DEPOSIT, options);
    }

    async function fetchWithoutDepositPopup(options = {}) {
        return await fetchPopup(POPUP_CONTEXTS.WITHOUT_DEPOSIT, options);
    }

    async function fetchPopupByDepositStatus(options = {}) {
        return await fetchPopup(POPUP_CONTEXTS.BY_DEPOSIT_STATUS, options);
    }
    
    /**
     * Busca todos os pop-ups ativos e retorna a lista completa
     * @param {Object} options - Opções adicionais para a busca
     * @param {String} influencerCode - Código do influencer para filtrar os pop-ups
     * @returns {Promise<Array|null>} - Array de pop-ups ativos ou null se nenhum for encontrado
     */
    async function fetchAllActivePopups(options = {}, influencerCode = null) {
        isLoading.value = true;
        try {
            // Obter o código do influencer do localStorage se não foi fornecido
            if (!influencerCode) {
                influencerCode = localStorage.getItem('influencer_code') || null;
            }
            
            // console.log(`Buscando todos os pop-ups ativos${influencerCode ? ` para influencer: ${influencerCode}` : ''}...`);
            
            // Adicionar o código do influencer como parâmetro de query
            // Importante: o backend espera o parâmetro como 'influencer_code'
            const queryParams = influencerCode ? `?influencer_code=${encodeURIComponent(influencerCode)}` : '';
            const response = await HttpApi.get(`popups/${POPUP_CONTEXTS.ALL_ACTIVE}${queryParams}`);
            // console.log('Resposta do servidor (todos os pop-ups ativos):', response.data);
            
            if (response.data.success && response.data.popups && response.data.popups.length > 0) {
                // console.log(`${response.data.popups.length} pop-ups ativos encontrados`);
                
                // Log detalhado de cada pop-up
                // Comentado para produção
                // response.data.popups.forEach(popup => {
                //     console.log('Detalhes do pop-up:', {
                //         id: popup.id,
                //         title: popup.title,
                //         target_user_type: popup.target_user_type,
                //         tipo_exato: typeof popup.target_user_type,
                //         show_only_once: popup.show_only_once
                //     });
                // });
                
                return response.data.popups;
            }
            // console.log('Nenhum pop-up ativo encontrado no servidor');
            return null;
        } catch (error) {
            // console.error('Erro ao buscar pop-ups ativos:', error);
            return null;
        } finally {
            isLoading.value = false;
        }
    }

    return {
        showAuthPopup,
        popupTitle,
        popupMessage,
        popupImage,
        popupButtonText,
        redirectUrl,
        showOnlyOnce,
        requireRedemption,
        browserPersistent, // Expor o novo estado
        conditionFunction,
        isLoading,
        currentPopupId,
        // Propriedades de freespin
        gameFreespinActive,
        gameCodeFreespin,
        roundsFreespin,
        // Métodos
        showPopup,
        hidePopup,
        showWelcomePopup,
        showRegisterPopup,
        fetchPopup,
        fetchLoginPopup,
        fetchRegisterPopup,
        fetchWithDepositPopup,
        fetchWithoutDepositPopup,
        fetchPopupByDepositStatus,
        fetchAllActivePopups,
        POPUP_CONTEXTS
    };
});
