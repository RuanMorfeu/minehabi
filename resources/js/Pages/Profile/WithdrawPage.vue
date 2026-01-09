<template>
    <BaseLayout>
        <div class="p-4 mx-auto mt-20 md:w-4/6 2xl:w-4/6">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                <div class="hidden col-span-1 md:block">
                    <WalletSideMenu />
                </div>
                <div class="relative col-span-2">
                    <div
                        v-if="
                            setting != null &&
                            wallet != null &&
                            isLoading === false
                        "
                        class="flex flex-col w-full p-4 bg-gray-200 border border-blue-500 rounded shadow-lg dark:border-gray-700 hover:bg-blue-700/20 dark:bg-gray-700"
                    >
                        <!-- Mensagem de aviso quando o usuário não tem depósitos -->
                        <div v-if="!hasDeposits && !checkingDeposits" class="p-4 mb-4 text-sm text-red-800 rounded-lg bg-red-50 dark:bg-gray-800 dark:text-red-400">
                            <p class="font-medium">Atenção!</p>
                            <p>Você precisa fazer pelo menos um depósito antes de solicitar um saque.</p>
                        </div>
                        <!-- Loader enquanto verifica depósitos -->
                        <div v-if="checkingDeposits" class="p-4 mb-4 text-sm text-blue-800 rounded-lg bg-blue-50 dark:bg-gray-800 dark:text-blue-400">
                            <p>Verificando informações...</p>
                        </div>
                        <form
                            v-if="wallet.currency === 'USD'"
                            action=""
                            @submit.prevent="submitWithdrawBank"
                            :class="{'opacity-50': !hasDeposits && !checkingDeposits}"
                        >
                            <div class="flex items-center justify-between">
                                <div>
                                    <i
                                        class="mr-3 text-lg fa-regular fa-building-columns"
                                    ></i>
                                    <span class="ml-3">{{ $t("BANK") }}</span>
                                </div>
                                <button
                                    @click.prevent="
                                        $router.push('/profile/wallet')
                                    "
                                    type="button"
                                    class="flex items-center justify-center pt-1 mr-3"
                                >
                                    <div>{{ wallet.currency }}</div>
                                    <div class="ml-2 mr-2">
                                        <img
                                            :src="
                                                `/assets/images/coin/` +
                                                wallet.currency +
                                                `.png`
                                            "
                                            alt=""
                                            width="32"
                                        />
                                    </div>
                                    <div class="ml-2 text-sm">
                                        <i class="fa-solid fa-chevron-down"></i>
                                    </div>
                                </button>
                            </div>

                            <div class="mt-5">
                                <div class="mb-3 text-sm dark:text-gray-400">
                                    <label for=""
                                        >Nome do titular da conta</label
                                    >
                                    <input
                                        v-model="withdraw_deposit.name"
                                        type="text"
                                        class="input"
                                        placeholder="Digite o nome do titular da conta"
                                        required
                                    />
                                </div>

                                <div class="mt-5">
                                    <label
                                        for="message"
                                        class="block mb-2 text-sm font-medium text-gray-900 dark:text-white"
                                        >{{ $t("Banking information") }}</label
                                    >
                                    <textarea
                                        v-model="withdraw_deposit.bank_info"
                                        id="message"
                                        cols="30"
                                        rows="10"
                                        class="input min-h-[250px]"
                                        :placeholder="
                                            $t('Enter bank information')
                                        "
                                    ></textarea>
                                </div>

                                <div class="mt-4 mb-3 dark:text-gray-400">
                                    <div class="flex justify-between text-sm">
                                        <p>
                                            Valor ({{
                                                setting.min_withdrawal
                                            }}
                                            ~ {{ setting.max_withdrawal }})
                                        </p>
                                        <p>
                                            Saldo:
                                            {{
                                                state.currencyFormat(
                                                    parseFloat(
                                                        wallet.balance_withdrawal
                                                    ),
                                                    wallet.currency
                                                )
                                            }}
                                            {{ wallet.currency }}
                                        </p>
                                    </div>
                                    <div class="flex bg-white dark:bg-gray-900">
                                        <input
                                            type="text"
                                            class="input"
                                            v-model="withdraw_deposit.amount"
                                            :min="setting.min_withdrawal"
                                            :max="setting.max_withdrawal"
                                            placeholder=""
                                            required
                                        />
                                        <div class="flex items-center pr-1">
                                            <div
                                                class="inline-flex shadow-sm"
                                                role="group"
                                            >
                                                <button
                                                    @click.prevent="
                                                        setMinAmount
                                                    "
                                                    type="button"
                                                    class="px-4 py-2 text-sm font-medium text-gray-900 bg-white border border-gray-200 hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:ring-2 focus:ring-blue-700 focus:text-blue-700 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:hover:text-white dark:hover:bg-gray-600 dark:focus:ring-blue-500 dark:focus:text-white"
                                                >
                                                    min
                                                </button>
                                                <button
                                                    @click.prevent="
                                                        setPercentAmount(50)
                                                    "
                                                    type="button"
                                                    class="px-4 py-2 text-sm font-medium text-gray-900 bg-white border-t border-b border-gray-200 hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:ring-2 focus:ring-blue-700 focus:text-blue-700 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:hover:text-white dark:hover:bg-gray-600 dark:focus:ring-blue-500 dark:focus:text-white"
                                                >
                                                    50%
                                                </button>
                                                <button
                                                    @click.prevent="
                                                        setPercentAmount(100)
                                                    "
                                                    type="button"
                                                    class="px-4 py-2 text-sm font-medium text-gray-900 bg-white border border-gray-200 hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:ring-2 focus:ring-blue-700 focus:text-blue-700 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:hover:text-white dark:hover:bg-gray-600 dark:focus:ring-blue-500 dark:focus:text-white"
                                                >
                                                    100%
                                                </button>
                                                <button
                                                    @click.prevent="
                                                        setMaxAmount
                                                    "
                                                    type="button"
                                                    class="px-4 py-2 text-sm font-medium text-gray-900 bg-white border border-gray-200 hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:ring-2 focus:ring-blue-700 focus:text-blue-700 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:hover:text-white dark:hover:bg-gray-600 dark:focus:ring-blue-500 dark:focus:text-white"
                                                >
                                                    max
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    <div
                                        class="flex justify-between mt-2 text-sm"
                                    >
                                        <p>
                                            {{ $t("Available") }}:
                                            {{
                                                state.currencyFormat(
                                                    parseFloat(
                                                        wallet.balance_withdrawal
                                                    ),
                                                    wallet.currency
                                                )
                                            }}
                                            {{ wallet.currency }}
                                        </p>
                                        <p>
                                            {{ $t("Balance Rollback") }}:
                                            {{
                                                state.currencyFormat(
                                                    parseFloat(
                                                        wallet.balance_bonus
                                                    ),
                                                    wallet.currency
                                                )
                                            }}
                                            {{ wallet.currency }}
                                        </p>
                                    </div>
                                </div>

                                <div class="mt-5 mb-3">
                                    <div class="flex items-center mb-4">
                                        <input
                                            id="accept_terms_checkbox"
                                            v-model="
                                                withdraw_deposit.accept_terms
                                            "
                                            type="checkbox"
                                            value=""
                                            class="w-4 h-4 text-blue-600 bg-gray-100 border-blue-500 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600"
                                        />
                                        <label
                                            for="accept_terms_checkbox"
                                            class="text-sm font-medium text-gray-900 ms-2 dark:text-gray-400"
                                        >
                                            {{
                                                $t(
                                                    "I accept the transfer terms"
                                                )
                                            }}
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <div
                                class="flex items-center justify-center w-full mt-5"
                            >
                                <button
                                    type="submit"
                                    class="w-full ui-button-blue"
                                >
                                    <span
                                        class="text-sm font-semibold uppercase"
                                        >{{ $t("Request withdrawal") }}</span
                                    >
                                </button>
                            </div>
                        </form>

                        <form
                            v-if="wallet.currency === 'BRL'"
                            action=""
                            @submit.prevent="submitWithdraw"
                            :class="{'opacity-50': !hasDeposits && !checkingDeposits}"
                        >
                            <div class="flex items-center justify-between">
                                <p class="text-gray-500">
                                    {{ $t("Withdraw Coin") }}
                                </p>
                                <button
                                    @click.prevent="
                                        $router.push('/profile/wallet')
                                    "
                                    type="button"
                                    class="flex items-center justify-center pt-1 mr-3"
                                >
                                    <div>{{ wallet.currency }}</div>
                                    <div class="ml-2 mr-2">
                                        <img
                                            :src="
                                                `/assets/images/coin/` +
                                                wallet.currency +
                                                `.png`
                                            "
                                            alt=""
                                            width="32"
                                        />
                                    </div>
                                    <div class="ml-2 text-sm">
                                        <i class="fa-solid fa-chevron-down"></i>
                                    </div>
                                </button>
                            </div>

                            <div class="mt-5">
                                <p class="mb-2 text-gray-500">
                                    {{ $t("Withdraw with") }}
                                </p>
                                <div
                                    class="flex items-center justify-between w-full p-2 bg-white rounded dark:bg-gray-900"
                                >
                                    <div class="flex items-center w-full">
                                        <img
                                            :src="`/assets/images/pix.png`"
                                            alt=""
                                            width="100"
                                        />
                                        <span class="ml-3">PIX</span>
                                    </div>
                                    <div class="w-8">
                                        <i class="fa-solid fa-chevron-down"></i>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-5">
                                <div class="mb-3 dark:text-gray-400">
                                    <label for=""
                                        >Nome do titular da conta</label
                                    >
                                    <input
                                        v-model="withdraw.name"
                                        @input="validateAccountHolderName"
                                        type="text"
                                        class="input"
                                        placeholder="Digite o nome do titular da conta"
                                        required
                                    />
                                    <span v-if="errors.name" class="text-red-500 text-sm">{{ errors.name }}</span>
                                </div>
                                <div class="mb-3 text-sm dark:text-gray-400">
                                    <label for="">CPF</label>
                                    <input
                                        @input="setCpfFormater()"
                                        v-model="withdraw.cpf"
                                        type="text"
                                        class="input"
                                        placeholder="Digite o CPF"
                                        required
                                    />
                                </div>
                                <div class="mb-3 text-sm dark:text-gray-400" v-if="wallet.currency === 'EUR'">
                                    <label for="">NIF 
                                        <span v-if="nifPrefilledFromKyc" class="text-xs text-blue-600 dark:text-blue-500">
                                            (preenchido automaticamente da verificação)
                                        </span>
                                    </label>
                                    <input
                                        v-model="withdraw.nif"
                                        type="text"
                                        class="input"

                                        placeholder="Digite o NIF"
                                        required
                                    />

                                </div>
                                <div class="mb-3 dark:text-gray-400">
                                    <label for="">Chave Pix</label>
                                    <input
                                        v-model="withdraw.pix_key"
                                        type="text"
                                        class="input"
                                        placeholder="Digite a sua Chave pix"
                                        required
                                    />
                                </div>

                                <div class="mb-3 dark:text-gray-400">
                                    <label for="">Tipo de Chave</label>
                                    <select
                                        v-model="withdraw.pix_type"
                                        name="type_document"
                                        class="input"
                                        required
                                    >
                                        <option value="">
                                            Selecione uma chave
                                        </option>
                                        <option value="document">
                                            CPF/CNPJ
                                        </option>
                                        <option value="email">E-mail</option>
                                        <option value="phoneNumber">
                                            Telefone
                                        </option>
                                        <option value="randomKey">
                                            Chave Aleatória
                                        </option>
                                    </select>
                                </div>

                                <div class="mb-3 dark:text-gray-400">
                                    <div class="flex justify-between mb-3">
                                        <p>
                                            Valor ({{
                                                setting.min_withdrawal
                                            }}
                                            ~ {{ setting.max_withdrawal }})
                                        </p>
                                        <p>
                                            Saldo:
                                            {{
                                                state.currencyFormat(
                                                    parseFloat(
                                                        wallet.balance_withdrawal
                                                    ),
                                                    wallet.currency
                                                )
                                            }}
                                        </p>
                                    </div>
                                    <div class="flex bg-white dark:bg-gray-900">
                                        <input
                                            type="text"
                                            class="input"
                                            @input="setAmountFormater()"
                                            v-model="withdraw.amount"
                                            :min="setting.min_withdrawal"
                                            :max="setting.max_withdrawal"
                                            placeholder=""
                                            required
                                        />
                                        <div class="flex items-center pr-1">
                                            <div
                                                class="inline-flex shadow-sm"
                                                role="group"
                                            >
                                                <button
                                                    @click.prevent="
                                                        setMinAmount
                                                    "
                                                    type="button"
                                                    class="px-4 py-2 text-sm font-medium text-gray-900 bg-white border border-gray-200 hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:ring-2 focus:ring-blue-700 focus:text-blue-700 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:hover:text-white dark:hover:bg-gray-600 dark:focus:ring-blue-500 dark:focus:text-white"
                                                >
                                                    min
                                                </button>
                                                <button
                                                    @click.prevent="
                                                        setPercentAmount(50)
                                                    "
                                                    type="button"
                                                    class="px-4 py-2 text-sm font-medium text-gray-900 bg-white border-t border-b border-gray-200 hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:ring-2 focus:ring-blue-700 focus:text-blue-700 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:hover:text-white dark:hover:bg-gray-600 dark:focus:ring-blue-500 dark:focus:text-white"
                                                >
                                                    50%
                                                </button>
                                                <button
                                                    @click.prevent="
                                                        setPercentAmount(100)
                                                    "
                                                    type="button"
                                                    class="px-4 py-2 text-sm font-medium text-gray-900 bg-white border border-gray-200 hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:ring-2 focus:ring-blue-700 focus:text-blue-700 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:hover:text-white dark:hover:bg-gray-600 dark:focus:ring-blue-500 dark:focus:text-white"
                                                >
                                                    100%
                                                </button>
                                                <button
                                                    @click.prevent="
                                                        setMaxAmount
                                                    "
                                                    type="button"
                                                    class="px-4 py-2 text-sm font-medium text-gray-900 bg-white border border-gray-200 hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:ring-2 focus:ring-blue-700 focus:text-blue-700 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:hover:text-white dark:hover:bg-gray-600 dark:focus:ring-blue-500 dark:focus:text-white"
                                                >
                                                    max
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex justify-between mt-2">
                                        <p>
                                            {{ $t("Available") }}:
                                            {{
                                                state.currencyFormat(
                                                    parseFloat(
                                                        wallet.balance_withdrawal
                                                    ),
                                                    wallet.currency
                                                )
                                            }}
                                            {{ wallet.currency }}
                                        </p>
                                        <p>
                                            {{ $t("Balance Rollback") }}:
                                            {{
                                                state.currencyFormat(
                                                    parseFloat(
                                                        wallet.balance_bonus
                                                    ),
                                                    wallet.currency
                                                )
                                            }}
                                            {{ wallet.currency }}
                                        </p>
                                    </div>
                                </div>

                                <div class="mt-5 mb-3">
                                    <div class="flex items-center mb-4">
                                        <input
                                            id="accept_terms_checkbox"
                                            v-model="withdraw.accept_terms"
                                            type="checkbox"
                                            value=""
                                            class="w-4 h-4 text-blue-600 bg-gray-100 border-blue-500 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600"
                                        />
                                        <label
                                            for="accept_terms_checkbox"
                                            class="text-sm font-medium text-gray-900 ms-2 dark:text-gray-400"
                                        >
                                            {{
                                                $t(
                                                    "I accept the transfer terms"
                                                )
                                            }}
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <div
                                class="flex items-center justify-center w-full mt-5"
                            >
                                <button
                                    type="submit"
                                    class="w-full ui-button-blue"
                                >
                                    <span
                                        class="text-sm font-semibold uppercase"
                                        >{{ $t("Request withdrawal") }}</span
                                    >
                                </button>
                            </div>
                        </form>

                        <form class="space-y-3" 
                            v-if="wallet.currency === 'EUR'"
                            @submit.prevent="submitWithdraw"
                            :class="{'opacity-50': !hasDeposits && !checkingDeposits}"
                        >
                            <div>
                                <InputLabel class="mb-2">Montante</InputLabel>
                                <input
                                    type="text"
                                    class="input"
                                    @input="setAmountFormater(); errors.amount = ''"
                                    v-model="withdraw.amount"
                                    :min="setting.min_withdrawal"
                                    :max="setting.max_withdrawal"
                                    placeholder=""
                                />
                                <div v-if="errors.amount" class="mt-1 text-sm text-red-600">{{ errors.amount }}</div>
                            </div>
                            <div>
                                <span class="text-xs uppercase">
                                    Saldo disponível para retirada EUR {{ wallet.balance_withdrawal }}
                                </span>
                            </div>
                            <div>
                                <InputLabel class="mb-2">Meio de saque</InputLabel>
                                <select
                                    class="w-full rounded bg-zinc-900">
                                    <option value="iban" selected="selected">IBAN</option>
                                </select>
                            </div>
                            <div>
                                <InputLabel class="mb-2">Nome do titular da conta</InputLabel>
                                <TextInput
                                    class="w-full"
                                    v-model="withdraw.name"
                                    @input="validateAccountHolderName; errors.name = ''"
                                    placeholder="Digite o nome do titular da conta"
                                />
                                <div v-if="errors.name" class="mt-1 text-sm text-red-600">{{ errors.name }}</div>
                            </div>
                            <div>
                                <InputLabel class="mb-2">NIF 
                                    <span v-if="nifPrefilledFromKyc" class="text-xs text-blue-600 dark:text-blue-500">
                                        (preenchido automaticamente da verificação)
                                    </span>
                                </InputLabel>
                                <TextInput
                                    class="w-full"

                                    v-model="withdraw.nif"
                                    @input="validateNIF"
                                    placeholder="Digite o NIF"
                                />

                                <div v-if="errors.nif" class="mt-1 text-sm text-red-600">{{ errors.nif }}</div>
                            </div>
                            <div>
                                <InputLabel class="mb-2">IBAN</InputLabel>
                                <TextInput
                                    class="w-full"
                                    v-model="withdraw.pix_key"
                                    placeholder="Ex. PT61 1090 1014 0000 0712"
                                />
                                <div v-if="errors.iban" class="mt-1 text-sm text-red-600">{{ errors.iban }}</div>
                            </div>
                            <div class="mt-4">
                                <button type="submit" class="ui-button-blue">
                                    Solicitar Saque
                                </button>
                            </div>
                        </form>

                    </div>
                    <div
                        v-if="isLoading"
                        role="status"
                        class="absolute -translate-x-1/2 -translate-y-1/2 top-2/4 left-1/2"
                    >
                        <svg
                            aria-hidden="true"
                            class="w-8 h-8 text-gray-200 animate-spin dark:text-gray-600 fill-blue-600"
                            viewBox="0 0 100 101"
                            fill="none"
                            xmlns="http://www.w3.org/2000/svg"
                        >
                            <path
                                d="M100 50.5908C100 78.2051 77.6142 100.591 50 100.591C22.3858 100.591 0 78.2051 0 50.5908C0 22.9766 22.3858 0.59082 50 0.59082C77.6142 0.59082 100 22.9766 100 50.5908ZM9.08144 50.5908C9.08144 73.1895 27.4013 91.5094 50 91.5094C72.5987 91.5094 90.9186 73.1895 90.9186 50.5908C90.9186 27.9921 72.5987 9.67226 50 9.67226C27.4013 9.67226 9.08144 27.9921 9.08144 50.5908Z"
                                fill="currentColor"
                            />
                            <path
                                d="M93.9676 39.0409C96.393 38.4038 97.8624 35.9116 97.0079 33.5539C95.2932 28.8227 92.871 24.3692 89.8167 20.348C85.8452 15.1192 80.8826 10.7238 75.2124 7.41289C69.5422 4.10194 63.2754 1.94025 56.7698 1.05124C51.7666 0.367541 46.6976 0.446843 41.7345 1.27873C39.2613 1.69328 37.813 4.19778 38.4501 6.62326C39.0873 9.04874 41.5694 10.4717 44.0505 10.1071C47.8511 9.54855 51.7191 9.52689 55.5402 10.0491C60.8642 10.7766 65.9928 12.5457 70.6331 15.2552C75.2735 17.9648 79.3347 21.5619 82.5849 25.841C84.9175 28.9121 86.7997 32.2913 88.1811 35.8758C89.083 38.2158 91.5421 39.6781 93.9676 39.0409Z"
                                fill="currentFill"
                            />
                        </svg>
                        <span class="sr-only">{{ $t("Loading") }}...</span>
                    </div>
                </div>
            </div>
        </div>
    </BaseLayout>
