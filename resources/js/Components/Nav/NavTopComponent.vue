<template>
    <!-- Backdrops para os modais -->
    <div id="modalAuthBackdrop" class="fixed inset-0 bg-black bg-opacity-70 backdrop-blur-sm hidden" style="position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; z-index: 9990;"></div>
    <div id="modalRegisterBackdrop" class="fixed inset-0 bg-black bg-opacity-70 backdrop-blur-sm hidden" style="position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; z-index: 9990;"></div>
    <div id="modalProfileBackdrop" class="fixed inset-0 bg-black bg-opacity-70 backdrop-blur-sm hidden" style="position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; z-index: 9990;"></div>
    
    <nav class="fixed top-0 z-50 w-full navtop-color custom-box-shadow border-solid border-b border-1 border-gray-200 dark:border-gray-700">
        <div class="px-3 lg:px-5 lg:pl-3 nav-menu">
            <div class="flex items-center justify-between">
                <div class="flex items-center justify-start py-3">
                    <button @click.prevent="toggleMenu" type="button" class="inline-flex items-center p-2 text-sm text-sm text-blue-600 rounded-lg sm:hidden hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-gray-200 dark:text-blue-500 dark:hover:bg-gray-700 dark:focus:ring-gray-600">
                        <span class="sr-only">Open sidebar</span>
                        <svg class="w-6 h-6" aria-hidden="true" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                            <path clip-rule="evenodd" fill-rule="evenodd" d="M2 4.75A.75.75 0 012.75 4h14.5a.75.75 0 010 1.5H2.75A.75.75 0 012 4.75zm0 10.5a.75.75 0 01.75-.75h7.5a.75.75 0 010 1.5h-7.5a.75.75 0 01-.75-.75zM2 10a.75.75 0 01.75-.75h14.5a.75.75 0 010 1.5H2.75A.75.75 0 012 10z"></path>
                        </svg>
                    </button>

                    <div class="items-center justify-center text-gray-500 hidden md:flex me-4">
                        <RouterLink :to="{ name: 'home' }" active-class="border-b-2 border-primary text-white"  class="flex gap-2 flex-row w-[50%] cursor-pointer items-center px-2 py-3 justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" version="1.1"
                                xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:svgjs="http://svgjs.com/svgjs"
                                width="16px" height="16px" x="0" y="0" viewBox="0 0 512 512" xml:space="preserve">
                                <g>
                                    <path
                                        d="M479.001 34.656 285.933.613c-21.715-3.829-42.423 10.671-46.252 32.386l-1.827 10.363c27.921 3.44 51.51 23.554 58.937 51.269l11.799 44.035a35.815 35.815 0 0 1 20.537-2.414c13.677 2.412 24.17 12.255 28.079 24.642 7.91-10.303 21.137-15.964 34.814-13.552 19.575 3.452 32.646 22.119 29.194 41.694-7.5 42.534-53.143 76.721-74.804 90.782l33.071 123.423a69.601 69.601 0 0 1 2.349 19.79l27.824 4.906c21.715 3.829 42.423-10.671 46.252-32.386l55.48-314.641c3.83-21.717-10.67-42.425-32.385-46.254z"
                                        fill="currentColor" data-original="#000000"></path>
                                    <path
                                        d="M267.867 102.382c-4.78-17.838-20.911-29.603-38.54-29.602-3.42 0-6.898.443-10.359 1.37L29.602 124.89c-21.299 5.707-33.939 27.6-28.232 48.899l82.691 308.609c4.78 17.838 20.911 29.602 38.54 29.602 3.42 0 6.898-.443 10.358-1.37l189.366-50.741c21.299-5.707 33.939-27.6 28.232-48.899zM120.51 313.333a10.603 10.603 0 0 1-3.042-11.353l28.956-85.738c2.422-7.172 11.364-9.568 17.048-4.568l67.946 59.774a10.603 10.603 0 0 1 3.042 11.353l-28.956 85.738c-2.422 7.172-11.364 9.569-17.048 4.568z"
                                        fill="currentColor" data-original="#000000"></path>
                                </g>
                            </svg>
                            <p class="text-[14.4px] font-bold">JOGOS</p>
                        </RouterLink>
                    </div>
                    
                    <a v-if="setting" href="/" class="flex md:mr-24 md:ml-3">
                        <div class="hidden sm:block">
                            <img :src="`/storage/`+setting.software_logo_black" alt="" class="h-8 mr-3 mr-3 block dark:hidden" /> <!-- Altere o tamanho aqui -->
                            <img :src="`/storage/`+setting.software_logo_white" alt=""  class="h-8 mr-3 mr-3 hidden dark:block" /> <!-- Altere o tamanho aqui -->
                        </div>
                        <div class="block sm:hidden">
                            <!-- Usando o logo regular em dispositivos móveis -->
                            <img :src="`/storage/`+setting.software_logo_black" alt="" class="w-auto max-h-[25px] mr-3 block dark:hidden" />
                            <img :src="`/storage/`+setting.software_logo_white" alt=""  class="w-auto max-h-[25px] mr-3 hidden dark:block" />
                        </div>
                    </a>
                </div>
                <div class="hidden md:block">

                </div>
                <div v-if="!simple" class="flex items-center py-3">

                    <div v-if="!isAuthenticated" class="flex ml-5">
                        <button @click.prevent="loginToggle" class="px-5 py-2 text-sm font-bold text-white bg-blue-600 rounded-full hover:bg-blue-700 transition-colors shadow-sm">
                            Entrar
                        </button>
                        <button @click.prevent="registerToggle" class="px-5 py-2 ml-3 mr-3 text-sm font-bold text-gray-700 bg-gray-100 rounded-full hover:bg-gray-200 transition-colors">
                            Registar
                        </button>
                    </div>


                    <div v-if="isAuthenticated" class="flex items-center">
                        <WalletBalance />
                        <MakeDeposit :showMobile="true" :title="$t('Deposit')" />
                        <!-- <LanguageSelector />
                        <DropdownDarkLight/> -->

                        <div class="flex items-center">
                            <div>
                                <button type="button" class="flex text-sm bg-gray-800 rounded-full focus:ring-4 focus:ring-gray-300 dark:focus:ring-gray-600" aria-expanded="false" data-dropdown-toggle="dropdown-user">
                                    <span class="sr-only">Open user menu</span>
                                    <img class="w-8 h-8 rounded-full" :src="userData?.avatar ? '/storage/'+userData.avatar : `/assets/images/profile.jpg`" alt="">
                                </button>
                            </div>
                            <div class="z-50 hidden my-4 text-base list-none bg-white divide-y divide-gray-100 rounded shadow dark:bg-gray-700 dark:divide-gray-600" id="dropdown-user">
                                <div class="px-4 py-3" role="none">
                                    <p class="text-sm text-gray-900 dark:text-white" role="none">
                                        {{ userData?.name }}
                                    </p>
                                    <p class="text-sm font-medium text-gray-900 truncate dark:text-gray-300" role="none">
                                        {{ userData?.email }}
                                    </p>
                                </div>

                                
                                <ul class="py-1" role="none">
                                    <li>
                                        <RouterLink :to="{ name: 'home' }" active-class="link-active" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-600 dark:hover:text-white">
                                            <span class="w-8 h-8 mr-3">
                                                <i class="fa-duotone fa-house"></i>
                                            </span>
                                            {{ $t('Dashboard') }}
                                        </RouterLink>
                                    </li>
                                    <li>
                                        <RouterLink :to="{ name: 'profileAffiliate' }" active-class="profile-menu-active" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-600 dark:hover:text-white">
                                            <span class="w-8 h-8 mr-3">
                                                <i class="fa-duotone fa-people-group"></i>
                                            </span>
                                            {{ $t('Affiliate') }}
                                        </RouterLink>
                                    </li>

                                    <li>
                                        <RouterLink :to="{ name: 'profileWithdraw' }" active-class="profile-menu-active" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-600 dark:hover:text-white">
                                            <span class="w-8 h-8 mr-3">
                                                <i class="fa-light fa-money-bill-transfer"></i>
                                            </span>
                                            {{ $t('Withdraw') }}
                                        </RouterLink>
                                    </li>
                                    <li>
                                        <RouterLink :to="{ name: 'profileWallet' }" active-class="profile-menu-active" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-600 dark:hover:text-white">
                                            <span class="w-8 h-8 mr-3">
                                              <i class="fa-duotone fa-wallet"></i>
                                            </span>
                                            {{ $t('My Wallet') }}
                                        </RouterLink>
                                    </li>
                                    <li>
                                        <a href="#" @click.prevent="profileToggle" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-600 dark:hover:text-white" role="menuitem">
                                            <span class="w-8 h-8 mr-3">
                                               <i class="fa-regular fa-id-card-clip"></i>
                                            </span>
                                            {{ $t('My Profile') }}
                                        </a>
                                    </li>
                                    <li>
                                        <a @click.prevent="logoutAccount" href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-600 dark:hover:text-white" role="menuitem">
                                             <span class="w-8 h-8 mr-3">
                                               <i class="fa-duotone fa-right-from-bracket"></i>
                                            </span>
                                            {{ $t('Sign out') }}
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <transition name="fade">
            <div v-if="showSearchMenu" class="fixed top-0 left-0 right-0 bottom-0 flex items-center justify-center ">
                <div @click="toggleSearch" class="absolute inset-0 carousel_banners opacity-50 cursor-pointer"></div>

                <!-- Start searchbar action -->
                <div class="search-menu p-4 sm:ml-64">

                    <div class="mb-5 w-full">
                        <div class="md:w-5/6 2xl:w-5/6 mx-auto">
                            <div class="flex flex-col">
                                <div class="relative w-full">
                                    <input type="search"
                                           v-model.lazy="searchTerm"
                                           class="block dark:focus:border-blue-500 p-2.5 w-full z-20 text-sm text-gray-900 input-color-primary rounded-e-lg border-none focus:outline-none dark:border-s-gray-800  dark:border-gray-800 dark:placeholder-gray-400 dark:text-white "
                                           placeholder="Nome do jogo | Provedor"
                                           required>

                                    <button v-if="searchTerm.length > 0" @click.prevent="clearData" type="button" class="absolute top-0 end-0 h-full p-2.5 text-sm font-medium text-white rounded-e-lg dark:bg-[#1C1E22] ">
                                        <span class="">Recusar</span>
                                    </button>
                                </div>
                                <div class="text-center mt-4">
                                    <p>A pesquisa requer pelo menos 3 caracteres</p>
                                </div>
                            </div>

                            <div v-if="!isLoadingSearch" class="mt-8 grid grid-cols-3 md:grid-cols-6 gap-4 py-5">
                                <CassinoGameCard
                                    v-if="games"
                                    v-for="(game, index) in games?.data"
                                    :index="index"
                                    :title="game.game_name"
                                    :cover="game.cover"
                                    :gamecode="game.game_code"
                                    :type="game.distribution"
                                    :game="game"
                                />
                            </div>
                            <div v-else class="relative items-center block max-w-sm p-6 bg-white border border-gray-100 rounded-lg shadow-md dark:bg-gray-800 dark:border-gray-800 dark:hover:bg-gray-700">
                                <h5 class="mb-2 text-2xl font-bold tracking-tight text-gray-900 dark:text-white opacity-20">Noteworthy technology acquisitions 2021</h5>
                                <p class="font-normal text-gray-700 dark:text-gray-400 opacity-20">Here are the biggest enterprise technology acquisitions of 2021 so far, in reverse chronological order.</p>
                                <div role="status" class="absolute -translate-x-1/2 -translate-y-1/2 top-2/4 left-1/2">
                                    <svg aria-hidden="true" class="w-8 h-8 text-gray-200 animate-spin dark:text-gray-600 fill-blue-600" viewBox="0 0 100 101" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M100 50.5908C100 78.2051 77.6142 100.591 50 100.591C22.3858 100.591 0 78.2051 0 50.5908C0 22.9766 22.3858 0.59082 50 0.59082C77.6142 0.59082 100 22.9766 100 50.5908ZM9.08144 50.5908C9.08144 73.1895 27.4013 91.5094 50 91.5094C72.5987 91.5094 90.9186 73.1895 90.9186 50.5908C90.9186 27.9921 72.5987 9.67226 50 9.67226C27.4013 9.67226 9.08144 27.9921 9.08144 50.5908Z" fill="currentColor"/><path d="M93.9676 39.0409C96.393 38.4038 97.8624 35.9116 97.0079 33.5539C95.2932 28.8227 92.871 24.3692 89.8167 20.348C85.8452 15.1192 80.8826 10.7238 75.2124 7.41289C69.5422 4.10194 63.2754 1.94025 56.7698 1.05124C51.7666 0.367541 46.6976 0.446843 41.7345 1.27873C39.2613 1.69328 37.813 4.19778 38.4501 6.62326C39.0873 9.04874 41.5694 10.4717 44.0505 10.1071C47.8511 9.54855 51.7191 9.52689 55.5402 10.0491C60.8642 10.7766 65.9928 12.5457 70.6331 15.2552C75.2735 17.9648 79.3347 21.5619 82.5849 25.841C84.9175 28.9121 86.7997 32.2913 88.1811 35.8758C89.083 38.2158 91.5421 39.6781 93.9676 39.0409Z" fill="currentFill"/></svg>
                                    <span class="sr-only">Loading...</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- End searchbar action -->


            </div>
        </transition>

    </nav>

    <!-- Modal de login -->
    
    <div
        id="modalElAuth"
        tabindex="-1"
        aria-hidden="true"
        class="fixed top-0 left-0 right-0 z-[9999] hidden overflow-x-hidden overflow-y-auto md:inset-0 h-screen md:h-[calc(100%-1rem)] max-h-full my-8"
    >
        <div
            class="relative w-[95%] md:w-[500px] max-w-lg mx-auto max-h-full snake-auth-modal shadow-lg"
        >
            <div class="flex md:justify-between">
                <div class="w-full relative">
                    <div
                        v-if="isLoadingLogin"
                        class="absolute top-0 left-0 right-0 bottom-0 bg-[#00000073] backdrop-blur-[6px] z-[999] p-5"
                    >
                        <div
                            role="status"
                            class="absolute -translate-x-1/2 -translate-y-1/2 top-2/4 left-1/2"
                        >
                            <svg
                                aria-hidden="true"
                                class="w-10 h-10 mr-2 text-gray-200 animate-spin dark:text-gray-600 fill-[#796be1]"
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
                            <span class="sr-only">Loading...</span>
                        </div>
                    </div>

                    <form
                        @submit.prevent="loginSubmit"
                        method="post"
                        action=""
                        class=""
                    >
                        <div
                            class="flex justify-between items-center p-5"
                        >
                            <div>
                                <h5 class="font-bold snake-auth-title">
                                    Seja bem-vindo novamente!
                                </h5>
                                <p class="text-sm snake-auth-subtitle">
                                    Preencha suas credenciais abaixo
                                </p>
                            </div>
                            <a @click.prevent="loginToggle" href="" class="w-8 h-8 flex items-center justify-center bg-gray-700 hover:bg-gray-600 rounded-full transition-all duration-200">
                                <i class="fa-solid fa-xmark text-white text-lg"></i>
                            </a>
                        </div>
                        
                        <!-- Banner de login -->
                        <div v-if="loginBanner" class="mb-4 px-5 rounded-lg overflow-hidden shadow-lg">
                            <div>
                                <img :src="loginBanner.image.startsWith('http') ? loginBanner.image : '/storage/' + loginBanner.image" :alt="loginBanner.description" class="w-full h-auto rounded-lg">
                            </div>
                        </div>

                        <div class="p-5">
                            <div class="relative mb-3">
                                <div
                                    class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none"
                                >
                                    <i
                                        class="fa-regular fa-envelope text-blue-600" style="z-index: 1;"
                                    ></i>
                                </div>
                                <input
                                    required
                                    type="text"
                                    v-model="loginForm.email"
                                    name="email"
                                    class="snake-auth-input"
                                    :placeholder="$t('Enter email')"
                                />
                            </div>

                            <div class="relative mb-3">
                                <div
                                    class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none"
                                >
                                    <i
                                        class="fa-solid fa-key text-blue-600" style="z-index: 1;"
                                    ></i>
                                </div>
                                <input
                                    required
                                    :type="typeInputPassword"
                                    v-model="loginForm.password"
                                    name="password"
                                    class="snake-auth-input pr-[40px]"
                                    :placeholder="$t('Type the password')"
                                />
                                <button
                                    type="button"
                                    @click.prevent="togglePassword"
                                    class="absolute inset-y-0 right-0 flex items-center pr-3.5"
                                >
                                    <i
                                        v-if="typeInputPassword === 'password'"
                                        class="fa-regular fa-eye"
                                    ></i>
                                    <i
                                        v-if="typeInputPassword === 'text'"
                                        class="fa-sharp fa-regular fa-eye-slash"
                                    ></i>
                                </button>
                            </div>
                            <a
                                href="/forgot-password"
                                class="text-white text-sm"
                                >{{ $t("Forgot password") }}</a
                            >

                            <div class="mt-6 w-full">
                                <button
                                    type="submit"
                                    class="snake-auth-button"
                                >
                                    {{ $t("Log in") }}
                                </button>
                            </div>
                            <div class="snake-auth-links">
                                <a href="" @click.prevent="hideLoginShowRegisterToggle"><strong>Criar conta</strong></a>
                                <a href="/forgot-password">{{ $t("Forgot password") }}</a>
                            </div>
                        </div>
                    </form>              </div>
            </div>
        </div>
    </div>

    <!-- Modal de registro -->
    
    <div
        id="modalElRegister"
        tabindex="-1"
        aria-hidden="true"
        class="fixed inset-0 flex items-center justify-center z-[9999] hidden overflow-y-auto overflow-x-hidden max-h-full"
    >
        <div
            class="relative w-[95%] md:w-[500px] max-w-lg mx-auto max-h-full snake-auth-modal shadow-lg overflow-auto tallHeight:h-[calc(100vh-140px)]"
        >
            <div
                v-if="isLoadingRegister"
                class="absolute top-0 left-0 right-0 bottom-0 bg-[#00000073] backdrop-blur-[6px] z-[999]"
            >
                <div
                    role="status"
                    class="absolute -translate-x-1/2 -translate-y-1/2 top-2/4 left-1/2"
                >
                    <svg
                        aria-hidden="true"
                        class="w-10 h-10 mr-2 text-gray-200 animate-spin dark:text-gray-600 fill-blue-600"
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
                    <span class="sr-only">Loading...</span>
                </div>
            </div>

            <div class="flex md:justify-between h-full">
                <div class="relative w-full">
                    <form
                        @submit.prevent="registerSubmit"
                        method="post"
                        action=""
                        class=""
                    >
                        <div
                            class="flex justify-between items-center p-5"
                        >
                            <div>
                                <h5 class="font-bold snake-auth-title">
                                    Crie sua conta agora!
                                </h5>
                                <p class="text-sm snake-auth-subtitle">
                                    Preencha suas informações abaixo
                                </p>
                            </div>
                            <a @click.prevent="registerToggle" href="" class="w-8 h-8 flex items-center justify-center bg-gray-700 hover:bg-gray-600 rounded-full transition-all duration-200">
                                <i class="fa-solid fa-xmark text-white text-lg"></i>
                            </a>
                        </div>
                        
                        <!-- Banner de registro -->
                        <div v-if="registerBanner" class="mb-4 px-5 rounded-lg overflow-hidden shadow-lg">
                            <div>
                                <img :src="registerBanner.image.startsWith('http') ? registerBanner.image : '/storage/' + registerBanner.image" :alt="registerBanner.description" class="w-full h-auto rounded-lg">
                            </div>
                        </div>

                        <div class="p-5">
                            <div class="relative mb-3">
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
                                    class="snake-auth-input"
                                    :placeholder="$t('Enter name')"
                                    required
                                />
                            </div>

                            <div class="relative mb-3">
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
                                    class="snake-auth-input"
                                    :placeholder="$t('Enter email')"
                                    required
                                />
                            </div>


                            <div class="relative mb-3">
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
                                    class="snake-auth-input pr-[40px]"
                                    :placeholder="$t('Type the password')"
                                    required
                                />
                                <button
                                    type="button"
                                    @click.prevent="togglePassword"
                                    class="absolute inset-y-0 right-0 flex items-center pr-3.5"
                                >
                                    <i
                                        v-if="typeInputPassword === 'password'"
                                        class="fa-regular fa-eye"
                                    ></i>
                                    <i
                                        v-if="typeInputPassword === 'text'"
                                        class="fa-sharp fa-regular fa-eye-slash"
                                    ></i>
                                </button>
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
                                            'snake-auth-input ps-16': !phoneError,
                                            'snake-auth-input ps-16 border-red-500 focus:ring-red-500 focus:border-red-500': phoneError
                                        }"
                                        placeholder="Telemóvel"
                                        maxlength="9"
                                        pattern="\d*"
                                        :placeholder="$t('Enter your phone')"
                                        required
                                    />
                                </div>
                                <div v-if="phoneError" class="mt-1 text-sm text-red-400">
                                    {{ phoneError }}
                                </div>
                            </div>

                            <div class="mb-3 mt-5">
                                <button
                                    @click.prevent="isReferral = !isReferral"
                                    type="button"
                                    class="flex justify-between w-full"
                                >
                                    <p>{{ $t("Enter Referral/Promo Code") }}</p>
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
                                    v-if="isReferral"
                                    class="relative mb-3 mt-1"
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
                                        name="name"
                                        v-model="registerForm.reference_code"
                                        class="snake-auth-input"
                                        :placeholder="$t('Code')"
                                    />
                                </div>
                            </div>

                            <hr class="mb-3 mt-2 border-[#323539]" />

                            <div class="mb-3 mt-6">
                                <div class="flex">
                                    <input
                                        id="link-checkbox"
                                        v-model="registerForm.term_a"
                                        name="term_a"
                                        required
                                        type="checkbox"
                                        value=""
                                        checked
                                        class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600"
                                    />
                                    <label
                                        for="link-checkbox"
                                        class="ml-2 text-sm font-medium text-gray-900 dark:text-gray-300"
                                        >{{
                                            $t(
                                                "I agree to the User Agreement & confirm I am at least 18 years old"
                                            )
                                        }}</label
                                    >
                                </div>
                            </div>

                            <div class="mb-3">
                                <div class="flex items-center">
                                </div>
                            </div>

                            <div class="mt-5 w-full">
                                <button
                                    type="submit"
                                    class="snake-auth-button"
                                >
                                    {{ $t("Register") }}
                                </button>
                                
                                <div class="snake-auth-links">
                                    <a href="" @click.prevent="hideRegisterShowLoginToggle" class="font-medium"><strong>{{ $t('Faça login') }}</strong></a>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de perfil -->
    
    <div id="modalProfileEl" tabindex="-1" aria-hidden="true" class="fixed top-0 left-0 right-0 z-[9999] hidden overflow-x-hidden overflow-y-auto md:inset-0 h-screen md:h-[calc(100%-1rem)] max-h-full">
        <div class="relative w-[95%] md:w-[500px] max-w-lg mx-auto max-h-full bg-white dark:bg-gray-900 rounded-lg shadow-lg">
            <div v-if="!isLoadingProfile" class="flex flex-col">

                <!-- PROFILE HEADER -->
                <div class="flex justify-between w-full p-4">
                    <h1 class="text-2xl font-bold">{{ $t('User data') }}</h1>
                    <button @click.prevent="profileToggle" type="button" class="w-8 h-8 flex items-center justify-center bg-gray-700 hover:bg-gray-600 rounded-full transition-all duration-200">
                        <i class="fa-solid fa-xmark text-white text-lg"></i>
                    </button>
                </div>

                <!-- PROFILE BODY -->
                <div v-if="profileUser != null" class="flex flex-col w-full p-4">

                    <!-- PROFILE INFO -->
                    <div class="flex items-center self-center justify-between w-full">
                        <button @click.prevent="like(profileUser.id)" type="button" class="heart">
                            <i class="fa-solid fa-heart"></i>
                            <span class="ml-2">{{ profileUser.totalLikes }}</span>
                        </button>
                        <div class="text-center flex flex-col justify-center self-center items-center">
                            <div class="relative">
                                <img class="w-24 h-246 p-1 rounded-full ring-2 ring-gray-300 dark:ring-gray-500"
                                     :src="avatarUrl"
                                     alt="">
                                <input ref="fileInput" type="file" style="display: none" @change="handleFileChange">
                                <button @click="openFileInput" type="button" class="absolute bottom-0 right-0 text-3xl">
                                    <i class="fa-duotone fa-image"></i>
                                </button>
                            </div>
                            <div class="relative">
                                <input @change.prevent="updateName" v-model="profileName" type="text" :readonly="!readonly" class="mt-4 appearance-none border border-gray-300 rounded-md p-2 bg-transparent border-none text-center" :placeholder="profileName" >
                            </div>
                        </div>
                        <div class="">
                            <button @click.prevent="readonly = !readonly" type="button" class="bg-gray-200 hover:bg-gray-400 dark:bg-gray-600 hover:dark:bg-gray-700 w-10 h-10  rounded">
                                <i v-if="!readonly" class="fa-sharp fa-light fa-pen"></i>
                                <i v-if="readonly" class="fa-solid fa-xmark"></i>
                            </button>
                        </div>
                    </div>

                    <div class="mt-3 shadow flex flex-col bg-gray-100 dark:bg-gray-900 rounded-lg">
                        <div class="flex justify-between px-4 pt-4">
                            <h1><span class="mr-2"><i class="fa-solid fa-chart-mixed"></i></span> {{ $t('Statistics') }}</h1>
                        </div>
                        <div class="p-4">
                            <div class="grid grid-cols-3 gap-4">
                                <div class="bg-gray-200 dark:bg-gray-700 text-center p-4">
                                    <p class="text-[12px]">{{ $t('Total winnings') }}</p>
                                    <p class="text-2xl font-bold">
                                        {{ totalEarnings }}
                                    </p>
                                </div>
                                <div class="bg-gray-200 dark:bg-gray-700 text-center p-4">
                                    <p class="text-[12px]">{{ $t('Total bets') }}</p>
                                    <p class="text-2xl font-bold">{{ totalBets }}</p>
                                </div>
                                <div class="bg-gray-200 dark:bg-gray-700 text-center p-4">
                                    <p class="text-[12px]">{{ $t('Total bet') }}</p>
                                    <p class="text-2xl font-bold">{{ sumBets }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="py-3 text-center">
                        <p>ingressou em {{ profileUser.dateHumanReadable }}</p>
                    </div>

                </div>
            </div>
            <div v-if="isLoadingProfile" class="flex flex-col w-full h-full">
                <div role="status" class="absolute -translate-x-1/2 -translate-y-1/2 top-2/4 left-1/2">
                    <svg aria-hidden="true" class="w-8 h-8 text-gray-200 animate-spin dark:text-gray-600 fill-blue-600" viewBox="0 0 100 101" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M100 50.5908C100 78.2051 77.6142 100.591 50 100.591C22.3858 100.591 0 78.2051 0 50.5908C0 22.9766 22.3858 0.59082 50 0.59082C77.6142 0.59082 100 22.9766 100 50.5908ZM9.08144 50.5908C9.08144 73.1895 27.4013 91.5094 50 91.5094C72.5987 91.5094 90.9186 73.1895 90.9186 50.5908C90.9186 27.9921 72.5987 9.67226 50 9.67226C27.4013 9.67226 9.08144 27.9921 9.08144 50.5908Z" fill="currentColor"/><path d="M93.9676 39.0409C96.393 38.4038 97.8624 35.9116 97.0079 33.5539C95.2932 28.8227 92.871 24.3692 89.8167 20.348C85.8452 15.1192 80.8826 10.7238 75.2124 7.41289C69.5422 4.10194 63.2754 1.94025 56.7698 1.05124C51.7666 0.367541 46.6976 0.446843 41.7345 1.27873C39.2613 1.69328 37.813 4.19778 38.4501 6.62326C39.0873 9.04874 41.5694 10.4717 44.0505 10.1071C47.8511 9.54855 51.7191 9.52689 55.5402 10.0491C60.8642 10.7766 65.9928 12.5457 70.6331 15.2552C75.2735 17.9648 79.3347 21.5619 82.5849 25.841C84.9175 28.9121 86.7997 32.2913 88.1811 35.8758C89.083 38.2158 91.5421 39.6781 93.9676 39.0409Z" fill="currentFill"/></svg>
                    <span class="sr-only">{{ $t('Loading') }}...</span>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { RouterLink, useRoute } from "vue-router";
import { sidebarStore } from "@/Stores/SideBarStore.js";
import { Modal } from 'flowbite';
import { useAuthStore } from "@/Stores/Auth.js";
import { useToast } from "vue-toastification";
import { useRouter } from 'vue-router';

import DropdownDarkLight from "@/Components/UI/DropdownDarkLight.vue";
import LanguageSelector from "@/Components/UI/LanguageSelector.vue";
import WalletBalance from "@/Components/UI/WalletBalance.vue";
import HttpApi from "@/Services/HttpApi.js";
import MakeDeposit from "@/Components/UI/MakeDeposit.vue";
import {useSettingStore} from "@/Stores/SettingStore.js";
import {searchGameStore} from "@/Stores/SearchGameStore.js";
import CassinoGameCard from "@/Pages/Cassino/Components/CassinoGameCard.vue";

export default {
    props: ['simple'],
    components: {CassinoGameCard, MakeDeposit, WalletBalance, LanguageSelector, DropdownDarkLight, RouterLink },
    data() {
        return {
            savedCode: '',
            isLoadingLogin: false,
            isLoadingRegister: false,
            isLoadingProfile: false,
            isReferral: false,
            showReferralBar: false,
            modalAuth: null,
            modalRegister: null,
            modalProfile: null,
            typeInputPassword: 'password',
            searchTerm: '',
            searchData: [],
            searchLoading: false,
            simple: false,
            loginBanner: null,
            registerBanner: null,
            loginForm: {
                email: '',
                password: '',
                remember: true,
            },
            registerForm: {
                name: '',
                email: '',
                password: '',
                phone: '',
                reference_code: '',
                term_a: true,
            },
            phoneError: '',
            profileForm: {
                name: '',
                avatar: null,
            },
            fileInputRef: null,
            selectedFile: null,
        }
    },
    setup() {
        const router = useRouter();

        return {
            router,
        };
    },
    computed: {
        searchGameDataStore() {
            return searchGameStore();
        },
        searchGameMenu() {
            const search = searchGameStore();
            return search.getSearchGameStatus;
        },
        sidebarMenuStore() {
            return sidebarStore();
        },
        isAuthenticated() {
            const authStore = useAuthStore();
            return authStore.isAuth;
        },
        userData() {
            const authStore = useAuthStore();
            return authStore.user;
        },
        setting() {
            const authStore = useSettingStore();
            return authStore.setting;
        }
    },
    unmounted() {

    },
    mounted() {
        const _this = this;
        const route = useRoute();

        // Definir simple como true para as rotas de terms, login, register e recuperação de senha
        if(route.name === 'terms' || route.path === '/login' || route.path === '/register' || route.path === '/password/reset' || route.path === '/forgot-password') {
            _this.simple = true;
        }

        // Initialize modals
        _this.modalAuth = new Modal(document.getElementById('modalElAuth'), {
            placement: 'center',
            backdrop: 'static',
            closable: true,
        });

        _this.modalRegister = new Modal(document.getElementById('modalElRegister'), {
            placement: 'center',
            backdrop: 'static',
            closable: true,
        });

        _this.modalProfile = new Modal(document.getElementById('modalProfileEl'), {
            placement: 'center',
            backdrop: 'static',
            closable: true,
        });

        // Buscar os banners de login e registro
        this.fetchLoginBanner();
        this.fetchRegisterBanner();

        const urlParams = new URLSearchParams(window.location.search);
        const code = urlParams.get('code');

        if (code) {
            _this.registerForm.reference_code = code;
            _this.isReferral = true;
        }

        const savedCode = localStorage.getItem('referral_code');
        if (savedCode) {
            _this.registerForm.reference_code = savedCode;
            _this.isReferral = true;
        }

        // Abrir modal de registro automaticamente se houver código de afiliado
        if ((code || savedCode) && !_this.isAuthenticated) {
            // Pequeno delay para garantir que o modal foi inicializado
            setTimeout(() => {
                _this.registerToggle();
            }, 500);
        }

        // Add event listener for search input
        document.getElementById('search-navbar')?.addEventListener('keyup', function(event) {
            if (event.key === 'Enter') {
                _this.getSearch();
            }
        });

        // Add event listener for search button
        document.getElementById('search-button')?.addEventListener('click', function() {
            _this.getSearch();
        });

        // Add event listener for closing search results
        document.addEventListener('click', function(event) {
            const searchResults = document.getElementById('search-results');
            const searchInput = document.getElementById('search-navbar');
            const searchButton = document.getElementById('search-button');

            if (searchResults && !searchResults.contains(event.target) && event.target !== searchInput && event.target !== searchButton) {
                searchResults.classList.add('hidden');
            }
        });

        // Check if user is authenticated
        if (this.isAuthenticated) {
            this.getProfile();
        }
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
        toggleSearch: function() {
            this.searchGameDataStore.setSearchGameToogle();
        },
        redirectSocialTo: function() {
            return '/auth/redirect/google'
        },
        like: async function(id) {
            const _this = this;
            const _toast = useToast();
            await HttpApi.post('/profile/like/' + id, {})
                .then(response => {

                    _this.getProfile();
                    _toast.success(_this.$t(response.data.message));
                })
                .catch(error => {
                    Object.entries(JSON.parse(error.request.responseText)).forEach(([key, value]) => {
                        _toast.error(`${value}`);
                    });
                });
        },
        updateName: async function(event) {
            const _this = this;
            _this.isLoadingProfile = true;

            await HttpApi.post('/profile/updateName', { name: _this.profileName })
                .then(response => {
                    _this.isLoadingProfile = false;
                })
                .catch(error => {
                    const _this = this;
                    Object.entries(JSON.parse(error.request.responseText)).forEach(([key, value]) => {

                    });
                    _this.isLoadingProfile = false;
                });
        },
        togglePassword: function() {
            if(this.typeInputPassword === 'password') {
                this.typeInputPassword = 'text';
            }else{
                this.typeInputPassword = 'password';
            }
        },
        loginSubmit: function(event) {
            const _this = this;
            const _toast = useToast();
            _this.isLoadingLogin = true;
            const authStore = useAuthStore();

            HttpApi.post('auth/login', _this.loginForm)
                .then(async response => {
                    await new Promise(r => {
                        setTimeout(() => {
                            authStore.setToken(response.data.access_token);
                            authStore.setUser(response.data.user);
                            authStore.setIsAuth(true);

                            _this.loginForm = {
                                email: '',
                                password: '',
                            }

                            // Remover o backdrop e a classe modal-open
                            const backdrop = document.getElementById('modalAuthBackdrop');
                            backdrop.classList.add('hidden');
                            document.body.classList.remove('modal-open');
                            
                            // Fechar o modal
                            _this.modalAuth.toggle();
                            _toast.success(_this.$t('You have been authenticated, welcome!'));

                            _this.isLoadingLogin = false;
                        }, 1000)
                    });
                })
                .catch(error => {
                    const _this = this;
                    Object.entries(JSON.parse(error.request.responseText)).forEach(([key, value]) => {
                        _toast.error(`${value}`);
                    });
                    _this.isLoadingLogin = false;
                });
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
        registerSubmit: async function(event) {
            const _this = this;
            const _toast = useToast();
            
            // Validar o telefone antes de enviar
            if (!_this.validatePhone()) {
                _toast.error(_this.phoneError);
                return;
            }
            
            _this.isLoadingRegister = true;

            // Criar uma cópia do formulário para não modificar o original diretamente
            const formData = { ..._this.registerForm };
            
            // Adicionar o prefixo +351 ao telefone se não estiver presente
            if (formData.phone && !formData.phone.startsWith('+')) {
                formData.phone = '+351' + formData.phone;
            }

            const authStore = useAuthStore();
            await HttpApi.post('auth/register', formData)
                .then(response => {
                    if(response.data.access_token !== undefined) {
                        authStore.setToken(response.data.access_token);
                        authStore.setUser(response.data.user);
                        authStore.setIsAuth(true);

                        _this.registerForm = {
                            name: '',
                            email: '',
                            password: '',
                            phone: '',
                            reference_code: '',
                            term_a: true,
                        }

                        // Remover o backdrop e a classe modal-open
                        const backdrop = document.getElementById('modalRegisterBackdrop');
                        backdrop.classList.add('hidden');
                        document.body.classList.remove('modal-open');
                        
                        // Fechar o modal
                        _this.modalRegister.toggle();
                        
                        // Rastreamento do Facebook Pixel para o evento de registro
                        if (window.fbq) {
                            try {
                                // Registra o evento de registro no Facebook Pixel
                                fbq('track', 'CompleteRegistration', {
                                    content_name: 'register_modal',
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
                                                content_name: 'register_modal',
                                                status: true
                                            }
                                        }]
                                    })
                                }).catch(err => {
                                    console.error('Erro ao enviar conversão para o Facebook:', err);
                                });
                                
                                console.log('Evento de registro modal enviado para o Facebook');
                            } catch (fbError) {
                                console.error('Erro ao rastrear evento de registro no Facebook:', fbError);
                            }
                        }
                        
                        _this.router.push({ name: 'home' });
                        _toast.success(_this.$t('Your account has been created successfully'));
                    }

                    _this.isLoadingRegister = false;
                })
                .catch(error => {
                    // Garantir que as notificações de erro apareçam por cima do modal
                    setTimeout(() => {
                        Object.entries(JSON.parse(error.request.responseText)).forEach(([key, value]) => {
                            _toast.error(`${value}`, {
                                position: 'top-center',
                                timeout: 5000,
                                closeOnClick: true,
                                pauseOnFocusLoss: true,
                                pauseOnHover: true,
                                draggable: true,
                                draggablePercent: 0.6,
                                showCloseButtonOnHover: false,
                                hideProgressBar: false,
                                closeButton: 'button',
                                icon: true,
                                rtl: false
                            });
                        });
                    }, 100);
                    _this.isLoadingRegister = false;
                });
        },
        logoutAccount: function() {
            const authStore = useAuthStore();
            const _toast = useToast();

            HttpApi.post('auth/logout', {})
                .then(response => {
                    authStore.logout();
                    this.router.push({ name: 'home' });

                    _toast.success(this.$t('You have been successfully disconnected'));
                })
                .catch(error => {
                    Object.entries(JSON.parse(error.request.responseText)).forEach(([key, value]) => {
                        console.log(value);
                        //_toast.error(`${value}`);
                    });
                });
        },
        hideLoginShowRegisterToggle: function() {
            // Esconder o backdrop do login
            const loginBackdrop = document.getElementById('modalAuthBackdrop');
            loginBackdrop.classList.add('hidden');
            
            // Mostrar o backdrop do registro
            const registerBackdrop = document.getElementById('modalRegisterBackdrop');
            registerBackdrop.classList.remove('hidden');
            
            // Manter a classe modal-open no body
            document.body.classList.add('modal-open');
            
            // Alternar os modais
            this.modalAuth.hide();
            this.modalRegister.show();
        },
        
        hideRegisterShowLoginToggle: function() {
            // Esconder o backdrop do registro
            const registerBackdrop = document.getElementById('modalRegisterBackdrop');
            registerBackdrop.classList.add('hidden');
            
            // Mostrar o backdrop do login
            const loginBackdrop = document.getElementById('modalAuthBackdrop');
            loginBackdrop.classList.remove('hidden');
            
            // Manter a classe modal-open no body
            document.body.classList.add('modal-open');
            
            // Alternar os modais
            this.modalRegister.hide();
            this.modalAuth.show();
        },
        toggleMenu: function() {
            this.sidebarMenuStore.setSidebarToogle();
        },
        loginToggle: function() {
            const backdrop = document.getElementById('modalAuthBackdrop');
            if (backdrop.classList.contains('hidden')) {
                backdrop.classList.remove('hidden');
                document.body.classList.add('modal-open');
            } else {
                backdrop.classList.add('hidden');
                document.body.classList.remove('modal-open');
            }
            this.modalAuth.toggle();
        },
        registerToggle: function() {
            const backdrop = document.getElementById('modalRegisterBackdrop');
            if (backdrop.classList.contains('hidden')) {
                backdrop.classList.remove('hidden');
                document.body.classList.add('modal-open');
            } else {
                backdrop.classList.add('hidden');
                document.body.classList.remove('modal-open');
            }
            this.modalRegister.toggle();
        },
        profileToggle: function() {
            const backdrop = document.getElementById('modalProfileBackdrop');
            if (backdrop.classList.contains('hidden')) {
                backdrop.classList.remove('hidden');
                document.body.classList.add('modal-open');
            } else {
                backdrop.classList.add('hidden');
                document.body.classList.remove('modal-open');
            }
            this.modalProfile.toggle();
        },
        openFileInput() {
            this.$refs.fileInput.click();
        },
        async handleFileChange(event) {
            const file = event.target.files[0];
            const formData = new FormData();
            formData.append('avatar', file);

            const reader = new FileReader();
            reader.onload = () => {
                this.avatarUrl = reader.result;
            };
            reader.readAsDataURL(file);

            await HttpApi.post('/profile/upload-avatar', formData, {
                headers: {
                    'Content-Type': 'multipart/form-data',
                },
            }).then(response => {
                console.log('Avatar atualizado com sucesso', response.data);
            })
                .catch(error => {
                    console.error('Erro ao atualizar avatar', error);
                });
        },
        getProfile: async function() {
            const _this = this;
            _this.isLoadingProfile = true;

            await HttpApi.get('/profile/')
                .then(response => {
                    _this.sumBets = response.data.sumBets;
                    _this.totalBets = response.data.totalBets;
                    _this.totalEarnings = response.data.totalEarnings;

                    const user = response.data.user;

                    if(user?.avatar != null) {
                        _this.avatarUrl = '/storage/'+user.avatar;
                    }

                    _this.profileName = user.name;
                    _this.profileUser = user;
                    _this.isLoadingProfile = false;
                })
                .catch(error => {
                    const _this = this;
                    Object.entries(JSON.parse(error.request.responseText)).forEach(([key, value]) => {

                    });
                    _this.isLoadingProfile = false;
                });
        },
        getSearch: async function() {
            const _this = this;

            await HttpApi.get('/search/games?searchTerm='+this.searchTerm)
                .then(response => {
                    _this.games = response.data.games;
                    _this.isLoadingSearch = false;
                })
                .catch(error => {
                    const _this = this;
                    Object.entries(JSON.parse(error.request.responseText)).forEach(([key, value]) => {

                    });
                    _this.isLoadingSearch = false;
                });
        },
        clearData: async function() {
            this.searchTerm = '';
            await this.getSearch();
        },
        handleAffiliateClick() {
            if (this.isAuthenticated) {
                this.$router.push('/profile/affiliate');
            } else {
                this.$router.push('/register');
            }
        },
        closeReferralBar() {
            this.showReferralBar = false;
        },
    },
    async created() {
        if(this.isAuthenticated) {

            await this.getProfile();
        }
    },
    watch: {
        searchTerm(newValue, oldValue) {
            this.getSearch();
        },
        async searchGameMenu(newValue, oldValue) {
            await this.getSearch();
            this.showSearchMenu = !this.showSearchMenu;
        },
        '$route.query.code'(newCode) {
            // Se detectar código na query, preencher e abrir modal
            if (newCode && !this.isAuthenticated) {
                this.registerForm.reference_code = newCode;
                this.isReferral = true;
                localStorage.setItem('referral_code', newCode);
                
                // Abrir modal com pequeno delay
                setTimeout(() => {
                    this.registerToggle();
                }, 500);
            }
        }
    },
};
</script>

<style>
/* Estilos para aplicar o efeito de desfoque no navtop quando os modais estão abertos */
.modal-open nav {
    filter: blur(4px);
    transition: filter 0.3s ease;
}
</style>
