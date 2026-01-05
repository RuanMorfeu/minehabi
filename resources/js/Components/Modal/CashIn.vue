
<script setup>
import { computed, onMounted, ref, watch, reactive } from "vue";
import { useToast } from "vue-toastification";
import { useForm, usePage } from "@inertiajs/vue3";
import { route } from "ziggy-js";
import axios from "axios";
import InputLabel from "@/Components/InputLabel.vue";
import TextInput from "@/Components/TextInput.vue";
import Checkbox from "@/Components/Checkbox.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import Dialog from "@/Components/Modal/Dialog.vue";
import MollieCardForm from '@/Components/MollieCardForm.vue'
import MollieApplePayForm from '@/Components/MollieApplePayForm.vue'
import MollieGooglePayForm from '@/Components/MollieGooglePayForm.vue'
import MollieMBWayForm from '@/Components/MollieMBWayForm.vue'
import MollieMultibancoForm from '@/Components/MollieMultibancoForm.vue'
import HttpApi from "@/Services/HttpApi.js";
import { useAuthStore } from "@/Stores/Auth.js";
import { useSettingStore } from "@/Stores/SettingStore.js";

const gatewayDefaultUrl = ref('');
const loadingDepositMethods = ref(false);
const showInputVoucher = ref(false);
const depositGateway = ref(false);
const depositMethods = ref([]);
const depositResponse = ref({
    entidade: '',
    referencia: ''
});
const paymentTypes = ref({
    payment_types: {}
});
const loadingPaymentApi = ref(true);
const isSubmitting = ref(false);
const hasDeposits = ref(false); // Controla se o usuário já fez algum depósito
const depositCount = ref(0); // Controla quantos depósitos o usuário já fez
const showMollieEmbedded = ref(false); // Controla se mostra o checkout embebido do Mollie
const mollieProfileId = ref(''); // Profile ID do Mollie para Components

// Inicializar o settingStore
const settingStore = useSettingStore();

// Computed para verificar se é MBWay via Mollie
const isMbwayMollie = computed(() => {
    return form.deposit_method_slug === 'mbway' && setting.mbway_gateway === 'mollie';
});

// Computed para verificar se deve mostrar campo telefone
const shouldShowPhoneField = computed(() => {
    const result = form.deposit_method_slug === 'mbway' && !isMbwayMollie.value;
    return result;
});

// Função para detectar dispositivos Apple
const isAppleDevice = () => {
    const userAgent = navigator.userAgent || navigator.vendor || window.opera;
    
    // Detectar iOS (iPhone, iPad, iPod)
    const isIOS = /iPad|iPhone|iPod/.test(userAgent) && !window.MSStream;
    
    // Detectar macOS (Safari no Mac)
    const isMac = /Macintosh|MacIntel|MacPPC|Mac68K/.test(userAgent);
    
    // Detectar Safari no Mac (para Apple Pay web)
    const isSafariMac = isMac && /Safari/.test(userAgent) && !/Chrome|Chromium|Edge/.test(userAgent);
    
    return isIOS || isSafariMac;
};

// Computed para filtrar métodos de pagamento baseado no dispositivo
const filteredPaymentTypes = computed(() => {
    if (!paymentTypes.value?.payment_types) {
        return [];
    }
    
    // Converter objeto para array se necessário
    const types = Array.isArray(paymentTypes.value.payment_types) 
        ? paymentTypes.value.payment_types 
        : Object.values(paymentTypes.value.payment_types);
    
    // Filtrar Apple Pay apenas para dispositivos Apple
    return types.filter(type => {
        if (type.slug === 'mollie-applepay') {
            return isAppleDevice();
        }
        return true;
    });
});

// Buscar configuração do Mollie ao montar o componente
onMounted(async () => {
    try {
        const response = await HttpApi.get('mollie/config');
        
        if (response.data.status && response.data.profile_id) {
            mollieProfileId.value = response.data.profile_id;
        } else {
            // Fallback: usar Profile ID hardcoded para desenvolvimento
            mollieProfileId.value = 'pfl_oA69gFvKfj';
        }
    } catch (error) {
        // Fallback: usar Profile ID hardcoded para desenvolvimento
        mollieProfileId.value = 'pfl_oA69gFvKfj';
    }
});

// Objeto para armazenar nomes de jogos
const gameNames = ref({});

// Função para calcular os giros grátis para um valor específico
function calculateFreespinsForAmount(amount) {
    if (!amount || isNaN(parseFloat(amount)) || parseFloat(amount) <= 0) {
        return null;
    }
    
    const depositAmount = parseFloat(amount);
    const settings = settingStore.getSettingData();
    if (!settings) {
        return null;
    }
    
    try {
        // Verificar se é o primeiro depósito do usuário
        if (depositCount.value === 0 && settings.game_free_rounds_active_deposit) {
            // Verificar categoria 4 (prioridade maior)
            if (settings.amount_rounds_free_deposit_cat4_min && 
                depositAmount >= settings.amount_rounds_free_deposit_cat4_min && 
                (!settings.amount_rounds_free_deposit_cat4_max || depositAmount <= settings.amount_rounds_free_deposit_cat4_max) && 
                settings.rounds_free_deposit_cat4) {
                return settings.rounds_free_deposit_cat4;
            }
            
            // Verificar categoria 3
            if (settings.amount_rounds_free_deposit_cat3_min && 
                depositAmount >= settings.amount_rounds_free_deposit_cat3_min && 
                (!settings.amount_rounds_free_deposit_cat3_max || depositAmount <= settings.amount_rounds_free_deposit_cat3_max) && 
                settings.rounds_free_deposit_cat3) {
                return settings.rounds_free_deposit_cat3;
            }
            
            // Verificar categoria 2
            if (settings.amount_rounds_free_deposit_cat2_min && 
                depositAmount >= settings.amount_rounds_free_deposit_cat2_min && 
                (!settings.amount_rounds_free_deposit_cat2_max || depositAmount <= settings.amount_rounds_free_deposit_cat2_max) && 
                settings.rounds_free_deposit_cat2) {
                return settings.rounds_free_deposit_cat2;
            }
            
            // Verificar categoria 1
            if (settings.amount_rounds_free_deposit_cat1_min && 
                depositAmount >= settings.amount_rounds_free_deposit_cat1_min && 
                (!settings.amount_rounds_free_deposit_cat1_max || depositAmount <= settings.amount_rounds_free_deposit_cat1_max) && 
                settings.rounds_free_deposit_cat1) {
                return settings.rounds_free_deposit_cat1;
            }
        }
        
        // Verificar se é um depósito subsequente
        if (depositCount.value > 0 && settings.game_free_rounds_active_any_deposit) {
            // Verificar categoria 4 (prioridade maior)
            if (settings.amount_rounds_free_any_deposit_cat4_min && 
                depositAmount >= settings.amount_rounds_free_any_deposit_cat4_min && 
                (!settings.amount_rounds_free_any_deposit_cat4_max || depositAmount <= settings.amount_rounds_free_any_deposit_cat4_max) && 
                settings.rounds_free_any_deposit_cat4) {
                return settings.rounds_free_any_deposit_cat4;
            }
            
            // Verificar categoria 3
            if (settings.amount_rounds_free_any_deposit_cat3_min && 
                depositAmount >= settings.amount_rounds_free_any_deposit_cat3_min && 
                (!settings.amount_rounds_free_any_deposit_cat3_max || depositAmount <= settings.amount_rounds_free_any_deposit_cat3_max) && 
                settings.rounds_free_any_deposit_cat3) {
                return settings.rounds_free_any_deposit_cat3;
            }
            
            // Verificar categoria 2
            if (settings.amount_rounds_free_any_deposit_cat2_min && 
                depositAmount >= settings.amount_rounds_free_any_deposit_cat2_min && 
                (!settings.amount_rounds_free_any_deposit_cat2_max || depositAmount <= settings.amount_rounds_free_any_deposit_cat2_max) && 
                settings.rounds_free_any_deposit_cat2) {
                return settings.rounds_free_any_deposit_cat2;
            }
            
            // Verificar categoria 1
            if (settings.amount_rounds_free_any_deposit_cat1_min && 
                depositAmount >= settings.amount_rounds_free_any_deposit_cat1_min && 
                (!settings.amount_rounds_free_any_deposit_cat1_max || depositAmount <= settings.amount_rounds_free_any_deposit_cat1_max) && 
                settings.rounds_free_any_deposit_cat1) {
                return settings.rounds_free_any_deposit_cat1;
            }
        }
        
        return null;
    } catch (error) {
        console.error('Erro ao calcular giros grátis:', error);
        return null;
    }
}

