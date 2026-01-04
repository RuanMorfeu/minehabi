<template>
    <BaseLayout>
        <LoadingComponent :isLoading="isLoading">
            <div class="flex justify-center items-center h-full">
                <a v-if="setting" href="/" class="logo-animation"> <!-- Adicionando a classe items-center para centralizar verticalmente -->
                    <img :src="`/storage/` + setting.software_logo_black" alt="" class="h-10 mr-3 block dark:hidden" />
                    <img :src="`/storage/` + setting.software_logo_white" alt="" class="h-10 mr-3 hidden dark:block" />
                </a>
            </div>
        </LoadingComponent>

        <div v-if="!isLoading" class="md:w-5/6 2xl:w-5/6 mx-auto my-16 p-4">
            <HeaderComponent>
                <template #header>
                    {{ $t('List of') }} <span class=" bg-blue-100 text-blue-800 text-2xl font-semibold me-2 px-2.5 py-0.5 rounded dark:bg-blue-200 dark:text-blue-800 ms-2">{{ $t('Vip') }}</span>
                </template>

            </HeaderComponent>

            <div>
                <div class="level-actual relative dark:bg-blue-700 rounded-lg">
                    <div class="absolute top-0 left-0 px-3 py-2 bg-blue-700 rounded-tl-lg">
                        {{ $t('Current level') }}
                    </div>

                    <div class="pt-6">
                        <div class="flex justify-between items-center self-center w-full mt-3 p-4">
                            <div>
                                <img :src="`/assets/images/00.png`" alt="" class="h-16">
                            </div>
                            <div class="flex gap-2">
                                <p>Para o próximo nível</p>
                                <a href="" class="font-bold text-primary">Vip {{ nextLevel?.bet_level }}</a>
                                <p>Aposta válida ainda é necessária </p>
                                <a href="" class="font-bold text-primary">{{ nextLevel?.bet_required }}</a>
                            </div>
                            <div class="flex gap-2">
                                <button type="button" class="focus:outline-none text-white bg-purple-700 hover:bg-purple-800 focus:ring-4 focus:ring-purple-300 font-medium rounded-lg text-sm px-5 py-2.5 mb-2 dark:bg-purple-600 dark:hover:bg-purple-700 dark:focus:ring-purple-900">
                                    Regras
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="dark:bg-blue-700 rounded-lg mt-5 w-full p-4">
                    <div class="title-vip flex justify-between">
                        <p class="text-2xl">Lista de níveis VIP</p>
                        <p class="text-2xl"><i class="fa-sharp fa-light fa-coins mr-2"></i> {{ vipPoints }}</p>
                    </div>

                    <!-- Start Tabs -->
                    <div class="mb-4 border-b border-blue-600 dark:border-blue-500 mt-3 lg:mt-16">
                        <ul class="flex flex-wrap -mb-px text-sm font-medium text-center text-blue-600 dark:text-blue-500" id="tabs-vip" role="tablist">
                            <li class="me-2" role="presentation">
                                <button
                                    class="inline-block rounded-t-lg border-b-2 border-transparent p-4 hover:border-gray-300 hover:text-blue-600 dark:hover:text-blue-400"
                                    id="vipdefault-tab"
                                    type="button"
                                    role="tab"
                                    aria-controls="vipdefault-panel"
                                    aria-selected="false"
                                >
                                    Bônus de aumento de nível
                                </button>
                            </li>
                            <li class="me-2" role="presentation">
                                <button
                                    class="inline-block rounded-t-lg border-b-2 border-transparent p-4 hover:border-gray-300 hover:text-blue-600 dark:hover:text-blue-400"
                                    id="weeklybonus-tab"
                                    type="button"
                                    role="tab"
                                    aria-controls="weeklybonus-panel"
                                    aria-selected="false"
                                >
                                    Bônus Semanal
                                </button>
                            </li>
                            <li class="me-2" role="presentation">
                                <button
                                    class="inline-block rounded-t-lg border-b-2 border-transparent p-4 hover:border-gray-300 hover:text-blue-600 dark:hover:text-blue-400"
                                    id="monthlybonus-tab"
                                    type="button"
                                    role="tab"
                                    aria-controls="monthlybonus-panel"
                                    aria-selected="false"
                                >
                                    Bônus Mensal
                                </button>
                            </li>
                            <li class="me-2" role="presentation">
                                <button
                                    class="inline-block rounded-t-lg border-b-2 border-transparent p-4 hover:border-gray-300 hover:text-blue-600 dark:hover:text-blue-400"
                                    id="annualbonus-tab"
                                    type="button"
                                    role="tab"
                                    aria-controls="annualbonus-panel"
                                    aria-selected="false"
                                >
                                    Bônus Anual
                                </button>
                            </li>
                        </ul>
                    </div>
                    <div id="tabContentExample">
                        <div
                            class="hidden rounded-lg bg-gray-50 p-4 dark:bg-blue-700"
                            id="vipdefault-panel"
                            role="tabpanel"
                            aria-labelledby="vipdefault-tab">
                            <div class="flex w-full">
                                <TableVip :vips="nivelToday" :vipPoints="vipPoints" />
                            </div>
                        </div>
                        <div
                            class="hidden rounded-lg bg-gray-50 p-4 dark:bg-blue-700"
                            id="weeklybonus-panel"
                            role="tabpanel"
                            aria-labelledby="weeklybonus-tab">
                            <div class="flex w-full">
                                <TableVip :vips="nivelWeekly" :vipPoints="vipPoints" />
                            </div>
                        </div>
                        <div
                            class="hidden rounded-lg bg-gray-50 p-4 dark:bg-blue-700"
                            id="monthlybonus-panel"
                            role="tabpanel"
                            aria-labelledby="monthlybonus-tab">
                            <div class="flex w-full">
                                <TableVip :vips="nivelMonthly" :vipPoints="vipPoints" />
                            </div>
                        </div>
                        <div
                            class="hidden rounded-lg bg-gray-50 p-4 dark:bg-blue-700"
                            id="annualbonus-panel"
                            role="tabpanel"
                            aria-labelledby="annualbonus-tab">
                            <div class="flex w-full">
                                <TableVip :vips="nivelYearly" :vipPoints="vipPoints" />
                            </div>
                        </div>
                    </div>
                    <!-- End Tabs -->



                </div>
            </div>
        </div>
    </BaseLayout>
