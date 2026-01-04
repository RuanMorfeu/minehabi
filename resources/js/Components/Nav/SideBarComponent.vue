<template>
    <aside
        :class="[
            sidebar === true ? 'translate-x-0' : '-translate-x-full',
            //isAuthenticated ? 'top-[65px]' : 'top-[115px]'
        ]"
        class="fixed top-[74px] left-0 z-50 w-64 w-full-mobile h-screen transition-all duration-300 -translate-x-full sm:translate-x-0 sidebar-color custom-side-shadow snake-sidebar"
        aria-label="Sidebar"
    >
        <div class="h-full pb-4 pt-[30px] overflow-y-auto sidebar-color px-4">
            <!-- <ul class="">
                <li class="mb-3">
                    <div class="flex justify-between w-full gap-4">
                        <button @click.prevent="toggleMissionModal" class="border btn-menu-mission border-violet-800">
                            <div class="btn-menu-mission-text">
                                <h1>{{ $t('Mission') }}</h1>
                            </div>
                            <img :src="`/assets/images/quests.png`" alt="" width="38">
                        </button>
                        <button v-if="!isAuthenticated && setting && setting.disable_spin" @click.prevent="$router.push('/register')" class="border border-red-500 btn-menu-rotate">
                            <div class="btn-menu-rotate-text">
                                <h1>{{ $t('Rotate') }} </h1>
                            </div>
                            <div class="spin-anim">
                                <img class="img-spinbg" src="data:image/png;base64,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" alt=""><img class="img-turntable" src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAEAAAAA+CAMAAACsn+1dAAAC9FBMVEUAAAD/5YPzwUbwuzr/7Z7/76n+5ZD/7qT/8Kn52Hb623zwvj764Jf/7qfvvTz+66DzxlH74In20Wf+6Jn/8avxxU774IfvvDz17avuujf1zmLutzHtuDPttzD1zl7vvTz1zmP21G/413Z7KvN2JvD52nv0zGH/8Kr20WnhImBtH+kktcXttjByIuwuvM4qucr53IH1z2Txw0vyqBrDMubwwEP3wy/Bthi6rxW1qxTAL+TbH1vzyFj4ykyxphNhFuDmJmXa0lpnG+URpLDzrBrtkheaVfvccvHUUO68K+Jcz98gscHuuTb0sBlt0+GzIt5HxtX74YrWG1byxVLQF1HGD0e9sxacXPn/7KL0sxrulxrDuhnYYPFgz93JFEvvvDzxoxmsoQ+MQ/e5J+D955j845H1RH8Zq7rvLWzMwTL2uBmkFNTqKWjvnBrHNekUp7X634XVzEWlmgvOTOvMmoj601f6zkPwnxilaPrWZe/vqXjs0WXTzE34yEPPxzKvHtserrz96J36apv4XpHP04z1VYrny1hMy9ypGdfAj6L0Z5bxW4zsQXnY0Ef1vTPaZvEkqa3PcJz5rovsmGX3zFrmeFnTvUPvsEDZzD3BsCmjZ/rKOOrc35y3wXTxxnDAwGnjhFrdxFPXz03HvRpmHNyuINj836/i5637WI/wg2rzwl+onQ2RQ/qWTPJ209RkzdPSX8+CP8w5u8MwtLrJkYz6iYXxyILdmHjsr2vqrljhzUjItTfAPc+ZV8poycDBjsDejb3ruLi+jbVbtZ/nupzciIvKwmneYF3po1zwuk/hxk3wpS7fd+bljtaqI8uxcMSj28L507XTaLDapKfTlKCKv477ZY3YrIaswnz2WnjzaHXPNE/ytyyfWu3XcsK6Qr6nbrXEXLO5RbOGya9Bsanx0aezfKajx5Tu1ZHlunPcSFvNvVhxKNrNTNi2fdfFmNHFlMhFwcd4uI/YMVjlwEzGuiGlafuGOOnJO+i/4rxav7agZa6sTjDJAAAAIXRSTlMADCtCHaM2xY1Ok3X84pZy0L52W0Lz7r378Ozh2LaKWrlG6jL9AAAIGklEQVRIx7WXd1gSYRyAlbRsaHtPMA4qMzg9TUPLFLNEi2iQWjQgMzOpbGlDykybjsy22tC0TG240tLUcrT33nvv+U+/77sDR1n2R6+Pj88D9773+747BPT+H/r1WxoZGRoaGhk1bWXwz3YrowY8ROK5c4mJ8NfYsKV+3W0DI2Nwzp066eWydu1aFxeXk6fO8XjtDFvVUTfk8eScOfJELy+vQQgvILFd2tkkXoM6JPSNeDzl7Dlz5kh5JwcNWouAxknetyFDlu+DhMHf1m7Mk4M+e/ZsNu/UfBeatfNP8c66u9OJpn/0m/J4XKTb2dkt5BXO2kwza1Yhb9/YsThxlmeoX/v4hnJ0etCtrKymKhM3T2f4mpg0bNgwlIAh2tW6DIMGciV9etCnTuXIX1lOn26JAunys+bm5igBQ6QlGdf//fmN5XK7OVp9zBg7eYElQ4H8SB9zgCnI5b8tNJBr5FLkT8W+2015oa2tJf4plO/v06cPPYT7viT5I+PfrMJI0+RylEZKnx58NzepxpbGUrOlVy9tIS1J8yb1UYNfdrKlRnl595IojVqrOzkJNemDMemaB72ggBPvtmjezHO+1cSwhl9fqTy0e8nSyVFKLqM7WVDKAjpQoPzu2otOvNuifDt6nrPza2XTGhugjAJ/6eQpqIB1CwsnZfEkzKMmrgBK7N+iPGo6EQp7C5tU24amKtXlJUvAnzItSslxQ7rF+PFc1WDk5ylDgoOhgHzVUVMIjEaLaF71CrZRwQKwP21AlIrjhPTxc0Wq9KHAbdUDb2+UOAB+d1NU2OS8962qftUBpEvBn4z8AT0CVWzsz41VFaDABdVDbyAY/GO9u+MCjJAKI/w6APJ79AuUskGf6+cnLV4MPH3q7+/v7Y19CHRnRjgq1Y3QUiq9rF1ADwhAgQ+6hwdbumvx4jzpE3/gQIj0WM+eugLsgrSRNtBcGqUbAPnDh0MBfHGZ9F5Y2HlpkaOjfwT4A3FAN0JxG31mBWo1XgEMwPgjRgSqhR5icYX6YljYBXWEo2NEiDp8IASqFo6qG9KBhmq1IOrQNTwAE0AFQiwWCzJ9wwQhjM8E8D6mvr7wVK1m1tBIwOUIBILMwOtTtP64ceMCBVAgBKfvCJ6ALwjv3x8K9CLyjxXD8dwgbnNmCwRBqx0cgrgoknziAx0YCYUUcazgXqSgCPsACmTcvSAAghwcZiwKEtCb0Iabu9phwSJPnxlBXCAw+Tn4I6HATangXizmRoRwwQde5odnwgHsBB9ra58FDjNCufhCGnC5G1ZvX7TD09N61CifBBThRJ+4AYVsbgonkxuC/Z0Zj6+gZ0LXb+zbdxQdyOW2xC9EDme1gzbQ18bG5nBCEIfD2QaRbA4oXE54xt0ADgCyvb2NDQp4osAGTgt8EbQBa+tRfSFgbz9hgv3hBDaKcDCZ8Bu0fuOEPXvgGTpgTQca4UD1CXBgz8yZM20Oh7KhArATDtvPBJBfLcCmA2y2LtC3SgBNQcOvc0A3gf3G9UFgbsM+/oOXUBnwrB7YUHUPQA5FVsCPjCt0IP7GiehtbD4bNrHGBF1QgMXnb1jtwAQ2ri8X8vn8K48z4MoF8K8GbeOLRPx4uC1uxJTygXK4jNoJcvn4Kujz+ehG8vH85FNOwCHPwvNf9geQ/5BfEij0wAVg3PP4bDiAKPf5BIEZi3L59KupgzB3hsP28lChULgt4G5GT7hjsS+86vheeC9SWOEhEsbjALzIPpyIToEDiVC4lUOFLBzoJhSFEvDglcf53Xv37okC2N/q6LhVePq+MFaMCuBDYPjwfv16PI+JRseHEh30MC0Ignh28cwXU9PuKIALOwMI8B2JbN/ThEws9pAR8eN0gR4DBqy6fiibILoxbyoEccZ50+iJEGAKWr+IiPT1zSI86EJVf+WKZZOTCdhDTAfiovM8CDAjAMgHLhH3fX0jiQoxLsToAtNwIIVgMYEuopTUaiMEiLZG+EPguOi0r+99UawY8JOJYnQDoMB1UTM9BpZIdAaPwBTAD/aGf8QRoixfIAVtgoeHHymK6VdlgGgRrIChmejZXlTAAfDjgr29oVAkigyDQJbID/l+46HA7AAErola6+sC9WSyM86fR0/EhYOyuAOuwShxVXYnDIiUVYDuN3euBSmLqRxABvdx5QiybGYXTJHfy9UVJRLKdiHuyGKRDu92qEBfgmXXZK0NQKwcgQx3xos4SMbth/dxV+AhmbUYsauMxDp6x4cC+DBADtlYryomJJnvPG/T6IPkcfg4RBfek5FDMVmkBejIdxpDkcn4HiCb1fiI1ozMSXWeh3xz8z6QALaSeXQgkrxJ605ubmtIMnnZ5Otka5ZedVitqay94KcNGwYFnPiYM4kmj5Iw+pg1diRJJV8ro7rq1aQeReVQx/e5j4UCThRRJYMZcihGX2Nnt24hRZVRsAG/0JiiPqYNcXcfyyQuUedtGUqoMW7gI33dQisrimqr9ztMKCpu+RBdIo7Kwx80LW1tz1M34ez49OArqLa1fN5urFC8OLIcFyBBlVpqeaVQgA2nx77CpBYf9qG9QnEJDQGNB4qSWTpKFWBjfY1CAeuvFVYniSIOhoDGE8nt+TpKFHYLQUenb1/vz994TCQSCSSWD3khGVTJbYnCCoDn2rL0/gKrLRz24tIRSbSLlw4XicTqJjzesateHajX2UxiZmYWfT7dhSE9MtYM6AR63WCZdDTDxOaUlsaCDLQ3oRdf50bjzp3MtHTs3Fhn/1uFVQ9g/fFL709G4xjCwDAZIwAAAABJRU5ErkJggg==" alt=""><img class="img-pointer" src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAACIAAAA8CAMAAAA0RZgbAAAC91BMVEUAAABYDARfCwRRAABLAABJAABIAABKAABKBQP/1JyIRA2HRAxJAACNUBf80JqJRA355n7905uIRRCIRA2HRQ2HRQ6JRxH//9v+05v5zJn4y5jcroCJRQ6HQw3/87+CQAuISBD/+beIRQ6HRQ6JRw+OSRa5LPH577R8x5750ZT/88/+76/xvpr2xpn45YHvupznqKXzx5P66bDxxZDtwY7KoFyvfDfgsoOWWR2IRQ6FQgz+8K3+7p51PSz/8pv/98+STBP/9ND97aT88srz7cL86pX76In/8L776qPqwILYV9PRc8r14ZbxtKXXgbTkoKThw37/88v24YvXtXPXtm397aLpvIr+8r/nzHDmuIj854SmcDf+8sGjaSqKSRp9OQv/8sb96Y/QoXT765L/88DLmHB+NwuIRg+IRg/86YefaU1nIwX/87NdFQdjKB/a5sDxzYin1bbMM/GdKevdOfG0MemkLufW5L7wd/TFOebaZez24X3WQ+Xq0XPN4rGvRtzkc+X033m1SdX/9M/97J3/89Dsis/hbsL97aru1oPcdb3Ne7vqoqzUhq7+76znzXD/8sj233rev2P765H754Tau3X/88/Tr2Lnzo7/8tHJoU7/883ozW755H+6jEK2h0Xr0HPCl0fXqHfly3CmbEKRURX/77L97JjQoHb/9NLEk2xzMQm5hmG3g2CqdFmFUDr/9NL977D/1Jz/8syzOvOgJO+aJO+mLvGvK+7YYvfhUO/KO+7WRu28M+0KmpEIoY7md/nARfUMmJjlzHD+8sgNlKAMlpz865cIoIf66IDzcf6+M+7+8cL977X97aYMnZX76YvsefrNUffdM/bETPX/88/87J776pAInYv66IXx23nmZ/vCSfXMMfP+8Lz97qoOkaTpb/rVWffVMPbeQ/LUQO/LO++tKu6Bxq2/26tVsKtIqqtsvapgt6qh0aMwoaIinp7jcvinL/HCLvGnJfDcSu+xKu+t1rTr6a7R36o6o6Y1s5M3sJB1vDZKAAAAsXRSTlMABgQKDhIbGB/8ZTsVDe5w/vNQQzUwGwT45t1+elo7KycKXkgiFv7+/fzv3dza2djRw7q5q6KRiIRrV081KiAVEvv7+vrz8fDr5t/a19LR0czCt6+kop6WlpWSj4mJh4aAe25kY15dVU1MOy8pJiX9/fr5+ff19PTy8e/v6+vq6ejl4+Hg2trV1dTT09PQzsvIx8LBwcC7ubeysK6uraubl5OSiXh4cGpmWlpUTEpCMC39GEEeAAADgklEQVRIx9WTVVQbQRSGs9lslEaA0CRAKNDiDsUp1N3d3d3d3d3dXZgqdUkb0iZpCi0Ub5HiUteHDix0Z8jpc0+/zcnZm/3OzL9zb1j/OY7R0Y5/e6Qe1nPHUo/GryCNPZbv7nf6Ul1MqNu56WUzPPZiziaNpunMyQUlpUXFxUWlJQVfJ2o0ms6YMkjfLP7evecPnzx+8/jJw7t3X8fHN9MMwhS1Xv8TV37p9Wo8TMtMN1xxy2yJ540JMCyAyvOK8h/lFVVKYWZAHUSoYy3sZnAtK/wWS+P6vczN1dDV3oH9RyHk9n1ja2Ew9LWsx2KoZ2nnGevZ80SEOnLcOHXEsH4BnrGtJFY2iGJjJdmVtQoNtyGrk1CEhREJGxqNjZgforOMDcPlLBR5eGiTJkOZOsJoDB1VH1Pqj7Jrl9SdqbsntQuzaoApDazCuia1Ys5qWdJ2J2sYBTsZp2CtNrKmbKTVHhzpwMJxGGnXXDu4pjqpbX7cGUbBwzhLuiRvram6JK8OE8EoeBj42qZkx+qmtkje6WRN1FIIa6f+JtNouog0mYItmShID3xS+tD3g1NaSJxtzBQbZ8m+FB/6flvKRqEoxkyJEQkP63RVPXCU6vbYy5koyECESnVVPRit0/VnBgEPs1m6n8Vms/pIFyODgA9EN+lcgiAJH+kWZBDwgQhOTT3Uq9fR1NQD9nJmJtGTGTFw/iOaAeEOhLlDkGNHDOw0yV0A3N1bDzg7nqQdzKDGHmsNBN7t2/uKgezIeIvaDpukuEMUsnWznlayQgUCJ0AHMwgOb7hAuSjt7QvI27Q0PxDIp0g2tojFRbF4Ttz7+zRx2X7Alssh0CQcbg/QNiEhrprshASVOAougyaZIPN9AEl/CUlPh3dtgS2PJNB9hoO1V3Bk/uhOBMkLAQuvo3y47qvkUwQeZeo1nI4KqLARpTeYcRXHT4YpJM8WLMm/gZD/yUvF52Bxz4H1X57duQM/8AteLtMFQVxUISi+Uuny7BbNO3i5dACn0JeGYeBOHT7m3a4hY7ZYhXcAnh3fW9AmI+9mFZ8zpngJzvA4BD4LFmPEijU5ibnQyM2Z5w1CYBJ27VaPUQGvldMSExPbdFQIbPkUnQR3onrLgECsVADgf55LMSOFOBQ3akhQoH9QyAW+BYcxUIfkUDwuhAcFZhfMgRLJgcDnyBLmViXwH/kP+A0MspdQULbX5QAAAABJRU5ErkJggg==" alt="">
                            </div>
                        </button>
                    </div>
                </li>
            </ul> -->

            <div
                class="flex items-center justify-center text-gray-500 md:hidden"
            >
                <RouterLink
                    :to="{ name: 'home' }"
                    active-class="text-white border-b-2 border-primary"
                    class="flex gap-2 flex-row w-[50%] cursor-pointer items-center px-2 py-3 justify-center"
                >
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        version="1.1"
                        xmlns:xlink="http://www.w3.org/1999/xlink"
                        xmlns:svgjs="http://svgjs.com/svgjs"
                        width="16px"
                        height="16px"
                        x="0"
                        y="0"
                        viewBox="0 0 512 512"
                        xml:space="preserve"
                    >
                        <g>
                            <path
                                d="M479.001 34.656 285.933.613c-21.715-3.829-42.423 10.671-46.252 32.386l-1.827 10.363c27.921 3.44 51.51 23.554 58.937 51.269l11.799 44.035a35.815 35.815 0 0 1 20.537-2.414c13.677 2.412 24.17 12.255 28.079 24.642 7.91-10.303 21.137-15.964 34.814-13.552 19.575 3.452 32.646 22.119 29.194 41.694-7.5 42.534-53.143 76.721-74.804 90.782l33.071 123.423a69.601 69.601 0 0 1 2.349 19.79l27.824 4.906c21.715 3.829 42.423-10.671 46.252-32.386l55.48-314.641c3.83-21.717-10.67-42.425-32.385-46.254z"
                                fill="currentColor"
                                data-original="#000000"
                            ></path>
                            <path
                                d="M267.867 102.382c-4.78-17.838-20.911-29.603-38.54-29.602-3.42 0-6.898.443-10.359 1.37L29.602 124.89c-21.299 5.707-33.939 27.6-28.232 48.899l82.691 308.609c4.78 17.838 20.911 29.602 38.54 29.602 3.42 0 6.898-.443 10.358-1.37l189.366-50.741c21.299-5.707 33.939-27.6 28.232-48.899zM120.51 313.333a10.603 10.603 0 0 1-3.042-11.353l28.956-85.738c2.422-7.172 11.364-9.568 17.048-4.568l67.946 59.774a10.603 10.603 0 0 1 3.042 11.353l-28.956 85.738c-2.422 7.172-11.364 9.569-17.048 4.568z"
                                fill="currentColor"
                                data-original="#000000"
                            ></path>
                        </g>
                    </svg>
                    <p class="text-[14.4px] font-bold">CASSINO</p>
                </RouterLink>

            
            </div>

            <div class="snake-sidebar-brand">
                <div class="flex justify-center">
                    <RouterLink :to="{ name: 'home' }" class="flex justify-center">
                        <div class="w-[60%]"></div>
                    </RouterLink>
                </div>
                <div class="snake-welcome">
                    <span>Seja muito bem vindo(a)</span>
                </div>
            </div>

            <div v-if="isAuthenticated && userData" class="snake-balance mt-4">
                <img :src="walletIconUrl" alt="wallet" />
                <span>{{ userData?.wallet?.balance ?? userData?.balance ?? '0.00' }}</span>
            </div>

            <ul class="space-y-2 font-medium">
                <li class="">
                    <RouterLink
                        :to="{ name: 'home' }"
                        active-class="link-active"
                        class="snake-side-item"
                    >
                        <i class="fa-duotone fa-house"></i>
                        <span class="ml-3">{{ $t("Home") }}</span>
                    </RouterLink>
                </li>

                <li class="" v-if="isAuthenticated">
                    <RouterLink
                        :to="{ name: 'profileWallet' }"
                        active-class="link-active"
                        class="snake-side-item"
                    >
                        <i class="fa-duotone fa-wallet"></i>
                        <span class="ml-3">{{ $t("Wallet") }}</span>
                    </RouterLink>
                </li>
                <li class="" v-if="isAuthenticated">
                    <RouterLink
                        :to="{ name: 'casinos' }"
                        active-class="link-active"
                        class="snake-side-item"
                    >
                        <i class="fa-duotone fa-stars"></i>
                        <span class="ml-3">{{ $t("Favorites") }}</span>
                    </RouterLink>
                </li>

                <!-- Dynamic Games List -->
                <!-- Debug: {{ allExclusiveGames.length }} games -->
                <li v-if="allExclusiveGames && allExclusiveGames.length > 0" v-for="(game, index) in allExclusiveGames.slice(0, 10)" :key="index">
                    <RouterLink
                        :to="{ name: 'home' }"
                        active-class="link-active"
                        class="snake-side-item"
                    >
                        <i class="fa-duotone fa-gamepad"></i>
                        <span class="ml-3">{{ game.game_name }}</span>
                    </RouterLink>
                </li>
                <li v-if="!allExclusiveGames || allExclusiveGames.length === 0">
                    <div class="snake-side-item" style="opacity: 0.75;">
                        <i class="fa-duotone fa-gamepad"></i>
                        <span class="ml-3">Carregando jogos...</span>
                    </div>
                </li>
            </ul>
            <ul
                class="bg-gray-600/[0.2] flex gap-3 flex-col py-3 px-4 hover:dark:bg-gray-600/[0.1] rounded-[6px] font-medium mt-6 mb-[200px]"
            >
                <a @click.prevent="logoutAccount" href="#" class="flex items-center w-full font-normal text-gray-700 transition duration-700 group dark:text-gray-400 dark:hover:text-white" role="menuitem">
                    <span class="w-4 mr-3">
                        <i class="fa-duotone fa-right-from-bracket"></i>
                    </span>
                    {{ $t('Sign out') }}
                </a>
            </ul>
        </div>
    </aside>