// Computed property para verificar e calcular os giros grátis com base no valor depositado
const freespinsInfo = computed(() => {
    // Verificar se o formulário e o valor estão definidos
    if (!form || !form.amount || form.amount === '' || isNaN(parseFloat(form.amount))) {
        return null;
    }
    
    const depositAmount = parseFloat(form.amount);
    if (depositAmount <= 0) {
        return null;
    }
    
    const settings = settingStore.getSettingData();
    if (!settings) {
        return null;
    }
    
    try {
        // Verificar se é o primeiro depósito do usuário
        if (depositCount.value === 0 && settings.game_free_rounds_active_deposit) {
            
            // Verificar categoria 4 (prioridade maior)
            if (settings.amount_rounds_free_deposit_cat4_min && 
                depositAmount >= settings.amount_rounds_free_deposit_cat4_min && 
                (!settings.amount_rounds_free_deposit_cat4_max || depositAmount <= settings.amount_rounds_free_deposit_cat4_max) && 
                settings.rounds_free_deposit_cat4) {
                return {
                    rounds: settings.rounds_free_deposit_cat4,
                    gameName: settings.freespin_game_name || getGameName(settings.game_code_rounds_free_deposit)
                };
            }
            
            // Verificar categoria 3
            if (settings.amount_rounds_free_deposit_cat3_min && 
                depositAmount >= settings.amount_rounds_free_deposit_cat3_min && 
                (!settings.amount_rounds_free_deposit_cat3_max || depositAmount <= settings.amount_rounds_free_deposit_cat3_max) && 
                settings.rounds_free_deposit_cat3) {
                return {
                    rounds: settings.rounds_free_deposit_cat3,
                    gameName: settings.freespin_game_name || getGameName(settings.game_code_rounds_free_deposit)
                };
            }
            
            // Verificar categoria 2
            if (settings.amount_rounds_free_deposit_cat2_min && 
                depositAmount >= settings.amount_rounds_free_deposit_cat2_min && 
                (!settings.amount_rounds_free_deposit_cat2_max || depositAmount <= settings.amount_rounds_free_deposit_cat2_max) && 
                settings.rounds_free_deposit_cat2) {
                return {
                    rounds: settings.rounds_free_deposit_cat2,
                    gameName: settings.freespin_game_name || getGameName(settings.game_code_rounds_free_deposit)
                };
            }
            
            // Verificar categoria 1 (adicionada para o primeiro depósito)
            if (settings.amount_rounds_free_deposit_cat1_min && 
                depositAmount >= settings.amount_rounds_free_deposit_cat1_min && 
                (!settings.amount_rounds_free_deposit_cat1_max || depositAmount <= settings.amount_rounds_free_deposit_cat1_max) && 
                settings.rounds_free_deposit_cat1) {
                return {
                    rounds: settings.rounds_free_deposit_cat1,
                    gameName: settings.freespin_game_name || getGameName(settings.game_code_rounds_free_deposit)
                };
            }
        }
        
        // Verificar se é um depósito subsequente
        if (depositCount.value > 0 && settings.game_free_rounds_active_any_deposit) {
            // Verificar categoria 4 (prioridade maior)
            if (settings.amount_rounds_free_any_deposit_cat4_min && 
                depositAmount >= settings.amount_rounds_free_any_deposit_cat4_min && 
                (!settings.amount_rounds_free_any_deposit_cat4_max || depositAmount <= settings.amount_rounds_free_any_deposit_cat4_max) && 
                settings.rounds_free_any_deposit_cat4) {
                return {
                    rounds: settings.rounds_free_any_deposit_cat4,
                    gameName: settings.freespin_game_name || getGameName(settings.game_code_rounds_free_any_deposit)
                };
            }
            
            // Verificar categoria 3
            if (settings.amount_rounds_free_any_deposit_cat3_min && 
                depositAmount >= settings.amount_rounds_free_any_deposit_cat3_min && 
                (!settings.amount_rounds_free_any_deposit_cat3_max || depositAmount <= settings.amount_rounds_free_any_deposit_cat3_max) && 
                settings.rounds_free_any_deposit_cat3) {
                return {
                    rounds: settings.rounds_free_any_deposit_cat3,
                    gameName: settings.freespin_game_name || getGameName(settings.game_code_rounds_free_any_deposit)
                };
            }
            
            // Verificar categoria 2
            if (settings.amount_rounds_free_any_deposit_cat2_min && 
                depositAmount >= settings.amount_rounds_free_any_deposit_cat2_min && 
                (!settings.amount_rounds_free_any_deposit_cat2_max || depositAmount <= settings.amount_rounds_free_any_deposit_cat2_max) && 
                settings.rounds_free_any_deposit_cat2) {
                return {
                    rounds: settings.rounds_free_any_deposit_cat2,
                    gameName: settings.freespin_game_name || getGameName(settings.game_code_rounds_free_any_deposit)
                };
            }
            
            // Verificar categoria 1
            if (settings.amount_rounds_free_any_deposit_cat1_min && 
                depositAmount >= settings.amount_rounds_free_any_deposit_cat1_min && 
                (!settings.amount_rounds_free_any_deposit_cat1_max || depositAmount <= settings.amount_rounds_free_any_deposit_cat1_max) && 
                settings.rounds_free_any_deposit_cat1) {
                return {
                    rounds: settings.rounds_free_any_deposit_cat1,
                    gameName: settings.freespin_game_name || getGameName(settings.game_code_rounds_free_any_deposit)
                };
            }
        }
        
        return null;
    } catch (error) {
        return null;
    }
});

// Função para obter o nome do jogo a partir do código
function getGameName(gameCode) {
    if (!gameCode) return 'selecionado';
    
    // Verificar se já temos o nome do jogo em cache
    if (gameNames.value[gameCode]) {
        return gameNames.value[gameCode];
    }
    
    // Nomes de jogos populares (fallback para caso não consiga buscar da API)
    const popularGames = {
        'pragmatic_sweet_bonanza': 'Sweet Bonanza',
        'pragmatic_wolf_gold': 'Wolf Gold',
        'pragmatic_gates_of_olympus': 'Gates of Olympus',
        'pragmatic_fruit_party': 'Fruit Party',
        'pragmatic_dog_house': 'The Dog House',
        'pragmatic_wild_west_gold': 'Wild West Gold',
        'pragmatic_big_bass_bonanza': 'Big Bass Bonanza',
        'pragmatic_buffalo_king': 'Buffalo King',
        'pragmatic_release_the_kraken': 'Release the Kraken',
        'pragmatic_joker_jewels': 'Joker Jewels'
    };
    
    // Verificar se é um jogo popular conhecido
    if (popularGames[gameCode]) {
        return popularGames[gameCode];
    }
    
    // Tentar formatar o nome do jogo a partir do código
    if (gameCode && gameCode.includes('_')) {
        const parts = gameCode.split('_');
        const provider = parts[0].charAt(0).toUpperCase() + parts[0].slice(1);
        const gameName = parts.slice(1).map(part => part.charAt(0).toUpperCase() + part.slice(1)).join(' ');
        return gameName;
    }
    
    // Retornar o código do jogo como fallback
    return gameCode || 'Jogo Selecionado';
}

const errors = ref({
    amount: '',
    phone: ''
});

const page = usePage();

// Verificar se o usuário está bloqueado para depósitos
const isDepositBlocked = ref(false);

// Função para verificar se depósitos estão bloqueados
async function checkDepositBlock() {
    if (!props.auth) {
        isDepositBlocked.value = false;
        return;
    }
    
    try {
        const response = await HttpApi.get('auth/verify');
        if (response.data) {
            isDepositBlocked.value = response.data.block_deposits === true;
        }
    } catch (error) {
        console.error('Erro ao verificar bloqueio de depósitos:', error);
        isDepositBlocked.value = false;
    }
}

