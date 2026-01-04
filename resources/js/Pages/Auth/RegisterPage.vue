<template>
    <AuthLayout>
        <LoadingComponent :isLoading="isLoading">
            <div class="flex items-center justify-center h-full">
                <a v-if="setting" href="/" class="logo-animation">
                    <!-- Adicionando a classe items-center para centralizar verticalmente -->
                    <img
                        :src="`/storage/` + setting.software_logo_black"
                        alt=""
                        class="block h-10 mr-3 dark:hidden"
                    />
                    <img
                        :src="`/storage/` + setting.software_logo_white"
                        alt=""
                        class="hidden h-10 mr-3 dark:block"
                    />
                </a>
            </div>
        </LoadingComponent>
        <div v-if="!isLoading" class="my-auto pt-16">
            <div class="px-0 py-0">
                <div
                    class="min-h-[calc(100vh-565px)] text-center flex flex-col items-center justify-center"
                >
                    <div
                        class="w-full md:max-w-md lg:max-w-lg bg-gray-100 md:mt-0 dark:bg-gray-700 mx-auto shadow-lg rounded-lg"
                    >
                        <div class="p-4 space-y-3 md:space-y-4">
                            <h1 class="mb-4 text-2xl text-center font-bold">
                                {{ $t("Formulário de Registo") }}
                            </h1>

                            <!-- Banner de registro -->
                            <div v-if="registerBanner" class="mb-4 rounded-lg overflow-hidden shadow-lg">
                                <div>
                                    <img :src="registerBanner.image.startsWith('http') ? registerBanner.image : '/storage/' + registerBanner.image" :alt="registerBanner.description" class="w-full h-auto">
                                </div>
                            </div>

                            <div class="mt-2">
                                <form
                                    @submit.prevent="registerSubmit"
                                    method="post"
                                    action=""
                                    class=""
                                >
                                    <div class="mb-3">
                                        <div class="relative">
                                            <div
                                                class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none"
                                            >
                                                <i
                                                    class="fa-regular fa-user text-blue-600" style="z-index: 1;"
                                                ></i>
                                            </div>
                                            <input
                                                type="text"
                                                name="name"
                                                v-model="registerForm.name"
                                                class="input-group bg-gray-700 border-gray-600 text-white placeholder-gray-400 focus:ring-blue-500 focus:border-blue-500"
                                                :placeholder="$t('Digite seu nome')"
                                                required
                                            />
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <div class="relative">
                                            <div
                                                class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none"
                                            >
                                                <i
                                                    class="fa-regular fa-envelope text-blue-600" style="z-index: 1;"
                                                ></i>
                                            </div>
                                            <input
                                                type="email"
                                                name="email"
                                                v-model="registerForm.email"
                                                class="input-group bg-gray-700 border-gray-600 text-white placeholder-gray-400 focus:ring-blue-500 focus:border-blue-500"
                                                :placeholder="$t('Coloque o seu e-mail')"
                                                required
                                            />
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <div class="relative">
                                            <div
                                                class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none"
                                            >
                                                <i
                                                    class="fa-solid fa-key text-blue-600" style="z-index: 1;"
                                                ></i>
                                            </div>
                                            <input
                                                :type="typeInputPassword"
                                                name="password"
                                                v-model="registerForm.password"
                                                class="input-group pr-[40px] bg-gray-700 border-gray-600 text-white placeholder-gray-400 focus:ring-blue-500 focus:border-blue-500"
                                                :placeholder="
                                                    $t('Coloque a sua senha')
                                                "
                                                required
                                            />
                                            <button
                                                type="button"
                                                @click.prevent="togglePassword"
                                                class="absolute inset-y-0 right-0 flex items-center pr-3.5"
                                            >
                                                <i
                                                    v-if="
                                                        typeInputPassword ===
                                                        'password'
                                                    "
                                                    class="fa-regular fa-eye"
                                                ></i>
                                                <i
                                                    v-if="
                                                        typeInputPassword === 'text'
                                                    "
                                                    class="fa-sharp fa-regular fa-eye-slash"
                                                ></i>
                                            </button>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <div class="relative">
                                            <div
                                                class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none"
                                            >
                                                <span class="text-blue-600 font-bold" style="z-index: 1;">+351</span>
                                            </div>
                                            <input
                                                type="text"
                                                name="phone"
                                                v-model="registerForm.phone"
                                                @input="validatePhone"
                                                :class="{
                                                    'input-group ps-16 bg-gray-700 border-gray-600 text-white placeholder-gray-400 focus:ring-blue-500 focus:border-blue-500': !phoneError,
                                                    'input-group ps-16 bg-gray-700 border-red-500 text-white placeholder-gray-400 focus:ring-red-500 focus:border-red-500': phoneError
                                                }"
                                                placeholder="Número de telemóvel"
                                                maxlength="9"
                                                pattern="\d*"
                                                required
                                            />
                                        </div>
                                        <div v-if="phoneError" class="mt-1 text-sm text-red-400">
                                            {{ phoneError }}
                                        </div>
                                    </div>
                                    


                                    <div
                                        class="mt-5 mb-3"
                                        v-if="
                                            isReferral &&
                                            !registerForm.reference_code
                                        "
                                    >
                                        <button
                                            @click.prevent="
                                                isReferral = !isReferral
                                            "
                                            type="button"
                                            class="flex justify-between w-full"
                                        >
                                            <p>
                                                {{
                                                    $t(
                                                        "Enter Referral/Promo Code"
                                                    )
                                                }}
                                            </p>
                                            <div class="">
                                                <i
                                                    v-if="isReferral"
                                                    class="fa-solid fa-chevron-up"
                                                ></i>
                                                <i
                                                    v-if="!isReferral"
                                                    class="fa-solid fa-chevron-down"
                                                ></i>
                                            </div>
                                        </button>

                                        <div
                                            v-if="
                                                isReferral &&
                                                !registerForm.reference_code
                                            "
                                            class="relative mt-1 mb-3"
                                        >
                                            <div
                                                class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none"
                                            >
                                                <i
                                                    class="fa-regular fa-user text-blue-600"
                                                ></i>
                                            </div>
                                            <input
                                                type="text"
                                                name="reference_code"
                                                v-model="registerForm.reference_code"
                                                class="input-group bg-gray-700 border-gray-600 text-white placeholder-gray-400 focus:ring-blue-500 focus:border-blue-500"
                                                :placeholder="$t('Digite seu código promocional')"
                                            />
                                        </div>
                                    </div>
 <p class="text-sm text-gray-300 mb-6">
    {{ $t('Já possui conta') }}?
    <RouterLink :to="{ name: 'login' }" active-class="top-register-active" class="">
        <strong>{{ $t('Faça login') }}</strong>
    </RouterLink>
