<template>
  <div class="chicken-game-wrapper">
    <div id="main_wrapper">
        <header id="header">
            <div id="logo"></div>
            <div class="menu">
                <button data-rel="menu-balance" data-testid="menu-balance">
                    <span id="user_balance">{{ balance }}</span>
                    <svg width="18" height="18" viewBox="0 0 18 18" style="fill: rgb(255, 255, 255);">
                        <use :href="currencySvgPath"></use>
                    </svg>
                </button>
                <button id="sound_switcher"></button>
            </div>
        </header>

        <main id="main">
            <div id="game_container">
                <canvas id="game_field"></canvas>
                <div id="battlefield"></div>
            </div>
            <div id="stats">
                <span>LIVE WINS</span>
                <div><i></i></div>
                <span class="online">ONLINE: 8768</span>
            </div>
            <div id="random_bet"></div>
        </main>

        <footer id="footer">
            <div id="bet_wrapper">
                <section id="values">
                    <div class="bet_value_wrapper gray_input">
                        <button class="" data-rel="min">MIN</button>
                        <input type="text" value="0.50" id="bet_size" data-default-bet="0.50" data-min-bet="0.5" data-max-bet="100.00" step="0.01">
                        <button class="" data-rel="max">MAX</button>
                    </div>
                    <div class="basic_radio">
                        <!-- Filled by JS -->
                    </div>
                </section>
                <section id="dificulity">
                    <h2>
                        DIFFICULTY
                        <span>CHANCE</span>
                    </h2>
                    <div class="radio_buttons">
                        <label class="active selected">
                            <input type="radio" name="difficulity" value="easy" checked autocomplete="off">
                            <span>EASY</span>
                        </label>
                        <label>
                            <input type="radio" name="difficulity" value="medium" autocomplete="off">
                            <span>MEDIUM</span>
                        </label>
                        <label>
                            <input type="radio" name="difficulity" value="hard" autocomplete="off">
                            <span>HARD</span>
                        </label>
                        <label>
                            <input type="radio" name="difficulity" value="hardcore" autocomplete="off">
                            <span>HARDCORE</span>
                        </label>
                    </div>
                </section>
                <section id="buttons_wrapper">
                    <button id="close_bet" style="display: none;">CASH OUT <span></span></button>
                    <button id="start">PLAY</button>
                </section>
            </div>
        </footer>
    </div>
    <div id="win_modal">
        <div class="inner">
            <h2>YOU WIN!</h2>
            <h3>x100.00</h3>
            <h4>+<span>10000</span> <svg width="25" height="25" viewBox="0 0 18 18" style="fill:#2bfd80;">
                    <use :href="currencySvgPath"></use>
                </svg></h4>
        </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted, onUnmounted, ref, computed } from 'vue';
import { useAuthStore } from '@/Stores/Auth.js';
import HttpApi from '@/Services/HttpApi.js';

const authStore = useAuthStore();
const balance = ref('0.00');

// Currency SVG path - using EUR symbol
const currencySvgPath = computed(() => '/assets/images/chicken/currency.svg#EUR');

// Load scripts dynamically
const loadScript = (src) => {
  return new Promise((resolve, reject) => {
    const script = document.createElement('script');
    script.src = src;
    script.async = false;
    script.onload = resolve;
    script.onerror = reject;
    document.body.appendChild(script);
  });
};

const loadStyle = (href) => {
    const link = document.createElement('link');
    link.rel = 'stylesheet';
    link.href = href;
    document.head.appendChild(link);
};

