<template>
    <AuthLayout>
        <LoadingComponent :isLoading="isLoading">
            <div class="flex justify-center items-center h-full">
                <a v-if="setting" href="/" class="logo-animation"> <!-- Adicionando a classe items-center para centralizar verticalmente -->
                    <img :src="`/storage/` + setting.software_logo_black" alt="" class="h-10 mr-3 block dark:hidden" />
                    <img :src="`/storage/` + setting.software_logo_white" alt="" class="h-10 mr-3 hidden dark:block" />
                </a>
            </div>
        </LoadingComponent>

        <div v-if="!isLoading" class="my-auto pt-16">
            <div class="px-0 py-0">
                <div class="min-h-[calc(100vh-565px)] text-center flex flex-col items-center justify-center">
                    <div class="w-full md:max-w-md lg:max-w-lg bg-gray-100 md:mt-0 dark:bg-gray-700 mx-auto shadow-lg rounded-lg">
                        <div class="p-4 space-y-3 md:space-y-4">
                            <h1 class="mb-4 text-2xl text-center font-bold">{{ $t('Iniciar Sessão') }}</h1>

                            <!-- Mensagem de erro -->
                            <div v-if="errorMessage" class="mb-6 p-4 bg-gradient-to-r from-red-50 to-red-100 dark:from-red-900/30 dark:to-red-800/30 border-l-4 border-red-500 rounded-lg shadow-sm">
                                <div class="flex items-start space-x-3">
                                    <div class="flex-shrink-0">
                                        <div class="w-8 h-8 bg-red-500 rounded-full flex items-center justify-center">
                                            <i class="fas fa-ban text-white text-sm"></i>
                                        </div>
                                    </div>
                                    <div class="flex-1">
                                        <h3 class="text-sm font-semibold text-red-800 dark:text-red-200 mb-1">
                                            Conta Suspensa
                                        </h3>
                                        <p class="text-sm text-red-700 dark:text-red-300 leading-relaxed">
                                            {{ errorMessage }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Banner de login -->
                            <div v-if="loginBanner" class="mb-4 rounded-lg overflow-hidden shadow-lg">
                                <div>
                                    <img :src="loginBanner.image.startsWith('http') ? loginBanner.image : '/storage/' + loginBanner.image" :alt="loginBanner.description" class="w-full h-auto">
                                </div>
                            </div>

                            <div class="mt-2">
                                <form @submit.prevent="loginSubmit" method="post" action="" class="">

                                    <div class="mb-3">
                                        <div class="relative">
                                            <div class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none">
                                                <i class="fa-regular fa-envelope text-blue-600" style="z-index: 1;"></i>
                                            </div>
                                            <input required type="text" v-model="loginForm.email" name="email" class="input-group bg-gray-700 border-gray-600 text-white placeholder-gray-400 focus:ring-blue-500 focus:border-blue-500" :placeholder="$t('Coloque o seu e-mail')">
                                        </div>
                                    </div>

                                    <div class="mb-4">
                                        <div class="relative">
                                            <div class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none">
                                                <i class="fa-solid fa-key text-blue-600" style="z-index: 1;"
                                                ></i>
                                            </div>
                                            <input required :type="typeInputPassword"
                                                   v-model="loginForm.password"
                                                   name="password"
                                                   class="input-group pr-[40px] bg-gray-700 border-gray-600 text-white placeholder-gray-400 focus:ring-blue-500 focus:border-blue-500"
                                                   :placeholder="$t('Coloque a sua senha')">
                                            <button type="button" @click.prevent="togglePassword" class="absolute inset-y-0 right-0 flex items-center pr-3.5 ">
                                                <i v-if="typeInputPassword === 'password'" class="fa-regular fa-eye"></i>
                                                <i v-if="typeInputPassword === 'text'" class="fa-sharp fa-regular fa-eye-slash"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <a @click.prevent="$router.push('/forgot-password')" href="" class="text-white text-sm">{{ $t('Forgot password') }}</a>

                                    <div class="mt-5 w-full">
                                        <button type="submit" class="ui-button-blue rounded w-full py-3 mb-3 text-lg font-bold">
                                            {{ $t('Entrar') }}
                                        </button>
                                    </div>
                                    <p class="text-sm text-gray-300 mb-6">
                                        {{ $t('Not have an account yet') }}?
                                        <RouterLink :to="{ name: 'register' }" active-class="top-register-active" class="">
                                            <strong>{{ $t('Create an account') }}</strong>
                                        </RouterLink>
                                    </p>
                                </form>

                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthLayout>
</template>


<script>

import {useToast} from "vue-toastification";
import {useAuthStore} from "@/Stores/Auth.js";
import {usePopupStore} from "@/Stores/PopupStore.js";
import HttpApi from "@/Services/HttpApi.js";
import AuthLayout from "@/Layouts/AuthLayout.vue";
import {useRouter} from "vue-router";
import LoadingComponent from "@/Components/UI/LoadingComponent.vue";
import { onMounted } from "vue";

export default {
    props: [],
    components: { LoadingComponent, AuthLayout },
    data() {
        return {
            isLoading: false,
            typeInputPassword: 'password',
            isReferral: false,
            loginBanner: null,
            errorMessage: null,
            loginForm: {
                email: '',
                password: '',
            },
        }
    },
    setup(props) {
        const router = useRouter();
        const popupStore = usePopupStore();
        return {
            router,
            popupStore
        };
    },
    computed: {
        isAuthenticated() {
            const authStore = useAuthStore();
            return authStore.isAuth;
        },
    },
    mounted() {
        const router = useRouter();
        if(this.isAuthenticated) {
            router.push({ name: 'home' });
        }
        
        // Buscar o banner de login quando o componente for montado
        this.fetchLoginBanner();
    },
    methods: {
        fetchLoginBanner: function() {
            HttpApi.get('banners/login')
                .then(response => {
                    if (response.data.success) {
                        this.loginBanner = response.data.banner;
                    }
                })
                .catch(error => {
                    console.error('Erro ao buscar banner de login:', error);
                });
        },
        redirectSocialTo: function() {
            return '/auth/redirect/google'
        },
        loginToggle: function() {
            this.modalAuth.toggle();
        },
        loginSubmit: async function(event) {
            const _this = this;
            const _toast = useToast();
            _this.isLoading = true;
            _this.errorMessage = null; // Limpar mensagem de erro anterior
            const authStore = useAuthStore();

            await HttpApi.post('auth/login', _this.loginForm)
                .then(async response =>  {
                    await new Promise(r => {
                        setTimeout(() => {
                            authStore.setToken(response.data.access_token);
                            authStore.setUser(response.data.user);
                            authStore.setIsAuth(true);

                            _this.loginForm = {
                                email: '',
                                password: '',
                            }

                            _this.router.push({ name: 'home' });
                            _toast.success(_this.$t('You have been authenticated, welcome!'));
                            
                            // Mostrar pop-up de boas-vindas após login
                            _this.popupStore.fetchPopup(_this.popupStore.POPUP_CONTEXTS.LOGIN);

                            _this.isLoading = false;
                        }, 1000)
                    });

                })
                .catch(error => {
                    const _this = this;
                    try {
                        const errorData = JSON.parse(error.request.responseText);
                        
                        // Verificar se é um erro de banimento
                        if (errorData.banned && errorData.error) {
                            _this.errorMessage = errorData.error;
                        } else {
                            // Limpar mensagem de erro anterior
                            _this.errorMessage = null;
                            // Tratar outros tipos de erro
                            Object.entries(errorData).forEach(([key, value]) => {
                                _toast.error(`${value}`);
                            });
                        }
                    } catch (parseError) {
                        // Fallback se não conseguir fazer parse do JSON
                        _toast.error('Erro ao fazer login. Tente novamente.');
                    }
                    _this.isLoading = false;
                });
        },
        togglePassword: function() {
            if(this.typeInputPassword === 'password') {
                this.typeInputPassword = 'text';
            }else{
                this.typeInputPassword = 'password';
            }
        },
    },
    watch: {

    },
};
</script>

<style scoped>

</style>