</template>

<script>
import { RouterLink, useRouter } from "vue-router";
import BaseLayout from "@/Layouts/BaseLayout.vue";
import WalletSideMenu from "@/Pages/Profile/Components/WalletSideMenu.vue";
import HttpApi from "@/Services/HttpApi.js";
import { useToast } from "vue-toastification";
import { useSettingStore } from "@/Stores/SettingStore.js";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import TextInput from "@/Components/TextInput.vue";
import * as IBAN from 'iban'; // Importando a biblioteca de validação de IBAN
import { validateNIF } from 'validate-nif'; // Importando a biblioteca de validação de NIF

export default {
    props: [],
    components: { WalletSideMenu, BaseLayout, RouterLink, PrimaryButton, TextInput },
    data() {
        return {
            isLoading: true,
            isLoadingWallet: false,
            wallet: null,
            setting: null,
            user: null,
            hasDeposits: false,
            checkingDeposits: true,
            isKycApproved: false,
            checkingKyc: true,
            nifPrefilledFromKyc: false,
            withdraw: {
                name: "",
                pix_key: "",
                pix_type: "",
                amount: "",
                type: "",
                nif: "",
                accept_terms: false,
            },
            withdraw_deposit: {
                name: "",
                bank_info: "",
                amount: "",
                type: "",
                cpf: "",
                accept_terms: false,
            },
            errors: {
                amount: '',
                name: '',
                iban: '',
                nif: ''
            }
        };
    },
    setup(props) {
        const router = useRouter();
        return {
            router,
        };
    },
    computed: {
        shouldShowKycWarning() {
            // Só mostra aviso se KYC estiver habilitado e não aprovado
            if (!this.setting || !this.setting.kyc_required) {
                return false;
            }
            return !this.isKycApproved && !this.checkingKyc;
        }
    },
    mounted() {},
    methods: {
        setMinAmount: function () {
            this.withdraw.amount = (
                this.setting.min_withdrawal * 100
            ).toString();
            this.setAmountFormater();
        },
        setMaxAmount: function () {
            this.withdraw.amount = (
                this.setting.max_withdrawal * 100
            ).toString();
            this.setAmountFormater();
        },
        setAmountFormater: function () {
            if (this.withdraw_deposit) {
                // Remove qualquer caractere que não seja número ou ponto
                this.withdraw_deposit.amount = this.withdraw_deposit.amount.replace(/[^\d.]/g, '');
                // Garante que só existe um ponto decimal
                const parts = this.withdraw_deposit.amount.split('.');
                if (parts.length > 2) {
                    this.withdraw_deposit.amount = parts[0] + '.' + parts.slice(1).join('');
                }
            }
            if (this.withdraw) {
                // Remove qualquer caractere que não seja número ou ponto
                this.withdraw.amount = this.withdraw.amount.replace(/[^\d.]/g, '');
                // Garante que só existe um ponto decimal
                const parts = this.withdraw.amount.split('.');
                if (parts.length > 2) {
                    this.withdraw.amount = parts[0] + '.' + parts.slice(1).join('');
                }
            }
            var valorStr = this.withdraw.amount.replace(/\D/g, "");
            var valorNum = parseFloat(valorStr) / 100;

            if (!isNaN(valorNum)) {
                const opcoes = {
                    style: "decimal",
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2,
                };
                this.withdraw.amount = valorNum.toLocaleString("pt-BR", opcoes);
            }
        },
        setCpfFormater: function () {
            var cpf = this.withdraw.cpf.replace(/\D/g, "");
            if (cpf.length > 11) {
                cpf = cpf.slice(0, 11);
            }

            var cpfFormatado = "";

            if (cpf.length <= 3) {
                cpfFormatado = cpf;
            } else if (cpf.length <= 6) {
                cpfFormatado = cpf.replace(/(\d{3})/, "$1.");
            } else if (cpf.length <= 9) {
                cpfFormatado = cpf.replace(/(\d{3})(\d{3})/, "$1.$2.");
            } else if (cpf.length <= 11) {
                cpfFormatado = cpf.replace(
                    /(\d{3})(\d{3})(\d{3})/,
                    "$1.$2.$3-"
                );
            }

            this.withdraw.cpf = cpfFormatado;
        },
        setPercentAmount: function (percent) {
            this.withdraw.amount = (
                (percent / 100) *
                this.wallet.balance_withdrawal *
                100
            ).toString();
            this.setAmountFormater();
        },
        getWallet: function () {
            const _this = this;
            const _toast = useToast();
            _this.isLoadingWallet = true;

            HttpApi.get("profile/wallet")
                .then((response) => {
                    _this.wallet = response.data.wallet;
                    _this.wallet.user = response.data.user;

                    _this.withdraw.currency = response.data.wallet.currency;

                    if(_this.withdraw.currency == 'EUR'){
                        _this.withdraw.pix_type = 'IBAN';
                        _this.withdraw.accept_terms = true;
                    }

                    _this.withdraw.symbol = response.data.wallet.symbol;

                    _this.withdraw_deposit.currency =
                        response.data.wallet.currency;
                    _this.withdraw_deposit.symbol = response.data.wallet.symbol;

                    _this.isLoadingWallet = false;
                })
                .catch((error) => {
                    const _this = this;
                    Object.entries(
                        JSON.parse(error.request.responseText)
                    ).forEach(([key, value]) => {
                        _toast.error(`${value}`);
                    });
                    _this.isLoadingWallet = false;
                });
        },
        getSetting: function () {
            const _this = this;
            const settingStore = useSettingStore();
            const settingData = settingStore.setting;

            if (settingData) {
                _this.setting = settingData;
                _this.withdraw.amount = settingData.min_withdrawal;
                _this.withdraw_deposit.amount = settingData.min_withdrawal;
                // Verifica se pode executar KYC check agora
                _this.tryCheckKyc();
            }

            _this.isLoading = false;
        },
        submitWithdrawBank: function (event) {
            const _this = this;
            const _toast = useToast();
            
            // Verificar se o usuário já fez pelo menos um depósito
            if (!_this.hasDeposits && !_this.checkingDeposits) {
                _toast.error('Você precisa fazer pelo menos um depósito antes de solicitar um saque');
                return;
            }
            
            // Se ainda estiver verificando os depósitos, aguarde
            if (_this.checkingDeposits) {
                _toast.info('Aguarde enquanto verificamos suas informações...');
                return;
            }

            // Validar saldo suficiente
            const amount = parseFloat(_this.withdraw.amount?.replace(/\D/g, "")) / 100;
            const balance = parseFloat(_this.wallet.balance_withdrawal);
            if (amount > balance) {
                _toast.error('Saldo insuficiente para realizar o saque');
                return;
            }

            _this.isLoading = true;
            _this.withdraw_deposit.amount = amount;
            if (_this.withdraw_deposit.cpf) {
                _this.withdraw_deposit.cpf = _this.withdraw_deposit.cpf.replace(/\D/g, "");
            }
            // Garantir que o tipo seja definido
            if (!_this.withdraw_deposit.type) {
                _this.withdraw_deposit.type = "bank";
            }
            HttpApi.post("wallet/withdraw/request", _this.withdraw_deposit)
                .then((response) => {
                    _this.isLoading = false;
                    _this.withdraw_deposit = {
                        name: "",
                        bank_info: "",
                        amount: "",
                        type: "",
                        cpf: "",
                        accept_terms: false,
                    };

                    _this.router.push({ name: "profileTransactions" });
                    _toast.success(response.data.message);
                })
                .catch((error) => {
                    this.withdraw.amount = (
                        this.withdraw.amount * 100
                    ).toString();
                    this.setAmountFormater();
                    this.setCpfFormater();
                    Object.entries(
                        JSON.parse(error.request.responseText)
                    ).forEach(([key, value]) => {
                        _toast.error(`${value}`);
                    });
                    _this.isLoading = false;
                });
        },
        submitWithdraw: function (event) {
            // Reset error messages
            this.errors.amount = '';
            this.errors.name = '';
            this.errors.iban = '';
            this.errors.nif = '';
            
            // Verificar se o usuário já fez pelo menos um depósito
            if (!this.hasDeposits && !this.checkingDeposits) {
                const _toast = useToast();
                _toast.error('Você precisa fazer pelo menos um depósito antes de solicitar um saque');
                return;
            }
            
            // Se ainda estiver verificando os depósitos, aguarde
            if (this.checkingDeposits) {
                const _toast = useToast();
                _toast.info('Aguarde enquanto verificamos suas informações...');
                return;
            }

            // 1. Validar nome completo
            if (!this.validateAccountHolderName()) {
                return; // Se a validação falhar, não enviar o formulário
            }
            
            // 1.1 Validar NIF (se estiver preenchido)
            if (this.withdraw.nif && !this.validateNIF()) {
                return; // Se a validação falhar, não enviar o formulário
            }

            // 2. Validar valor
            if (!this.withdraw.amount) {
                this.errors.amount = 'Por favor, insira um valor para saque.';
                return;
            }

            const amount = parseFloat(this.withdraw.amount.replace(/\D/g, "")) / 100;
            
            // Validar saldo suficiente
            const balance = parseFloat(this.wallet.balance_withdrawal);
            if (amount > balance) {
                this.errors.amount = 'Saldo insuficiente para realizar o saque';
                return;
            }

            if (amount < this.setting.min_withdrawal || amount > this.setting.max_withdrawal) {
                this.errors.amount = `O valor deve estar entre ${this.setting.min_withdrawal} e ${this.setting.max_withdrawal}`;
                return;
            }

            // Validar limite diário de valor
            if (this.wallet.user.daily_withdrawal_limit) {
                const totalWithdrawnToday = parseFloat(this.wallet.user.withdrawal_amount_today || 0);
                if (totalWithdrawnToday + amount > this.wallet.user.daily_withdrawal_limit) {
                    this.errors.amount = `Você atingiu seu limite diário de saque (€${this.wallet.user.daily_withdrawal_limit}). Os limites serão resetados à meia-noite (horário de Lisboa).`;
                    return;
                }
            }

            // Validar limite diário de quantidade de saques
            if (this.wallet.user.daily_withdrawal_count_limit) {
                const withdrawalsToday = parseInt(this.wallet.user.withdrawal_count_today || 0);
                if (withdrawalsToday >= this.wallet.user.daily_withdrawal_count_limit) {
                    this.errors.amount = `Você atingiu o número máximo de saques diários (${this.wallet.user.daily_withdrawal_count_limit}). Os limites serão resetados à meia-noite (horário de Lisboa).`;
                    return;
                }
            }

            // 3. Validar IBAN (se for EUR)
            if (this.wallet.currency === 'EUR') {
                if (!this.withdraw.pix_key) {
                    this.errors.iban = 'Por favor, insira o IBAN.';
                    return;
                }

                if (!IBAN.isValid(this.withdraw.pix_key)) {
                    this.errors.iban = 'IBAN inválido. Por favor, verifique o número.';
                    return;
                }
            }

            const _this = this;
            _this.isLoading = true;

            // Format amount for submission
            _this.withdraw.amount = amount;
            
            // Garantir que o tipo seja definido
            if (!_this.withdraw.type) {
                _this.withdraw.type = "pix";
            }
            
            HttpApi.post("wallet/withdraw/request", _this.withdraw)
                .then((response) => {
                    _this.isLoading = false;
                    _this.withdraw = {
                        name: "",
                        pix_key: "",
                        pix_type: "",
                        amount: "",
                        type: "",
                        nif: "",
                        accept_terms: false,
                    };

                    _this.router.push({ name: "profileTransactions" });
                    _toast.success(response.data.message);
                })
                .catch((error) => {
                    this.withdraw.amount = (
                        this.withdraw.amount * 100
                    ).toString();
                    this.setAmountFormater();
                    this.setCpfFormater();
                    Object.entries(
                        JSON.parse(error.request.responseText)
                    ).forEach(([key, value]) => {
                        _toast.error(`${value}`);
                    });
                    _this.isLoading = false;
                });
        },

        validateAccountHolderName: function() {
            const name = this.withdraw.name.trim();
            
            // Check if name is empty
            if (!name) {
                this.errors.name = 'O nome é obrigatório';
                return false;
            }

            // Check if the name contains at least two parts (nome e sobrenome)
            const nameParts = name.split(' ');
            if (nameParts.length < 2) {
                this.errors.name = 'Por favor, insira seu nome completo (nome e sobrenome)';
                return false;
            }

            // Check if each part of the name has at least 2 characters
            if (nameParts.some(part => part.length < 2)) {
                this.errors.name = 'Cada parte do nome deve ter pelo menos 2 caracteres';
                return false;
            }

            // Check if name contains only letters and spaces (including accents and special characters)
            const nameRegex = /^[A-Za-záàâãéèêíïóôõöúçñÁÀÂÃÉÈÊÍÏÓÔÕÖÚÇÑ ]+$/;
            if (!nameRegex.test(name)) {
                this.errors.name = 'O nome deve conter apenas letras e espaços';
                return false;
            }

            // Clear any previous error
            this.errors.name = '';
            return true;
        },

        validateNIF: function() {
            const nif = this.withdraw.nif.trim();
            
            // Se o campo estiver vazio, não validamos (pode não ser obrigatório em todos os casos)
            if (!nif) {
                this.errors.nif = '';
                return true;
            }
            
            // Usar a biblioteca validate-nif para validar o NIF
            try {
                const isValid = validateNIF(nif);
                
                if (!isValid) {
                    this.errors.nif = 'O NIF fornecido não é válido';
                    return false;
                }
                
                // Limpar erro se a validação passar
                this.errors.nif = '';
                return true;
            } catch (error) {
                this.errors.nif = 'O NIF fornecido não é válido';
                return false;
            }
        },

        checkUserDeposits: function() {
            const _this = this;
            const _toast = useToast();
            
            _this.checkingDeposits = true;
            
            HttpApi.get("wallet/check-deposits")
                .then((response) => {
                    _this.hasDeposits = response.data.hasDeposits;
                    _this.checkingDeposits = false;
                })
                .catch((error) => {
                    console.error("Erro ao verificar depósitos:", error);
                    // Em caso de erro, permitimos o saque para não bloquear o usuário
                    _this.hasDeposits = true;
                    _this.checkingDeposits = false;
                });
        },
        
        checkKycStatus: function() {
            const _this = this;
            
            // Configuração individual tem prioridade sobre global
            if (_this.user && _this.user.kyc_required !== undefined) {
                // Se tem configuração individual, usa ela
                if (!_this.user.kyc_required) {
                    _this.checkingKyc = false;
                    _this.isKycApproved = true;
                    return;
                }
            } else if (!_this.setting || !_this.setting.kyc_required) {
                // Se não tem individual, usa global
                _this.checkingKyc = false;
                _this.isKycApproved = true;
                return;
            }
            
            _this.checkingKyc = true;
            
            HttpApi.get("profile/verification")
                .then((response) => {
                    if (response.data.status && response.data.data) {
                        const verificationData = response.data.data;
                        const verificationStatus = verificationData.verification_status;
                        
                        // Normalizar o status (remover espaços e converter para lowercase)
                        const normalizedStatus = verificationStatus ? verificationStatus.toString().trim().toLowerCase() : '';
                        
                        _this.isKycApproved = normalizedStatus === 'approved';
                        
                        // Pré-preencher NIF se disponível nos dados da verificação
                        if (verificationData.account && verificationData.account.document_number) {
                            const nifFromKyc = verificationData.account.document_number;
                            
                            // Pré-preencher o campo NIF se estiver vazio
                            if (!_this.withdraw.nif) {
                                _this.withdraw.nif = nifFromKyc;
                                _this.nifPrefilledFromKyc = true;
                            }
                        }
                    } else {
                        _this.isKycApproved = false;
                    }
                    _this.checkingKyc = false;
                })
                .catch((error) => {
                    console.error("Erro ao verificar status do KYC:", error);
                    // Em caso de erro, não permitimos o saque por segurança
                    _this.isKycApproved = false;
                    _this.checkingKyc = false;
                });
        },
        getUser: function() {
            const _this = this;
            
            HttpApi.get("profile/")
                .then((response) => {
                    if (response.data.status && response.data.user) {
                        _this.user = response.data.user;
                        // Verifica se pode executar KYC check agora
                        _this.tryCheckKyc();
                    }
                })
                .catch((error) => {
                    console.error("Erro ao carregar usuário:", error);
                });
        },
        tryCheckKyc: function() {
            // Só executa checkKycStatus se user e setting estiverem carregados
            if (this.user !== null && this.setting !== null) {
                this.checkKycStatus();
            }
        },

    },
    created() {
        this.getWallet();
        this.getSetting();
        this.getUser();
        this.checkUserDeposits();
    },

};
</script>

<style scoped></style>