// Configurações do bônus de influencer
const bonusConfig = ref({
    influencer_bonus_active: false,
    influencer_bonus_code: null
});

// Lista de bônus de influencer ativos
const influencerBonuses = ref([]);

// Bônus de influencer selecionado
const selectedInfluencerBonus = ref(null);

// Controla se o usuário já resgatou o bônus selecionado
const alreadyRedeemedBonus = ref(false);

// Buscar bônus de influencer ativos quando o componente for montado
async function fetchInfluencerBonuses() {
    try {

        const response = await HttpApi.get('influencer-bonuses');

        
        // A resposta da API está na propriedade 'data' do objeto de resposta.
        if (response && Array.isArray(response.data)) {

            influencerBonuses.value = response.data;
        } else {
            // Caso a resposta não tenha o formato esperado.
            console.error('[DEBUG-BONUS] Formato de resposta da API de bônus inesperado:', response);
            influencerBonuses.value = [];
        }
        
        // Filtrar bônus de uso único que já foram resgatados pelo usuário
        // Verifica os bônus resgatados apenas se o usuário estiver autenticado
        try {
            await checkRedeemedBonuses();


        } catch (error) {
            console.error('[DEBUG-BONUS] Erro ao verificar bônus resgatados:', error);
        }
        


        
        // Estado do localStorage
        const localStorageState = {
            browser_redeemed: JSON.parse(localStorage.getItem('browser_redeemed_influencer_bonuses') || '[]'),
            user_redeemed: JSON.parse(localStorage.getItem(`redeemed_bonuses_guest`) || '[]'),
            current_code: getInfluencerCode()
        };

        
        if (influencerBonuses.value.length > 0) {

            // Verificar campos esperados
            const firstBonus = influencerBonuses.value[0];
            const bonusFields = {
                id: firstBonus.id,
                name: firstBonus.name,
                code: firstBonus.code,
                bonus_percentage: firstBonus.bonus_percentage,
                max_bonus: firstBonus.max_bonus,
                min_deposit: firstBonus.min_deposit,
                is_active: firstBonus.is_active
            };

        }
        
        // Verifica se há um código de influencer e atualiza o bônus selecionado
        const currentCode = getInfluencerCode();
        if (currentCode) {

            selectedInfluencerBonus.value = getInfluencerBonusByCode(currentCode);

        }
    } catch (error) {
        console.error('[DEBUG-BONUS] Erro ao carregar bônus de influencer:', error);
    }
}

// Verifica quais bônus já foram resgatados pelo usuário atual
async function checkRedeemedBonuses() {
    try {
        const currentCode = getInfluencerCode();
        if (!currentCode) {
            alreadyRedeemedBonus.value = false;
            return;
        }

        const bonus = getInfluencerBonusByCode(currentCode);


        // PRIORIDADE ABSOLUTA: Verificar se o bônus é persistente no navegador e já foi resgatado
        // Exatamente como no AuthPopup.vue
        if (bonus && bonus.browser_persistent) {

            const browserRedeemedBonuses = JSON.parse(localStorage.getItem('browser_redeemed_influencer_bonuses')) || [];

            
            if (browserRedeemedBonuses.includes(currentCode)) {

                alreadyRedeemedBonus.value = true;
                return; // Retorna imediatamente, sem verificar API
            }

        }

        // Se chegou aqui, o bônus não é persistente no navegador ou ainda não foi resgatado
        // Verificar se o usuário está autenticado para consultar a API
        if (props.auth) {

            try {
                const response = await HttpApi.get(`influencer-bonuses/check-redemption/${currentCode}`);
                if (response.data && response.data.already_redeemed) {

                    alreadyRedeemedBonus.value = true;
                    
                    // Se o bônus é persistente no navegador, marca como resgatado para todos
                    if (bonus && bonus.browser_persistent) {

                        markBonusAsRedeemedInBrowser(currentCode);
                    }
                } else {

                    alreadyRedeemedBonus.value = false;
                }
                return;
            } catch (error) {
                console.error('[LOG] Erro na API, tratando como guest.', error);
            }
        }

        // Se chegou aqui, é um usuário não autenticado ou houve erro na API
        // Verificar se o bônus já foi resgatado por este usuário guest
        let userId = 'guest';
        const userRedeemedBonuses = JSON.parse(localStorage.getItem(`redeemed_bonuses_${userId}`)) || [];
        
        if (userRedeemedBonuses.includes(currentCode)) {

            alreadyRedeemedBonus.value = true;
            return;
        }
        
        // Se chegou aqui, o bônus está disponível para o guest

        alreadyRedeemedBonus.value = false;
    } catch (error) {
        console.error('[DEBUG-BONUS] Erro ao verificar bônus resgatados:', error);
        alreadyRedeemedBonus.value = false; // Define um estado seguro em caso de erro
    }
}

// Função para obter o bônus de influencer pelo código
function getInfluencerBonusByCode(code) {

    if (!code || !influencerBonuses.value || influencerBonuses.value.length === 0) {
        return null;
    }

    // Busca robusta: case-insensitive e ignorando espaços
    const trimmedCode = code.trim().toLowerCase();
    const bonus = influencerBonuses.value.find(b => 
        b.code.trim().toLowerCase() === trimmedCode && 
        b.is_active === true
    );
    

    return bonus || null;
}

// Calcula o valor do bônus de influencer considerando o limite máximo
const calculatedInfluencerBonus = computed(() => {



    
    if (!form.amount || !selectedInfluencerBonus.value) {

        return 0;
    }

    const amount = parseFloat(form.amount);
    const bonusPercentage = parseFloat(selectedInfluencerBonus.value.bonus_percentage);
    const maxBonus = parseFloat(selectedInfluencerBonus.value.max_bonus || 0);

    const calcValues = {
        amount,
        bonusPercentage,
        maxBonus
    };

    // Calcula o valor do bônus baseado na porcentagem
    const calculatedBonus = amount * bonusPercentage / 100;


    // Retorna o menor valor entre o bônus calculado e o máximo permitido
    const finalBonus = maxBonus > 0 ? Math.min(calculatedBonus, maxBonus) : calculatedBonus;

    
    return finalBonus;
});

// Verifica se deve mostrar a opção de bônus de influencer
const showInfluencerBonus = computed(() => {

    
    const userCode = getInfluencerCode();

    
    if (!userCode) {

        return false;
    }

    // Verifica se o bônus de influencer foi encontrado e está ativo
    const bonus = getInfluencerBonusByCode(userCode);

    
    if (!bonus) {

        return false;
    }
    
    // Verifica se o usuário já resgatou este bônus (se for de uso único)
    if (alreadyRedeemedBonus.value && bonus.one_time_use) {

        const bonusInfo = {
            codigo: bonus.code,
            nome: bonus.name,
            uso_unico: bonus.one_time_use ? 'SIM' : 'NÃO',
            ja_resgatado: alreadyRedeemedBonus.value ? 'SIM' : 'NÃO',
            usuario_id: 'Verificando usuário'
        };
        return false;
    }
    
    // Verifica se o valor do depósito é maior ou igual ao mínimo necessário
    const currentAmount = parseFloat(form.amount || 0);
    const meetsMinDepositRequirement = bonus.min_deposit === 0 || currentAmount >= bonus.min_deposit;
    
    const depositInfo = {
        currentAmount,
        minRequired: bonus.min_deposit,
        meetsRequirement: meetsMinDepositRequirement
    };

    return meetsMinDepositRequirement;
});