</template>

<script>
import { onMounted } from "vue";
import { sidebarStore } from "@/Stores/SideBarStore.js";
import { RouterLink } from "vue-router";
import HttpApi from "@/Services/HttpApi.js";
import { useToast } from "vue-toastification";
import { useAuthStore } from "@/Stores/Auth.js";
import { useSettingStore } from "@/Stores/SettingStore.js";
import { missionStore } from "@/Stores/MissionStore.js";

export default {
    props: [],
    components: { RouterLink },
    data() {
        return {
            snakeLogoUrl: new URL('../../../../public/assets/images/logo_white.png', import.meta.url).href,
            walletIconUrl: new URL('../../../../public/assets/icons/wallet-money.svg', import.meta.url).href,
            sidebar: false,
            isLoading: true,
            categories: [],
            sportsCategories: [],
            modalMission: null,
            setting: null,
            exclusive_games: null,
            exclusive2_games: null,
        };
    },
    setup(props) {
        onMounted(() => {});

        return {};
    },
    computed: {
        sidebarMenuStore() {
            return sidebarStore();
        },
        sidebarMenu() {
            const sidebar = sidebarStore();
            return sidebar.getSidebarStatus;
        },
        isAuthenticated() {
            const authStore = useAuthStore();
            return authStore.isAuth;
        },
        allExclusiveGames() {
            const games = [];
            
            // Adiciona jogos exclusivos originais
            if (this.exclusive_games && this.exclusive_games.length > 0) {
                games.push(...this.exclusive_games);
            }
            
            // Adiciona jogos exclusive2
            if (this.exclusive2_games && this.exclusive2_games.length > 0) {
                games.push(...this.exclusive2_games);
            }
            
            // Ordena por visualizações (mais vistos primeiro) com tratamento robusto
            const sorted = games.sort((a, b) => {
                const viewsA = parseInt(a.views) || 0;
                const viewsB = parseInt(b.views) || 0;
                return viewsB - viewsA;
            });
            
            console.log('SideBar: allExclusiveGames computed:', sorted.length);
            return sorted;
        },
    },
    mounted() {
        this.loadGames();
    },
    methods: {
        loadHoorySupport() {
            // Disponibiliza o toast globalmente para o script de suporte
            const toast = useToast();
            window.$toast = toast;
            
            // Verifica se o script já foi carregado
            if (window.loadHoorySupport) {
                window.loadHoorySupport();
            } else {
                // Carrega o script se ainda não estiver carregado
                const script = document.createElement('script');
                script.src = '/js/hoory-support.js';
                script.onload = function() {
                    window.loadHoorySupport();
                };
                document.head.appendChild(script);
            }
        },
        loadGames: async function() {
            console.log('SideBar: Carregando jogos...');
            try {
                // Carrega jogos exclusivos
                const exclusiveResponse = await HttpApi.get("exclusive/games");
                console.log('SideBar: Resposta exclusive:', exclusiveResponse.data);
                if (exclusiveResponse.data && exclusiveResponse.data.exclusive_games) {
                    this.exclusive_games = exclusiveResponse.data.exclusive_games;
                    console.log('SideBar: Jogos exclusive carregados:', this.exclusive_games.length);
                }
                
                // Carrega jogos exclusive2
                const exclusive2Response = await HttpApi.get("exclusive2/games");
                console.log('SideBar: Resposta exclusive2:', exclusive2Response.data);
                if (exclusive2Response.data && exclusive2Response.data.exclusive2_games) {
                    this.exclusive2_games = exclusive2Response.data.exclusive2_games;
                    console.log('SideBar: Jogos exclusive2 carregados:', this.exclusive2_games.length);
                }
                
                console.log('SideBar: Total de jogos:', this.allExclusiveGames.length);
            } catch (error) {
                console.error("SideBar: Erro ao carregar jogos:", error);
            }
        },
        toggleMenu() {
            this.sidebarMenuStore.setSidebarToogle();
        },
        
        
        toggleMissionModal: function () {
            const missionDataStore = missionStore();
            missionDataStore.setMissionToogle();
        },
        logoutAccount: function() {
            const authStore = useAuthStore();
            const _toast = useToast();

            HttpApi.post('auth/logout', {})
                .then(response => {
                    authStore.logout();
                    this.$router.push('/login');

                    _toast.success(this.$t('You have been successfully disconnected'));
                })
                .catch(error => {
                    Object.entries(JSON.parse(error.request.responseText)).forEach(([key, value]) => {
                        console.log(value);
                        //_toast.error(`${value}`);
                    });
                });
        },
        getCasinoCategories: function () {
            const _this = this;
            const _toast = useToast();
            _this.isLoading = true;

            HttpApi.get("categories")
                .then((response) => {
                    _this.categories = response.data.categories;
                    _this.isLoading = false;
                })
                .catch((error) => {
                    Object.entries(
                        JSON.parse(error.request.responseText)
                    ).forEach(([key, value]) => {
                        _toast.error(`${value}`);
                    });
                    _this.isLoading = false;
                });
        },
        getSetting: function () {
            const _this = this;
            const settingStore = useSettingStore();
            const settingData = settingStore.setting;
            if (settingData) {
                _this.setting = settingData;
            }
        },
        getCustom: function () {},
    },
    created() {
        this.getCasinoCategories();
        this.getSetting();
    },
    watch: {
        sidebarMenu(newVal, oldVal) {
            this.sidebar = newVal;
        },
    },
};
</script>

<style scoped></style>