</p>
                                    <hr
                                        class="mt-2 mb-3 dark:border-gray-600"
                                    />

                                    <div class="mb-4 mt-6">
                                        <div class="flex">
                                            <input
                                                id="term-a"
                                                v-model="registerForm.term_a"
                                                name="term_a"
                                                required
                                                type="checkbox"
                                                value=""
                                                class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600"
                                            />
                                            <label
                                                for="term-a"
                                                class="ml-2 text-sm font-medium text-left text-gray-300"
                                                >{{$t("I agree to the User Agreement & confirm I am at least 18 years old")}}</label>
                                        </div>
                                    </div>

                                    <div class="w-full mt-5">
                                        <button
                                            type="submit"
                                            class="w-full py-3 mb-3 rounded ui-button-blue text-lg font-bold"
                                        >
                                            {{ $t("Registrar") }}
                                        </button>
                                    </div>
                                </form>

                                <!-- <div class="mt-5 login-wrap">
                                    <div class="line-text">
                                        <div class="l"></div>
                                        <div class="t">{{ $t('Register with your social networks') }}</div>
                                        <div class="l"></div>
                                    </div>

                                    <div class="mt-3 social-group">
                                        <a :href="redirectSocialTo()" class="text-social-button hover:text-white focus:ring-4 focus:outline-none font-medium text-sm px-5 py-2.5 text-center mr-2 mb-2 dark:hover:text-white ">
                                            <i class="fa-brands fa-google"></i>
                                        </a>
                                    </div>
                                </div> -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthLayout>