// Atualiza o bônus de influencer selecionado quando o código muda
const setupInfluencerWatch = () => {
    watch(() => getInfluencerCode(), async (newCode) => {

        
        if (newCode) {
            // Verificar se o usuário já resgatou este bônus
            if (props.auth) {
                try {
                    await checkRedeemedBonuses();

                } catch (error) {
                    console.error('[DEBUG-BONUS] Erro ao verificar bônus resgatados após mudança de código:', error);
                }
            }
            
            selectedInfluencerBonus.value = getInfluencerBonusByCode(newCode);

            
            if (selectedInfluencerBonus.value && !alreadyRedeemedBonus.value) {
                // Não marca automaticamente o bônus, deixa o usuário decidir

            } else if (alreadyRedeemedBonus.value && selectedInfluencerBonus.value?.one_time_use) {
                form.accept_bonus = false;

                        const bonusInfo = {
                    codigo: selectedInfluencerBonus.value.code,
                    nome: selectedInfluencerBonus.value.name,
                    uso_unico: selectedInfluencerBonus.value.one_time_use ? 'SIM' : 'NÃO',
                    ja_resgatado: alreadyRedeemedBonus.value ? 'SIM' : 'NÃO',
                    usuario_id: 'Verificando usuário'
                };
            } else {

            }
        } else {
            selectedInfluencerBonus.value = null;
            form.accept_bonus = false;

        }
    }, { immediate: false }); // Removido immediate: true para evitar execução antecipada
};



const props = defineProps({
    auth: Boolean
})

const emit = defineEmits(['close']);

function closeModal() {
    emit('close');
}

const setting = reactive({
    min_deposit: 0,
    max_deposit: 5000,
    initial_bonus: 0,
    influencer_bonus: 0,
    influencer_bonus_active: false,
    second_deposit_bonus: 0,
    second_deposit_bonus_active: false,
    mbway_gateway: null,
    multibanco_gateway: null
})

function getSetting() {
    const settingStore = useSettingStore();
    const settingData = settingStore.setting;
    if (settingData) {
        Object.assign(setting, settingData);
    }
}

// Garantir que setting seja carregado ao abrir o modal
watch(() => props.show, (newValue) => {
    if (newValue) {
        getSetting();
    }
});

// Chamar getSetting imediatamente
getSetting();

function loadDepositMethods() {
    //console.log(`Buscando rota ${route("api.wallet.deposit.methods")}`);
    try {
        HttpApi.get('wallet/deposit/methods').then((response) => {
            loadingDepositMethods.value = false;
            depositMethods.value = response.data;
        }).catch((error) => {
            console.log('Erro ao carregar metodos de pagamento');
        });
    } catch (error) {
        console.log('Erro ao carregar metodos de pagamento');
    }

}

// Função para verificar se o usuário já fez algum depósito
async function checkUserDeposits() {
    if (!props.auth) return;
    
    try {
        const response = await HttpApi.get('wallet/deposit/has-deposits');
        hasDeposits.value = response.data.has_deposits;
        depositCount.value = response.data.deposit_count || 0;
    } catch (error) {
        console.error('Erro ao verificar depósitos do usuário:', error);
    }
}

// Função para obter o código de influencer para bônus
function getInfluencerCode() {
    // Verifica na URL apenas o parâmetro 'bonus'
    const urlParams = new URLSearchParams(window.location.search);
    const codeFromUrlBonus = urlParams.get('bonus');
    
    // Verifica se há um código de bônus na URL
    if (codeFromUrlBonus) {
        localStorage.setItem('bonus_influencer_code', codeFromUrlBonus);
        return codeFromUrlBonus;
    }
    
    // Se não encontrou na URL, verifica no localStorage
    const codeFromStorage = localStorage.getItem('bonus_influencer_code');
    
    // Verifica se há um código no formulário
    const codeFromForm = form.influencer_code;
    
    // Prioriza o código do formulário sobre o do localStorage
    return codeFromForm || codeFromStorage || null;
}

//captura a gateway para cada método de pagamento
async function checkGateway() {
    if (!props.auth) return;
    
    // Inicialmente usamos o gateway padrão como fallback
    if(!setting.default_gateway){
        return;
    }
    
    // O endpoint será determinado no momento do depósito com base no método de pagamento selecionado
    // e seu gateway configurado
    let defaultGate = setting.default_gateway;
    if(defaultGate == 'eupago'){
        gatewayDefaultUrl.value = 'wallet/deposit/store';
    }else{
        gatewayDefaultUrl.value = 'deposit';
    }
}

// Função para pré-carregar imagens de pagamento
function preloadPaymentImages() {
    const paymentImages = [
        '/assets/images/payments/mbway.svg',
        '/assets/images/payments/multibanco.svg'
    ];
    
    paymentImages.forEach(src => {
        const img = new Image();
        img.src = src;
    });
}

// Função para buscar os nomes dos jogos
async function fetchGameNames() {
    try {
        // Buscar jogos da API de pesquisa que retorna todos os jogos
        const { data } = await HttpApi.get('/api/search/games');
        if (data && data.games && data.games.data) {
            // Criar um mapa de código -> nome do jogo
            data.games.data.forEach(game => {
                if (game.game_code && game.game_name) {
                    gameNames.value[game.game_code] = game.game_name;
                }
            });
        }
        
        // Se não encontrou jogos, tentar outras rotas
        if (Object.keys(gameNames.value).length === 0) {
            try {
                const { data: featuredData } = await HttpApi.get('/api/games/featured');
                if (featuredData && featuredData.games) {
                    featuredData.games.forEach(game => {
                        if (game.game_code && game.game_name) {
                            gameNames.value[game.game_code] = game.game_name;
                        }
                    });
                }
            } catch (e) {
                console.error('Erro ao buscar jogos em destaque:', e);
            }
        }
        
        // Tentar buscar jogos individuais se necessário
        const settings = settingStore.settings;
        const gameCodeToFetch = settings?.game_code_rounds_free_any_deposit;
        
        if (gameCodeToFetch && !gameNames.value[gameCodeToFetch]) {
            try {
                const { data: gameData } = await HttpApi.get(`/api/games/${gameCodeToFetch}`);
                if (gameData && gameData.game && gameData.game.game_name) {
                    gameNames.value[gameCodeToFetch] = gameData.game.game_name;
                }
            } catch (e) {
                // Erro tratado silenciosamente
            }
        }
    } catch (error) {
        // Erro tratado silenciosamente
    }
}

const form = useForm({
    meta: {
        phone: '',
        deposit_bonus: false
    },
    voucher: "",
    amount: "",
    accept_bonus: false,
    deposit_method_slug: "mbway",
    phone: "",
    influencer_code: '', // Inicializa vazio, será preenchido após carregar os bônus
});

// A função loadInfluencerCode foi removida e sua lógica foi movida para o onMounted

onMounted(async () => {
    if (props.auth) {
        loadDepositMethods();
        await checkUserDeposits(); // Verifica se o usuário já fez algum depósito
        await fetchInfluencerBonuses(); // Carrega os bônus de influencer
        
        // Configura o watch após carregar os bônus
        setupInfluencerWatch();
        
        // Carrega o código do influencer após configurar o watch
        const code = getInfluencerCode();
        if (code) {
            form.influencer_code = code;
            selectedInfluencerBonus.value = getInfluencerBonusByCode(code);
            if (selectedInfluencerBonus.value) {
                // Não marca automaticamente o bônus, deixa o usuário decidir
            }
        }
    }
    getSetting();
    checkGateway();
    fetchGameNames();
});

const toast = useToast();

const copyToClipboard = (text) => {
    navigator.clipboard.writeText(text).then(() => {
        toast.success('Texto copiado!', {
            timeout: 2000,
            position: "top-right"
        });
    }).catch(err => {
        console.error('Erro ao copiar: ', err);
        toast.error('Erro ao copiar texto', {
            timeout: 2000,
            position: "top-right"
        });
    });
};

// Função para calcular a data de expiração da referência Multibanco (30 dias a partir de agora)
function getExpirationDate() {
    const now = new Date();
    const expiration = new Date(now.getTime() + 30 * 24 * 60 * 60 * 1000); // 30 dias
    const day = String(expiration.getDate()).padStart(2, '0');
    const month = String(expiration.getMonth() + 1).padStart(2, '0');
    const year = expiration.getFullYear();
    const hours = String(expiration.getHours()).padStart(2, '0');
    const minutes = String(expiration.getMinutes()).padStart(2, '0');
    return `${day}/${month}/${year}, ${hours}:${minutes}`;
}