</template>


<script>

import BaseLayout from "@/Layouts/BaseLayout.vue";
import LoadingComponent from "@/Components/UI/LoadingComponent.vue";
import HeaderComponent from "@/Components/UI/HeaderComponent.vue";
import HttpApi from "@/Services/HttpApi.js";
import { initFlowbite, Tabs, Modal } from 'flowbite';
import TableVip from "@/Pages/Home/Vip/TableVip.vue";

export default {
    props: [],
    components: {TableVip, HeaderComponent, LoadingComponent, BaseLayout },
    data() {
        return {
            isLoading: true,
            nivelToday: null,
            nivelWeekly: null,
            nivelMonthly: null,
            nivelYearly: null,
            nextLevel: null,
            vipPoints: 0,
        }
    },
    setup(props) {


        return {};
    },
    computed: {

    },
    mounted() {

    },
    methods: {
        loadingVipTab: function() {
            const tabsElement = document.getElementById('tabs-vip');
            console.log(tabsElement);
            if(tabsElement) {
                const tabElements = [
                    {
                        id: 'vipdefault',
                        triggerEl: document.querySelector('#vipdefault-tab'),
                        targetEl: document.querySelector('#vipdefault-panel'),
                    },
                    {
                        id: 'weeklybonus',
                        triggerEl: document.querySelector('#weeklybonus-tab'),
                        targetEl: document.querySelector('#weeklybonus-panel'),
                    },
                    {
                        id: 'monthlybonus',
                        triggerEl: document.querySelector('#monthlybonus-tab'),
                        targetEl: document.querySelector('#monthlybonus-panel'),
                    },
                    {
                        id: 'annualbonus',
                        triggerEl: document.querySelector('#annualbonus-tab'),
                        targetEl: document.querySelector('#annualbonus-panel'),
                    },
                ];

                const options = {
                    defaultTabId: 'vipdefault',
                    activeClasses: 'text-blue-600 hover:text-blue-600 dark:text-blue-500 dark:hover:text-blue-400 border-blue-600 dark:border-blue-500',
                    inactiveClasses: 'text-blue-600 hover:text-blue-600 dark:text-blue-500 border-gray-100 hover:border-gray-300 dark:border-blue-500 dark:hover:text-blue-400',
                    onShow: () => {

                    },
                };

                const instanceOptions = {
                    id: 'vipdefault',
                    override: true
                };

                /*
                * tabElements: array of tab objects
                * options: optional
                * instanceOptions: optional
                */
                this.tabs = new Tabs(tabsElement, tabElements, options, instanceOptions);
            }
        },
        getVips: async function () {
            const _this = this;
            await HttpApi.get(`/profile/vip/`)
                .then(response => {

                    _this.vipPoints = response.data.vipPoints;
                    _this.nextLevel = response.data.nextLevel;
                    _this.nivelToday = response.data.nivelToday;
                    _this.nivelWeekly = response.data.nivelWeekly;
                    _this.nivelMonthly = response.data.nivelMonthly;
                    _this.nivelYearly = response.data.nivelYearly;

                    _this.isLoading = false;

                    _this.$nextTick(() => {
                        _this.loadingVipTab();
                    });
                })
                .catch(error => {
                    _this.isLoading = false;
                });
        },
    },
    async created() {
        await this.getVips();
    },
    watch: {

    },
};
</script>

<style scoped>

</style>