</template>

<script>
import { useToast } from "vue-toastification";
import { useAuthStore } from "@/Stores/Auth.js";
import { usePopupStore } from "@/Stores/PopupStore.js";
import HttpApi from "@/Services/HttpApi.js";
import AuthLayout from "@/Layouts/AuthLayout.vue";
import { useRoute, useRouter } from "vue-router";
import { onMounted, reactive } from "vue";
import { useSpinStoreData } from "@/Stores/SpinStoreData.js";
import LoadingComponent from "@/Components/UI/LoadingComponent.vue";
import { useSettingStore } from "@/Stores/SettingStore";

export default {
    props: [],
    components: { LoadingComponent, AuthLayout },
    data() {
        return {
            isLoading: false,
            typeInputPassword: "password",
            isReferral: false,
            setting: null,
            registerBanner: null,

            registerForm: {
                name: "",
                email: "",
                password: "",
                cpf: "",
                phone: "",
                password_confirmation: "",
                reference_code: "",
                term_a: true,
                agreement: false,
                spin_data: null,
            },
            phoneError: "",
        };
    },
    setup() {
        const router = useRouter();
        const popupStore = usePopupStore();
        const routeParams = reactive({
            code: null,
        });

        onMounted(() => {
            const params = new URLSearchParams(window.location.search);
            if (params.has("code")) {
                routeParams.code = params.get("code");
                localStorage.setItem('referral_code', routeParams.code);
            }
        });

        return {
            routeParams,
            router,
        };
    },
    computed: {
        isAuthenticated() {
            const authStore = useAuthStore();
            return authStore.isAuth;
        },
    },
    beforeUnmount() {},
    mounted() {
        const router = useRouter();
        if (this.isAuthenticated) {
            router.push({ name: "home" });
        }
        
        // Buscar o banner de registro quando o componente for montado
        this.fetchRegisterBanner();
        
        const savedCode = localStorage.getItem('referral_code');

        if (this.router.currentRoute.value.params.code) {
            try {
                const str = atob(this.router.currentRoute.value.params.code);
                const obj = JSON.parse(str);

                this.registerForm.spin_token =
                    this.router.currentRoute.value.params.code;
            } catch (e) {
                this.registerForm.reference_code = this.routeParams.code || savedCode;
                this.isReferral = true;
            }
        } else if (this.routeParams.code || savedCode) {
            this.registerForm.reference_code = this.routeParams.code || savedCode;
            this.isReferral = true;
        }
    },
    methods: {
        fetchRegisterBanner: function() {
            HttpApi.get('banners/register')
                .then(response => {
                    if (response.data.success) {
                        this.registerBanner = response.data.banner;
                    }
                })
                .catch(error => {
                    console.error('Erro ao buscar banner de registro:', error);
                });
        },
        redirectSocialTo: function () {
            return "/auth/redirect/google";
        },
        togglePassword: function () {
            if (this.typeInputPassword === "password") {
                this.typeInputPassword = "text";
            } else {
                this.typeInputPassword = "password";
            }
        },
        getSetting: function () {
            const _this = this;
            const settingStore = useSettingStore();
            const settingData = settingStore.setting;
            if (settingData) {
                _this.setting = settingData;
            }
        },
        insertScript: function () {
            var script = document.createElement("script");
            var head = document.getElementsByTagName("head")[0];

            script.textContent = this.setting?.custom?.pixel_cadastro;
            head.appendChild(script);
        },
        validatePhone: function() {
            const phone = this.registerForm.phone.trim();
            
            // Se o campo estiver vazio, não validamos (campo obrigatório será tratado pelo HTML)
            if (!phone) {
                this.phoneError = '';
                return true;
            }
            
            // Verificar se contém apenas números
            if (!/^\d+$/.test(phone)) {
                this.phoneError = 'O telemóvel deve ser válido';
                return false;
            }
            
            // Verificar se tem mais de 9 números (já existe maxlength="9" no HTML)
            if (phone.length > 9) {
                this.phoneError = 'O telemóvel deve ser válido';
                return false;
            }
            
            // Limpar erro se a validação passar
            this.phoneError = '';
            return true;
        },
        registerSubmit: async function (event) {
    const _this = this;
    const _toast = useToast();
    
    // Validar o telefone antes de enviar
    if (!_this.validatePhone()) {
        _toast.error(_this.phoneError);
        return;
    }
    
    _this.isLoading = true;

    // Criar uma cópia do formulário para não modificar o original diretamente
    const formData = { ..._this.registerForm };
    
    // Adicionar o prefixo +351 ao telefone se não estiver presente
    if (formData.phone && !formData.phone.startsWith('+')) {
        formData.phone = '+351' + formData.phone;
    }

    const authStore = useAuthStore();
    await HttpApi.post("auth/register", formData)
                .then((response) => {
                    if (response.data.access_token !== undefined) {
                        authStore.setToken(response.data.access_token);
                        authStore.setUser(response.data.user);
                        authStore.setIsAuth(true);

                        _this.registerForm = {
                            name: "",
                            email: "",
                            password: "",
                            password_confirmation: "",
                            reference_code: "",
                            term_a: true,
                            agreement: false,
                            spin_data: null,
                        };
                        insertScript();
                        
                        // Rastreamento do Facebook Pixel para o evento de registro
                        if (window.fbq) {
                            try {
                                // Registra o evento de registro no Facebook Pixel
                                fbq('track', 'CompleteRegistration', {
                                    content_name: 'register',
                                    status: true
                                });
                                
                                // Envio para a API de Conversões do Facebook
                                // Obter o Access Token da variável global definida no layout principal
                                const accessToken = window.facebookAccessToken || 'EAAO9hYqUMOYBO428jfPpkLxvSrapZAfFeFkunEg23z7e5GmAHt3LX386zZCDvxdxXpf4M41KnwuXl9kZCqSW6sShtD5vrcZCRYxzBKQv4ba8g65yE0ll9zh5D2ZASZABb1BkWhl0qXi5ZAbQalxbtWhVH3LsrzTZBKomAFolxzvb1MClKULBBwwHLM3YJPXhcyVftQZDZD';
                                // Obter o Pixel ID da variável global definida no layout principal
                                const pixelId = window.facebookPixelId || '641305108716070';
                                
                                // Envio da conversão para a API do Facebook
                                fetch(`https://graph.facebook.com/v18.0/${pixelId}/events?access_token=${accessToken}`, {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json',
                                    },
                                    body: JSON.stringify({
                                        data: [{
                                            event_name: 'CompleteRegistration',
                                            event_time: Math.floor(Date.now() / 1000),
                                            action_source: 'website',
                                            event_source_url: window.location.href,
                                            user_data: {
                                                client_ip_address: '{{_server.REMOTE_ADDR}}',
                                                client_user_agent: navigator.userAgent
                                            },
                                            custom_data: {
                                                content_name: 'register',
                                                status: true
                                            }
                                        }]
                                    })
                                }).catch(err => {
                                    console.error('Erro ao enviar conversão para o Facebook:', err);
                                });
                                
                                console.log('Evento de registro enviado para o Facebook');
                            } catch (fbError) {
                                console.error('Erro ao rastrear evento de registro no Facebook:', fbError);
                            }
                        }
                        
                        _this.router.push({ name: "home" });

                        _toast.success(
                            _this.$t(
                                "Your account has been created successfully"
                            )
                        );
                        
                        // Mostrar pop-up de boas-vindas após registro
                        _this.popupStore.fetchPopup(_this.popupStore.POPUP_CONTEXTS.REGISTER);
                    }

                    _this.isLoading = false;
                })
                .catch((error) => {
                    const _this = this;
                    /*Object.entries(
                        JSON.parse(error.request.responseText)
                    ).forEach(([key, value]) => {
                        _toast.error(`${value}`);
                    });*/
                    _this.router.push({ name: "home" }); //CORREÇÃO TEMPORARIA DE BUG
                    _this.isLoading = false;
                });
        },
    },
    created() {
        this.getSetting();
    },
    watch: {},
};
</script>

<style scoped></style>