function selectDeposit(slug) {
    form.deposit_method_slug = slug;
}

function isActive(slug) {
    return form.deposit_method_slug === slug;
}

// Função para marcar um bônus como resgatado no navegador
function markBonusAsRedeemed(code) {
    if (!code) return;
    
    try {
        // Busca o bônus atual para verificar se ele é persistente no navegador
        let currentBonus = null;
        if (Array.isArray(influencerBonuses.value) && influencerBonuses.value.length > 0) {
            currentBonus = influencerBonuses.value.find(bonus => bonus.code === code);
        }
        
        // Se o bônus for persistente no navegador, marca como resgatado globalmente e retorna imediatamente
        // Exatamente como no AuthPopup.vue
        if (currentBonus && currentBonus.browser_persistent) {
            markBonusAsRedeemedInBrowser(code);
            return;
        }
        
        // Se não for persistente no navegador, marca como resgatado apenas para o usuário atual
        let userId = 'guest';
        try {
            const page = usePage();
            if (props.auth && page && page.props && page.props.auth && page.props.auth.user) {
                userId = 'user_' + page.props.auth.user.id;
            }
        } catch (e) { /* ignore */ }
        
        const userRedeemedBonuses = JSON.parse(localStorage.getItem(`redeemed_bonuses_${userId}`)) || [];
        if (!userRedeemedBonuses.includes(code)) {
            userRedeemedBonuses.push(code);
            localStorage.setItem(`redeemed_bonuses_${userId}`, JSON.stringify(userRedeemedBonuses));
        }
    } catch (error) {
        console.error('[DEBUG-BONUS] Erro ao marcar bônus como resgatado:', error);
    }
}

// Função para marcar um bônus como resgatado globalmente no navegador
// Separada como no AuthPopup.vue para maior clareza
function markBonusAsRedeemedInBrowser(code) {
    if (!code) return;
    try {
        const browserRedeemedBonuses = JSON.parse(localStorage.getItem('browser_redeemed_influencer_bonuses')) || [];
        if (!browserRedeemedBonuses.includes(code)) {
            browserRedeemedBonuses.push(code);
            localStorage.setItem('browser_redeemed_influencer_bonuses', JSON.stringify(browserRedeemedBonuses));
        }
    } catch (error) {
        console.error('[DEBUG-BONUS] Erro ao marcar bônus como resgatado no navegador:', error);
    }
}

// Funções para lidar com o checkout embebido do Mollie
function handleMolliePaymentSuccess(data) {
    
    // Marcar bônus como resgatado se aplicável
    const influencerCode = getInfluencerCode();
    if (influencerCode) {
        const currentBonus = getInfluencerBonusByCode(influencerCode);
        if (currentBonus && (currentBonus.one_time_use || currentBonus.browser_persistent)) {
            markBonusAsRedeemed(influencerCode);
        }
    }
    
    // Mostrar mensagem de sucesso
    toast.success('Pagamento processado com sucesso!', {
        timeout: 3000,
        position: "top-right"
    });
    
    // Fechar modal
    emit('close');
}

function handleMolliePaymentInitiated(data) {
    
    // Se requer 3DS, mostrar mensagem informativa
    toast.info('Redirecionando para autenticação 3D Secure...', {
        timeout: 3000,
        position: "top-right"
    });
}

function handleMolliePaymentError(errorMessage) {
    
    // Mostrar erro
    toast.error(errorMessage || 'Erro no processamento do pagamento', {
        timeout: 5000,
        position: "top-right"
    });
    
    // Voltar para seleção de método
    showMollieEmbedded.value = false;
}

function backToPaymentMethods() {
    showMollieEmbedded.value = false;
}

// Função para abrir URL de pagamento
const openPaymentUrl = (url) => {
    if (url && window && window.open) {
        window.open(url, '_blank');
    } else {
        console.error('Erro ao abrir URL de pagamento:', url);
    }
}

const submitDeposit = async () => {
    if (isSubmitting.value) return;

    // Verificar se o usuário está bloqueado para depósitos
    await checkDepositBlock();
    
    if (isDepositBlocked.value) {
        toast.error('Depósitos indisponíveis', {
            timeout: 5000
        });
        return;
    }

    // Reset errors
    errors.value = {
        amount: '',
        phone: ''
    };

    // Validate amount
    if (!form.amount || parseFloat(form.amount) <= 0) {
        errors.value.amount = 'Por favor, insira um valor válido';
        return;
    }
    if (parseFloat(form.amount) < parseFloat(setting.min_deposit)) {
        errors.value.amount = `O valor mínimo para depósito é EUR ${setting.min_deposit}`;
        return;
    }
    if (parseFloat(form.amount) > parseFloat(setting.max_deposit)) {
        errors.value.amount = `O valor máximo para depósito é EUR ${setting.max_deposit}`;
        return;
    }

    // Validate phone for MBWay (apenas se não for Mollie MBWay)
    if (shouldShowPhoneField.value) {
        if (!form.phone) {
            errors.value.phone = 'Por favor, insira um número de telemóvel';
            return;
        }
        if (!/^[0-9]{9}$/.test(form.phone)) {
            errors.value.phone = 'O número de telemóvel deve ter 9 dígitos';
            return;
        }
    }

    isSubmitting.value = true;
    stateLoadingCashInEvent();
    
    // Verificar se é um método Mollie
    if (form.deposit_method_slug.startsWith('mollie-')) {
        const mollieMethod = paymentTypes.value.payment_types.find(
            type => type.slug === form.deposit_method_slug
        )?.mollie_method;

        const mollieData = {
            amount: form.amount,
            mollie_method: mollieMethod,
            accept_bonus: form.accept_bonus
        };

        await HttpApi.post('mollie/create-payment', mollieData)
            .then(function (response) {
                stateLoadingCashInEvent();
                
                if (response.data.status) {
                    // Para todos os métodos Mollie (exceto mbway, multibanco, applepay e paybybank), mostrar tela de pagamento embebida
                    if (form.deposit_method_slug.startsWith('mollie-') && 
                        form.deposit_method_slug !== 'mollie-mbway' && 
                        form.deposit_method_slug !== 'mollie-multibanco' &&
                        form.deposit_method_slug !== 'mollie-applepay' &&
                        form.deposit_method_slug !== 'mollie-paybybank') {
                        
                        depositGateway.value = true;
                        depositResponse.value = {
                            ...response.data,
                            payment_method: form.deposit_method_slug
                        };
                        isSubmitting.value = false;
                        return;
                    }
                    
                    // Para mbway, multibanco, applepay e paybybank Mollie, verificar se tem checkout_url
                    if (response.data.checkout_url) {
                        // Para mbway, multibanco, applepay e paybybank Mollie, redirecionar diretamente
                        window.location.href = response.data.checkout_url;
                        
                        // Se o depósito foi bem-sucedido e tinha um código de influencer, marca como resgatado no navegador
                        const influencerCode = getInfluencerCode();
                        if (influencerCode) {
                            const currentBonus = getInfluencerBonusByCode(influencerCode);
                            
                            // Sempre marca como resgatado se for de uso único ou persistente no navegador
                            if (currentBonus && (currentBonus.one_time_use || currentBonus.browser_persistent)) {
                                markBonusAsRedeemed(influencerCode);
                            }
                        }
                        
                        // Fechar modal após abrir a página de pagamento
                        emit('close');
                    }
                }
            })
            .catch(error => {
                console.error('Erro no pagamento Mollie:', error);
                if (error.response?.data?.message) {
                    errors.value.amount = error.response.data.message;
                }
            })
            .finally(() => {
                isSubmitting.value = false;
            });
        
        return;
    }
    
    // Lógica original para outros métodos (EuPago/SIBS)
    let endpoint = 'wallet/deposit/store'; // Endpoint padrão para Eupago
    
    // Se o método for mbway, verificamos qual gateway está configurado para mbway
    if (form.deposit_method_slug === 'mbway') {
        if (setting.mbway_gateway === 'sibs') {
            endpoint = 'wallet/deposit/store'; // Endpoint correto para Sibs
        } else if (setting.mbway_gateway === 'mollie') {
            endpoint = 'wallet/deposit/store'; // Endpoint correto para Mollie
        }
    }
    // Se o método for mbank (multibanco), verificamos qual gateway está configurado para multibanco
    else if (form.deposit_method_slug === 'mbank') {
        if (setting.multibanco_gateway === 'sibs') {
            endpoint = 'wallet/deposit/store'; // Endpoint correto para Sibs
        } else if (setting.multibanco_gateway === 'mollie') {
            endpoint = 'wallet/deposit/store'; // Endpoint correto para Mollie
        }
    }
    
    // Adiciona o código de influencer ao formulário, se disponível
    const influencerCode = getInfluencerCode();
    if (influencerCode) {
        form.influencer_code = influencerCode;
        form.meta.influencer_code = influencerCode;
    }
    
    // Adiciona deposit_bonus ao meta quando accept_bonus está marcado
    form.meta.deposit_bonus = form.accept_bonus;
    
    
    await HttpApi.post(endpoint, form)
        .then(function (response) {
            stateLoadingCashInEvent();
            
            // Se o depósito foi bem-sucedido e tinha um código de influencer, marca como resgatado no navegador
            const influencerCode = getInfluencerCode();
            if (response.data && response.data.success && influencerCode) {
                const currentBonus = getInfluencerBonusByCode(influencerCode);
                
                // Sempre marca como resgatado se for de uso único ou persistente no navegador
                if (currentBonus && (currentBonus.one_time_use || currentBonus.browser_persistent)) {
                    markBonusAsRedeemed(influencerCode);
                } else {
                }
            }
            // Para MBWay - tela específica sem referencia/entidade
            if (response.data.mbway_success || (form.deposit_method_slug === 'mbway' && response.data.transactionStatus === 'Success')) {
                depositGateway.value = true;
                depositResponse.value = null; // MBWay não precisa de referencia/entidade
                console.log('MBWay success - showing waiting screen');
            }
            // Para Multibanco - tela com entidade e referencia
            else if (response.data.entidade && response.data.referencia) {
                depositGateway.value = true;
                depositResponse.value = {
                    entidade: response.data.entidade,
                    referencia: response.data.referencia
                };
                console.log('Multibanco depositResponse:', depositResponse.value);
            }
            // Para outros métodos Mollie com redirecionamento
            else if (response.data.checkout_url) {
                window.location.href = response.data.checkout_url;
                return;
            }
        })
        .catch(error => {
            if (error.response?.data?.errors) {
                const apiErrors = error.response.data.errors;
                if (apiErrors.amount) errors.value.amount = apiErrors.amount[0];
                if (apiErrors['meta.phone']) errors.value.phone = apiErrors['meta.phone'][0];
            }
        })
        .finally(() => {
            isSubmitting.value = false;
        });
};