onMounted(async () => {
    // Load CSS
    loadStyle('/assets/chicken/css/reset.css');
    loadStyle('/assets/chicken/css/bootstrap.min.css');
    loadStyle('/assets/chicken/css/style.css');
    loadStyle('/assets/chicken/css/style2.css');
    loadStyle('/assets/chicken/css/avesome.css');

    // Wait a bit for CSS to load
    await new Promise(resolve => setTimeout(resolve, 100));

    // Get initial balance
    try {
        const response = await HttpApi.get('profile/wallet');
        if (response.data.wallet) {
            // Usa o mesmo valor que o Mines: total_balance sem formatação
            const rawBalance = parseFloat(response.data.wallet.total_balance || 0);
            balance.value = rawBalance.toString();
            
            // Também define no window para o jogo pegar
            window.INITIAL_BALANCE = rawBalance;
            console.log('Initial balance set:', rawBalance);
        }
    } catch (e) {
        console.error("Error loading wallet", e);
    }

    // Load JS
    const loadScriptWithCache = (src) => loadScript(src);

    // 1. Core Dependencies
    if (!window.jQuery) {
        await loadScriptWithCache('/assets/chicken/js/jquery.js');
    }
    
    // 2. Parallel Dependencies
    await Promise.all([
        loadScriptWithCache('/assets/chicken/js/bootstrap.bundle.min.js'),
        loadScriptWithCache('/assets/chicken/js/howler.min.js')
    ]);
    
    // Game logic
    // Important: GAME_CONFIG must be set before game scripts run
    window.GAME_CONFIG = {
        user_id: authStore.user?.id,
        is_real_mode: true,
        currency_symbol: 'EUR',
        currency: 'EUR',  // Adicionando currency explicitamente
        min_bet: 1,
        max_bet: 100,
        user_country: 'default'
    };
    
    // Expose Token for legacy scripts
    window.API_TOKEN = authStore.token;
    window.ACCESS_TOKEN = authStore.token; // Compatibility for legacy checks
    console.log('Chicken.vue mounted, token:', authStore.token ? (authStore.token.substring(0, 10) + '...') : 'null');
    console.log('Raw token from localStorage:', localStorage.getItem('token'));
    console.log('authStore.token type:', typeof authStore.token);
    console.log('authStore.token length:', authStore.token ? authStore.token.length : 0);

    // Localization
    window.LOCALIZATION = {
        TEXT_BETS_WRAPPER_PLAY: 'PLAY',
        TEXT_BETS_WRAPPER_GO: 'GO',
        TEXT_BETS_WRAPPER_WAIT: 'WAIT',
        TEXT_LIVE_WINS_ONLINE: 'ONLINE'
    };

    // 3. Load Game Scripts (Sequential to preserve order)
    // Using static version v=4.2 to ensure cache usage but force update for new files
    await loadScriptWithCache('/assets/chicken/js/chicken_render.js?v=4.2'); 
    await loadScriptWithCache('/assets/chicken/js/chicken_core.js?v=4.2');

    // Force currency to be EUR after scripts load
    if (window.SETTINGS) {
        window.SETTINGS.currency = 'EUR';
        console.log('Forced SETTINGS.currency to EUR');
    }

    // 4. Force fix for ground borders - add inline styles
    const fixStyle = document.createElement('style');
    fixStyle.innerHTML = `
        #battlefield .sector {
            background: transparent url('/assets/images/chicken/border.png') right center repeat-y !important;
            background-size: 5px auto !important;
            border: none !important;
            box-shadow: none !important;
            outline: none !important;
        }
        #battlefield .sector .border {
            background: transparent url('/assets/images/chicken/footer1.png') center center repeat-x !important;
            border: none !important;
            box-shadow: none !important;
            outline: none !important;
        }
        #battlefield .sector:nth-child(odd) .border {
            background-image: url('/assets/images/chicken/footer2.png') !important;
        }
        #battlefield .sector .breaks {
            background: transparent url('/assets/images/chicken/break3.png') 20px center no-repeat !important;
            background-size: auto 30px !important;
            border: none !important;
            box-shadow: none !important;
            outline: none !important;
        }
        #battlefield .sector.closer:before {
            background: #2d324d url('/assets/images/chicken/walll.png') left center repeat-y !important;
            background-size: 100% auto !important;
        }
        #battlefield .sector.closer:after {
            background: #2d324d url('/assets/images/chicken/wallr.png') right center repeat-y !important;
            background-size: 100% auto !important;
        }
        /* Fix balance display */
        #user_balance {
            font-size: 14px !important;
            font-weight: 600 !important;
            color: #fff !important;
            font-family: "Montserrat", sans-serif !important;
            text-align: right !important;
            line-height: 1 !important;
            display: inline !important;
        }
        button[data-rel="menu-balance"] {
            display: flex !important;
            justify-content: center !important;
            min-width: 150px !important;
            align-items: center !important;
            padding: 4px 16px !important;
            gap: 12px !important;
            background: rgba(255, 255, 255, 0.1) !important;
            border-radius: 6px !important;
            height: 33px !important;
            color: #fff !important;
            border: none !important;
            cursor: pointer !important;
            font-size: 14px !important;
            font-weight: 600 !important;
            font-family: "Montserrat", sans-serif !important;
        }
        button[data-rel="menu-balance"] svg {
            width: 18px !important;
            height: 18px !important;
            fill: rgb(255, 255, 255) !important;
        }
    `;
    document.head.appendChild(fixStyle);
});

onUnmounted(() => {
    // Cleanup if necessary (remove scripts/styles to avoid pollution?)
    // For now, reloading the page is cleaner for this kind of legacy JS integration.
    // window.location.reload(); 
});
</script>

<style scoped>
/* Scoped styles to ensure wrapper handles layout */
.chicken-game-wrapper {
    width: 100%;
    min-height: 100vh;
    background: #1b1c2d;
    position: relative;
    overflow: hidden;
}

#main_wrapper {
    height: 100vh;
    display: flex;
    flex-direction: column;
}

/* Ensure canvas and game container fit */
#game_container {
    flex: 1;
    position: relative;
    overflow: hidden;
}
</style>