const fields = ref([])

async function loadDepositConfig() {
    try {
        const res = await HttpApi.get('wallet/deposit/options')
        paymentTypes.value = res.data
        loadingPaymentApi.value = false
        setFields('mbway')
    } catch (error) {
        console.error('Error loading deposit config:', error)
        loadingPaymentApi.value = false
    }
}

function setFields(type) {
    if (paymentTypes.value?.payment_types && paymentTypes.value.payment_types[type]) {
        fields.value = paymentTypes.value.payment_types[type].fields || []
    } else {
        fields.value = []
    }
}

if (props.auth) {
    loadDepositConfig()
}

// Sempre recarregar métodos quando o modal abrir
watch(() => props.show, (newValue) => {
    if (newValue && props.auth) {
        paymentTypes.value = null // Limpar dados anteriores
        loadingPaymentApi.value = true
        loadDepositConfig()
    }
})


const loadingCashInEvent = ref(false)

function stateLoadingCashInEvent() {
    loadingCashInEvent.value = !loadingCashInEvent.value
}

function restartPaymentModal() {
    form.reset()
    depositGateway.value = false;
}
</script>

<style scoped>
/* Estilo para as caixas de seleção de bônus */
:deep(.bonus-section input[type="checkbox"]) {
    border-color: #3b82f6 !important; /* Borda azul (Tailwind blue-500) */
    border-width: 1px;
}
</style>

<template>
    <div class="rounded light">
        <div class="p-2">

            <div v-show="loadingCashInEvent" class="flex items-center space-x-2">
    <div class="w-2.5 h-2.5 bg-blue-300 rounded-full animate-bounce"></div>
    <div class="w-2.5 h-2.5 bg-blue-400 rounded-full animate-bounce animation-delay-100"></div>
    <div class="w-2.5 h-2.5 bg-blue-500 rounded-full animate-bounce animation-delay-200"></div>
</div>


            <div v-show="!depositGateway">
                <div v-show="!loadingDepositMethods">
                    <form @submit.prevent="submitDeposit">

                        <div class="w-full gap-2 grid grid-cols-4 justify-center items-center">
                            <div v-show="false" class="bg-white  cursor-pointer justify-center text-center"
                                v-for="(method, index) in depositMethods" :key="index"
                                @click="selectDeposit(method.slug)" :class="[
                                    isActive(method.slug)
                                        ? 'bg-blue-300 text-blue-800 font-bold'
                                        : 'bg-white text-blue-800',
                                ]">
                                <div class="w-full  rounded-t">
                                    <img class="object-contain w-full h-16 mx-auto" :src="method.icon"
                                        alt="Deposit Method" />
                                </div>
                                <div class="text-sm w-full py-2 font-oswald uppercase text-center">

                                    {{ method.name }}
                                </div>
                            </div>
                        </div>
                        <div>
                            <InputLabel class="text-sm mb-2 text-white" value="Meio de pagamento" />
                        </div>
                        <div class="grid grid-cols-3 gap-1 mb-1">
                            <button class="border-2 items-center px-0 py-0 text-sm rounded transition-all duration-200 hover:scale-105" type="button"
    v-for="(types, index) in filteredPaymentTypes" :key="index"
    :class="{
        'border-blue-500 bg-white shadow-lg shadow-blue-500/30 ring-2 ring-blue-600/50': form.deposit_method_slug === types.slug, 
        'bg-white border-gray-300 hover:border-gray-400 hover:shadow-md': form.deposit_method_slug !== types.slug
    }"
    @click="selectDeposit(types.slug)"
    :style="form.deposit_method_slug === types.slug ? 'transform: scale(1.05);' : ''">
    <img class="mx-auto w-full h-16 mb-0 object-contain p-1" :src="types.icon" alt="">
</button>

                        </div>
                        <div v-show="form.deposit_method_slug" class="text-sm">
                            <div>
                                <div v-show="form.bounty"></div>
                                <div>
                                    <InputLabel class="block mb-2 text-sm font-medium text-white" value="Valor a ser depositado" />
                                    <TextInput 
                                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" 
                                        :placeholder="$t('0,00')" 
                                        v-model="form.amount"
                                        :min="setting.min_deposit" 
                                        :max="setting.max_deposit" 
                                        step="0.01"
                                        type="number"
                                        inputmode="decimal" />
                                    <div v-if="errors.amount" class="mt-2 text-sm text-red-600 dark:text-red-500">
                                        {{ errors.amount }}
                                    </div>
                                    <!-- Mensagem de giros grátis em uma única linha com efeito elegante e fundo mais transparente -->
                                    <div v-if="freespinsInfo && form.amount && !isNaN(parseFloat(form.amount)) && !errors.amount" class="mt-2 py-1.5 px-3 rounded bg-gradient-to-r from-blue-500/30 to-blue-600/20 backdrop-blur-sm border border-blue-400/30 shadow-sm">
                                        <div class="flex items-center">
                                            <div class="bg-white/90 p-1 rounded-full mr-2 flex-shrink-0 shadow-sm">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                            </div>
                                            <span class="text-sm">
                                                <span class="font-medium text-white drop-shadow-sm"><span class="text-blue-100 font-bold mr-1">+</span>{{ freespinsInfo.rounds }} Giros Grátis</span>
                                                <span v-if="freespinsInfo.gameName" class="text-blue-50/90"> no jogo <span class="font-medium text-white drop-shadow-sm">{{ freespinsInfo.gameName }}</span></span>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <div class="grid grid-cols-4 py-1 gap-1 mt-2 mb-2">
                                    <div v-for="(tab, index) in paymentTypes.tabs" :key="index" class="relative group">
                                        <!-- Balão informativo de giros grátis - posicionado no canto superior direito -->
                                        <div v-if="calculateFreespinsForAmount(tab) && settingStore.getSettingData()?.show_freespin_badges" 
                                             class="absolute -top-3 -right-2 bg-yellow-400 text-white text-[9px] font-bold py-0.5 px-1.5 rounded-full z-10 whitespace-nowrap">
                                            +{{ calculateFreespinsForAmount(tab) }}GG
                                        </div>
                                        
                                        <button type="button"
                                            class="border border-blue-500/40 hover:bg-blue-600 hover:text-primary-950 focus:bg-blue-600 rounded-md py-1 font-bold text-sm w-full relative z-0 transition-all duration-150"
                                            :class="{'ring-1 ring-blue-500': calculateFreespinsForAmount(tab)}"
                                            @click="form.amount = '' + tab">{{ paymentTypes.currency }} {{ tab }}
                                        </button>
                                    </div>
                                </div>
                                <!-- Legenda para GG -->
                                <p v-if="settingStore.getSettingData()?.show_freespin_badges" class="mt-1 text-xs text-gray-500 dark:text-gray-400">GG = Giros Grátis</p>
                                <!-- Campo telefone apenas para MBWay não-Mollie -->
                                <div v-if="shouldShowPhoneField">
                                    <InputLabel class="block mb-2 text-sm font-medium text-white" value="Telemóvel" />

                                    <div class="relative">
                                        <div class="absolute inset-y-0 start-0 flex items-center ps-3.5 pointer-events-none">
                                            <span class="text-gray-500 dark:text-gray-400">+351</span>
                                        </div>
                                        <TextInput
                                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full ps-16 p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                                            placeholder="Telemóvel"
                                            v-model="form.phone"
                                            maxlength="9" 
                                            pattern="\d*" 
                                        />
                                    </div>
                                    <div v-if="errors.phone" class="mt-2 text-sm text-red-600 dark:text-red-500">
                                        {{ errors.phone }}
                                    </div>
                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Apenas números portugueses (+351)</p>
                                </div>
                                
                                <!-- Aviso para MBWay via Mollie -->
                                <div v-if="isMbwayMollie" class="mb-4 p-3 bg-blue-800/30 border border-blue-600 rounded">
                                    <div class="flex items-center">
                                        <svg class="w-5 h-5 text-blue-400 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                                        </svg>
                                        <span class="text-blue-200 text-sm">
                                            Irá inserir o seu número de telemóvel no próximo passo
                                        </span>
                                    </div>
                                </div>

                                <div class="mt-2" v-show="showInputVoucher">
                                    <InputLabel class="mb-2 text-white" value="Voucher" />
                                    <TextInput class="text-sm" placeholder="Voucher" v-model="form.voucher" />
                                </div>
                                <!-- Mostrar opção de bônus de influencer (prioridade) -->
                                <div v-if="getInfluencerCode() && selectedInfluencerBonus && !(alreadyRedeemedBonus && selectedInfluencerBonus.one_time_use)" class="mt-3 bonus-section">
                                    <!-- Mostrar mensagem quando o valor do depósito for menor que o mínimo necessário -->
                                    <div v-if="selectedInfluencerBonus.min_deposit > 0 && parseFloat(form.amount || 0) < selectedInfluencerBonus.min_deposit" class="text-sm text-amber-500 mb-2">
                                        <i class="fas fa-info-circle mr-1"></i>
                                        Depósito mínimo de EUR {{ selectedInfluencerBonus.min_deposit }} necessário para receber o bônus de influencer.
                                    </div>
                                    
                                    <!-- Opção de bônus quando o valor for suficiente -->
                                    <div v-if="showInfluencerBonus" class="flex flex-col">
                                        <div class="flex items-center gap-2">
                                            <Checkbox 
                                                v-model="form.accept_bonus"
                                                :checked="form.accept_bonus"
                                                class="border border-blue-500 rounded"
                                            />
                                            <span class="text-sm text-white">{{ selectedInfluencerBonus.name || 'Bônus de influencer' }}</span>
                                        </div>
                                        <div v-if="form.accept_bonus" class="ml-6 mt-1">
                                            <span class="text-yellow-500">
                                                + EUR {{ calculatedInfluencerBonus.toFixed(2) }} Em bônus
                                                <span v-if="selectedInfluencerBonus.max_bonus > 0 && (form.amount * selectedInfluencerBonus.bonus_percentage / 100) > selectedInfluencerBonus.max_bonus" class="text-xs">
                                                    (limitado a EUR {{ selectedInfluencerBonus.max_bonus }})
                                                </span>
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Mostrar opção de bônus do primeiro depósito (se não tiver bônus de influencer) -->
                                <div v-else-if="setting.initial_bonus > 0 && !hasDeposits" class="flex items-center gap-2 mt-3 bonus-section">
                                    <Checkbox 
                                        v-model="form.accept_bonus"
                                        :checked="form.accept_bonus"
                                    />
                                    <span class="text-sm text-white">Bônus de primeiro depósito</span>
                                    <span class="text-yellow-500" v-if="form.accept_bonus">
                                        + EUR {{ form.amount * setting.initial_bonus / 100 }} Em bônus
                                    </span>
                                </div>

                                <!-- Mostrar opção de bônus do segundo depósito (se não tiver bônus de influencer) -->
                                <div v-else-if="setting.second_deposit_bonus > 0 && setting.second_deposit_bonus_active && depositCount === 1" class="flex items-center gap-2 mt-3 bonus-section">
                                    <Checkbox 
                                        v-model="form.accept_bonus"
                                        :checked="form.accept_bonus"
                                    />
                                    <span class="text-sm text-white">Bônus de segundo depósito</span>
                                    <span class="text-yellow-500" v-if="form.accept_bonus">
                                        + EUR {{ form.amount * setting.second_deposit_bonus / 100 }} Em bônus
                                    </span>
                                </div>

                                <div class="flex mt-4 justify-between items-center">
                                    <PrimaryButton 
                                        class="w-full ui-button-blue"
                                        :disabled="isSubmitting"
                                    >
                                        {{ isSubmitting ? $t("Processando...") : $t("GERAR DEPÓSITO") + ' ' + form.amount + ' €' }}
                                    </PrimaryButton>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>


            </div>
            <div v-show="depositGateway" class=" text-sm">
                <div class="font-bold text-white text-lg">
                    AGUARDANDO PAGAMENTO
                </div>
                <div v-if="form.deposit_method_slug === 'mbway'" class="text-white">
                    O pagamento via MB WAY foi enviado com sucesso para o seu telemóvel!
                </div>
                <div v-if="form.deposit_method_slug === 'mbank'">
                    <div class="mt-3" v-if="depositResponse && depositResponse.entidade">
                        <!-- Título -->
                        <h3 class="text-white text-xl font-bold text-center mb-6">Referência Multibanco</h3>
                        
                        <!-- Box do Montante -->
                        <div class="border-2 border-blue-500 rounded-lg p-4 mb-4">
                            <div class="text-gray-400 text-sm text-center mb-1">Montante</div>
                            <div class="text-blue-500 text-3xl font-bold text-center">{{ parseFloat(form.amount).toFixed(2).replace('.', ',') }} €</div>
                        </div>
                        
                        <!-- Entidade e Referência lado a lado -->
                        <div class="grid grid-cols-2 gap-3 mb-4">
                            <!-- Entidade -->
                            <button @click="copyToClipboard(depositResponse.entidade)" class="border border-gray-600 rounded-lg p-3 hover:border-gray-400 hover:bg-blue-700/20 transition-all cursor-pointer text-left">
                                <div class="text-gray-400 text-xs mb-1">Entidade</div>
                                <div class="flex items-center justify-between">
                                    <span class="text-white text-lg font-bold">{{ depositResponse.entidade }}</span>
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                    </svg>
                                </div>
                            </button>
                            
                            <!-- Referência -->
                            <button @click="copyToClipboard(depositResponse.referencia)" class="border border-gray-600 rounded-lg p-3 hover:border-gray-400 hover:bg-blue-700/20 transition-all cursor-pointer text-left">
                                <div class="text-gray-400 text-xs mb-1">Referência</div>
                                <div class="flex items-center justify-between">
                                    <span class="text-white text-lg font-bold">{{ depositResponse.referencia }}</span>
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                    </svg>
                                </div>
                            </button>
                        </div>
                        
                        <!-- Texto Explicativo -->
                        <p class="text-gray-400 text-sm text-center mb-6">
                            Use esta referência para pagar através do Multibanco ou da sua app bancária. Os fundos serão creditados na sua conta após o pagamento ser processado.
                        </p>
                        
                        <!-- Botão Fechar -->
                        <button @click="closeModal"
                            class="w-full border-blue-500 hover:bg-blue-600 text-white font-bold py-4 rounded-lg transition-colors text-lg">
                            Fechar
                        </button>
                    </div>
                </div>
                
                <!-- Tela de pagamento Mollie com formulário embebido -->
                <div v-if="form.deposit_method_slug.startsWith('mollie-')">
                    
                    <!-- Cartão de Crédito -->
                    <div v-if="form.deposit_method_slug === 'mollie-creditcard'">
                        <div class="text-white mb-4 text-left font-semibold">Complete o pagamento com cartão de débito ou crédito</div>
                        
                        <div class="mb-6 p-4 bg-gray-800/80 border border-gray-700 rounded-lg shadow-lg">
                            <div class="flex justify-between items-center mb-2">
                                <span class="text-gray-300 text-sm">Valor do depósito:</span>
                                <span class="text-white font-bold text-lg">EUR {{ form.amount }}</span>
                            </div>
                            <div v-if="form.accept_bonus" class="flex justify-between items-center border-t border-gray-700 pt-2">
                                <span class="text-gray-300 text-sm">Bônus:</span>
                                <span class="text-yellow-400 font-bold">+ EUR {{ calculatedInfluencerBonus?.toFixed(2) || (form.amount * setting.initial_bonus / 100).toFixed(2) }}</span>
                            </div>
                            <div v-if="form.accept_bonus" class="flex justify-between items-center border-t border-gray-700 pt-2 mt-2">
                                <span class="text-gray-300 text-sm font-semibold">Total:</span>
                                <span class="text-blue-400 font-bold text-lg">EUR {{ (parseFloat(form.amount) + (calculatedInfluencerBonus || (form.accept_bonus ? form.amount * setting.initial_bonus / 100 : 0))).toFixed(2) }}</span>
                            </div>
                        </div>

                        <div class="w-full">
                            <MollieCardForm
                                :amount="form.amount"
                                :profile-id="mollieProfileId"
                                @payment-success="handleMolliePaymentSuccess"
                                @payment-initiated="handleMolliePaymentInitiated"
                                @payment-error="handleMolliePaymentError"
                                @error="handleMolliePaymentError"
                            />
                        </div>
                        
                        <div class="mt-4 text-center">
                            <p class="text-gray-400 text-xs">
                                <i class="fas fa-lock mr-1"></i>
                                Pagamento seguro processado pelo Mollie
                            </p>
                        </div>
                    </div>
                    
                    
                    
                    <!-- MB WAY -->
                    <div v-if="form.deposit_method_slug === 'mollie-mbway'">
                        <MollieMBWayForm
                            :amount="form.amount"
                            :influencer-bonus="calculatedInfluencerBonus || (form.accept_bonus ? form.amount * setting.initial_bonus / 100 : 0)"
                            :accept-bonus="form.accept_bonus"
                            @payment-success="handleMolliePaymentSuccess"
                            @payment-initiated="handleMolliePaymentInitiated"
                            @payment-error="handleMolliePaymentError"
                            @back="backToPaymentMethods"
                        />
                    </div>
                    
                    <!-- Multibanco -->
                    <div v-if="form.deposit_method_slug === 'mollie-multibanco'">
                        <MollieMultibancoForm
                            :amount="form.amount"
                            :influencer-bonus="calculatedInfluencerBonus || (form.accept_bonus ? form.amount * setting.initial_bonus / 100 : 0)"
                            :accept-bonus="form.accept_bonus"
                            @payment-success="handleMolliePaymentSuccess"
                            @payment-initiated="handleMolliePaymentInitiated"
                            @payment-error="handleMolliePaymentError"
                            @back="backToPaymentMethods"
                        />
                    </div>
                    
                    <!-- Pay by Bank -->
                    <div v-if="form.deposit_method_slug === 'mollie-paybybank'">
                        <div class="text-white mb-4">Complete o pagamento via transferência bancária</div>
                        
                        <div class="mb-4 p-3 bg-gray-800 rounded">
                            <div class="text-white text-sm mb-2">
                                <strong>Valor:</strong> EUR {{ form.amount }}
                            </div>
                            <div v-if="form.accept_bonus" class="text-yellow-500 text-sm">
                                <strong>Bônus:</strong> + EUR {{ calculatedInfluencerBonus?.toFixed(2) || (form.amount * setting.initial_bonus / 100).toFixed(2) }}
                            </div>
                        </div>
                        
                        <div class="text-center">
                            <button 
                                @click="openPaymentUrl(depositResponse.checkout_url)"
                                class="w-full ui-button-blue font-bold shadow text-white px-6 py-4 rounded text-lg mb-4">
                                <i class="fas fa-university mr-2"></i>
                                Pagar via Banco
                            </button>
                            <p class="text-gray-400 text-sm">Clique no botão acima para abrir o pagamento bancário</p>
                        </div>
                    </div>
                    
                    <!-- Bank Transfer -->
                    <div v-if="form.deposit_method_slug === 'mollie-banktransfer'">
                        <div class="text-white mb-4">Complete o pagamento via transferência bancária</div>
                        
                        <div class="mb-4 p-3 bg-gray-800 rounded">
                            <div class="text-white text-sm mb-2">
                                <strong>Valor:</strong> EUR {{ form.amount }}
                            </div>
                            <div v-if="form.accept_bonus" class="text-yellow-500 text-sm">
                                <strong>Bônus:</strong> + EUR {{ calculatedInfluencerBonus?.toFixed(2) || (form.amount * setting.initial_bonus / 100).toFixed(2) }}
                            </div>
                        </div>
                        
                        <div class="text-center">
                            <button 
                                @click="openPaymentUrl(depositResponse.checkout_url)"
                                class="w-full ui-button-blue font-bold shadow text-white px-6 py-4 rounded text-lg mb-4">
                                <i class="fas fa-exchange-alt mr-2"></i>
                                Transferência Bancária
                            </button>
                            <p class="text-gray-400 text-sm">Clique no botão acima para abrir a transferência bancária</p>
                        </div>
                    </div>
                </div>
                
                <div class="mt-4" v-if="form.deposit_method_slug !== 'mbank'">
                    <button @click="restartPaymentModal"
                        class="w-full ui-button-blue font-bold shadow text-white px-3 text-xs uppercase py-2 rounded mb-3"
                        type="button">Realizar novo depósito</button>
                </div>
            </div>

        </div>
    </div>
    <Dialog text="Confirme os termos do bonus" title="TERMOS DE BÔNUS">
        <ul class="text-xs space-y-1">
            <li>
                Deposite e receba 100% de bônus em dinheiro + 40 rodadas grátis
            </li>
            <li>
                O depósito mínimo exigido é de R$ 50 para receber as 40 Rodadas
                Grátis.
            </li>
            <li>
                As Rodadas grátis são creditadas somente no primeiro depósito.
            </li>
            <li>
                O bônus em dinheiro é concedido em qualquer depósito até um
                máximo de R$ 1000
            </li>
            <li>A aposta máxima permitida com dinheiro de bônus é de R$ 25</li>
            <li>
                Os Termos de Bônus e os Termos de Bônus de Boas-vindas se
                aplicam a esta oferta.
            </li>
        </ul>
    </Dialog>
</template>