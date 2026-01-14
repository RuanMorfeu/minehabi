// Ensure LOCALIZATION is defined
if (typeof window.LOCALIZATION === 'undefined') {
    window.LOCALIZATION = {
        TEXT_BETS_WRAPPER_PLAY: 'PLAY',
        TEXT_BETS_WRAPPER_GO: 'GO',
        TEXT_BETS_WRAPPER_WAIT: 'WAIT',
        TEXT_LIVE_WINS_ONLINE: 'ONLINE'
    };
}
// Make sure it is available as a global variable
var LOCALIZATION = window.LOCALIZATION;

var SETTINGS = {
    w: 800, // будет обновлено при инициализации
    h: 600, // будет обновлено при инициализации 
    start: {
        x: 0, 
        y: 0 
    }, 
    timers: {  
    }, 
    volume: {
        active: +$('body').data('sound'), 
        music: +$('body').data('sound') ? 0.2 : 0, 
        sound: +$('body').data('sound') ? 0.9 : 0
    }, 
    currency: window.GAME_CONFIG ? window.GAME_CONFIG.currency_symbol : ($('body').attr('data-currency') ? $('body').attr('data-currency') : "USD"), 
    cfs: window.CFS || {
        easy: [ 1.03, 1.07, 1.12, 1.17, 1.23, 1.29, 1.36, 1.44, 1.53, 1.63, 1.74, 1.86, 1.99, 2.13, 2.29, 2.46, 2.64, 2.84, 3.06, 3.30, 3.56, 3.84, 4.15, 4.48, 4.84, 5.23, 5.65, 6.11, 6.61, 7.15 ],
        medium: [ 1.15, 1.32, 1.51, 1.73, 1.98, 2.27, 2.60, 2.98, 3.41, 3.90, 4.46, 5.11, 5.85, 6.70, 7.67, 8.78, 10.05, 11.51, 13.19, 15.11, 17.32, 19.85, 22.75, 26.08, 29.90, 34.28, 39.30, 45.05, 51.63, 59.20 ],
        hard: [ 1.25, 1.45, 1.68, 1.95, 2.26, 2.62, 3.04, 3.52, 4.08, 4.73, 5.48, 6.35, 7.36, 8.53, 9.89, 11.47, 13.30, 15.42, 17.89, 20.75, 24.07, 27.92, 32.39, 37.57, 43.58, 50.55, 58.64, 68.02, 78.89, 91.50 ],
        hardcore: [ 1.35, 1.60, 1.90, 2.25, 2.67, 3.17, 3.76, 4.46, 5.30, 6.30, 7.48, 8.89, 10.57, 12.57, 14.95, 17.78, 21.15, 25.15, 29.90, 35.56, 42.30, 50.28, 59.77, 71.06, 84.51, 100.45, 119.43, 141.96, 168.76, 200.50 ]
    },  
    chance: {
        easy: [ 7, 20 ],    
        medium: [ 3, 12 ],  
        hard: [ 2, 8 ],     
        hardcore: [ 2, 6 ]  
    },
    min_bet: window.GAME_CONFIG ? window.GAME_CONFIG.min_bet : 0.5, 
    max_bet: window.GAME_CONFIG ? window.GAME_CONFIG.max_bet : 150, 
    segw: parseInt( $('#battlefield .sector').css('width') ),
} 

var SOUNDS = {
    music: new Howl({
        src: ['/assets/sounds/chicken/music.webm'], 
        //autoplay: true, 
        preload: true, 
        html5: true, 
        loop: true, 
        volume: SETTINGS.volume.music 
    }), 
    button: new Howl({
        src: ['/assets/sounds/chicken/button.webm'], 
        //autoplay: true, 
        preload: true, 
        html5: true, 
        loop: false, 
        volume: SETTINGS.volume.sound 
    }), 
    win: new Howl({
        src: ['/assets/sounds/chicken/win.webm'], 
        //autoplay: true, 
        preload: true, 
        html5: true, 
        loop: false, 
        volume: SETTINGS.volume.sound 
    }), 
    lose: new Howl({
        src: ['/assets/sounds/chicken/lose.webm'], 
        //autoplay: true, 
        preload: true, 
        html5: true, 
        loop: false, 
        volume: SETTINGS.volume.sound 
    }), 
    step: new Howl({
        src: ['/assets/sounds/chicken/step.webm'], 
        //autoplay: true, 
        preload: true, 
        html5: true, 
        loop: false, 
        volume: SETTINGS.volume.sound 
    })
}

class Game{
    constructor( $obj ){ 
        // Получаем access_token из URL или глобальной переменной
        var urlParams = new URLSearchParams(window.location.search);
        var accessTokenParam = urlParams.get('access_token');
        
        // Сохраняем access_token в глобальной переменной
        if (accessTokenParam) {
            window.ACCESS_TOKEN = accessTokenParam;
            console.log('Access token set from URL:', accessTokenParam);
        }
        
        // Инициализируем window.GAME_CONFIG только если он не существует
        if (!window.GAME_CONFIG) {
            window.GAME_CONFIG = {};
        }
        
        // Устанавливаем дефолтный баланс только если нет access_token
        if (window.ACCESS_TOKEN || window.API_TOKEN) {
            console.log('Access token present - balance will be loaded from API');
            this.balance = 0; // Временное значение, будет обновлено из API
        } else {
            this.balance = SETTINGS.balance || this.getDefaultBalanceForCountry();
            console.log('No access token - using default balance:', this.balance);
        }
        
        console.log('Game initialized with access token:', !!window.ACCESS_TOKEN);
        
        this.currency = SETTINGS.currency; 
        this.stp = 0;  
        this.cur_cfs = 'easy'; 
        this.cur_lvl = 'easy'; 
        this.current_bet = 0; 
        this.cur_status = "loading"; 
        this.wrap = $('#battlefield'); 
        this.sectors = []; 
        this.alife = 0; 
        this.win = 0; 
        this.fire = 0; 
        this.traps = null; // for local traps
        this.localTraps = null;
        // this.create(); 
        this.bind(); 
        $('#game_container').css('min-height', parseInt( $('#main').css('height') )+'px' );
        
        // Инициализируем кнопки уровней
        this.initializeLevelButtons();
        
        // Принудительно сбрасываем все кнопки при инициализации
        this.resetAllLevelButtons();
        
        // Проверяем кнопки уровней через некоторое время (возможно, они загружаются позже)
        setTimeout(() => {
            console.log('=== DELAYED LEVEL BUTTONS CHECK ===');
            this.initializeLevelButtons();
        }, 2000);
        
        setTimeout(() => {
            console.log('=== SECOND DELAYED LEVEL BUTTONS CHECK ===');
            this.initializeLevelButtons();
        }, 5000);
        
        // Получаем актуальную информацию о пользователе при инициализации
        if (window.ACCESS_TOKEN || window.API_TOKEN) {
            console.log('Fetching user info on game initialization...');
            // Показываем загрузку только если баланс еще не показан PHP
            if ($('[data-rel="menu-balance"] span').html() !== '...') {
                $('[data-rel="menu-balance"] span').html('...');
            }
            
            this.fetchUserInfo().then(userInfo => {
                if (userInfo) {
                    console.log('User info loaded successfully, real mode activated');
                    // Обновляем интерфейс с данными пользователя
                    this.updateBalanceDisplay();
                    this.updateQuickBets(SETTINGS.currency);
                } else {
                    console.log('Failed to fetch user info - falling back to demo mode');
                    this.setupDemoMode('default');
                }
            });
        } else {
            // console.log('No access token - using demo mode');
            // this.setupDemoMode('default');
        }
        
        // Initialize API mode
        this.gameId = null;
        console.log('API mode initialized');

        // Инициализируем WebSocket подключение (DISABLED FOR API MODE)
        this.ws = null;
        this.isWebSocketConnected = false;
        this.reconnectAttempts = 0;
        
        // Подключаемся к WebSocket серверу
        // this.connectWebSocket();
        
        // Запускаем периодическое получение ловушек от WebSocket
        // this.startWebSocketTrapPolling();
    }

    // API Helper
    async callApi(endpoint, method, body = {}) {
        if (!window.API_TOKEN) {
            console.error('No API Token found');
            return null;
        }

        try {
            const response = await fetch(`/api/chicken/${endpoint}`, {
                method: method,
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${window.API_TOKEN}`
                },
                body: JSON.stringify(body)
            });

            const data = await response.json();
            return data;
        } catch (error) {
            console.error(`API Error (${endpoint}):`, error);
            return null;
        }
    }
    
    // Метод для получения адаптивного масштаба курицы
    getChickenScale() {
        // Базовая формула масштабирования
        var baseScale = (SETTINGS.segw / (250/100) * (75/100) / 100);
        
        // Определяем, является ли устройство мобильным
        var isMobile = window.innerWidth <= 768 || /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
        
        if (isMobile) {
            // Для мобильных устройств используем более мягкое уменьшение
            var mobileScale = baseScale * 0.9; // Apenas 10% de redução para mobile
            
            // На очень маленьких экранах делаем еще меньше, но не слишком
            if (window.innerWidth <= 480) {
                mobileScale = baseScale * 1.0; // Sem redução para telas muito pequenas
            }
            
            // На средних мобильных экранах используем почти полный размер
            if (window.innerWidth > 480 && window.innerWidth <= 768) {
                mobileScale = baseScale * 0.95; // Apenas 5% de redução para médios
            }
            
            console.log(`Mobile device detected. Base scale: ${baseScale.toFixed(3)}, Mobile scale: ${mobileScale.toFixed(3)}`);
            return mobileScale;
        } else {
            // Для десктопа используем базовую формулу
            console.log(`Desktop device. Scale: ${baseScale.toFixed(3)}`);
            return baseScale;
        }
    }
    
    // Метод для получения дефолтного баланса для страны
    getDefaultBalanceForCountry() {
        const country = window.GAME_CONFIG ? window.GAME_CONFIG.user_country : 'default';
        
        const countryBalances = {
            'Colombia': 250000,
            'Paraguay': 5000000,
            'Ecuador': 500,
            'Brazil': 2000,
            'Argentina': 15000,
            'Mexico': 10000,
            'Peru': 2000,
            'Chile': 500000,
            'Uruguay': 20000,
            'Bolivia': 3500,
            'Venezuela': 5000000,
            'Guyana': 100000,
            'Suriname': 200000,
            'Kenya': 100000,
            'Nigeria': 150000,
            'Zimbabwe': 5000,
            'Nigeria': 800000,
            'default': 500
        };
        
        const balance = countryBalances[country] || countryBalances['default'];
        console.log(`Default balance for ${country}: ${balance}`);
        return balance;
    }
    
    // Метод для подключения к WebSocket серверу
    connectWebSocket() {
        try {
            console.log('🔌 Connecting to WebSocket server...');
            // Определяем URL WebSocket сервера
            const protocol = window.location.protocol === 'https:' ? 'wss:' : 'ws:';
            const host = window.location.hostname;
            
            // Определяем URL в зависимости от окружения
            let wsUrl;
            if (host === 'chicken.valor-games.co' || host.includes('valor-games.co')) {
                wsUrl = "wss://chicken.valor-games.co/ws/";
            } else if (host === 'localhost' || host === '127.0.0.1') {
                wsUrl = "ws://localhost:8081/ws/";
            } else {
                // Для других хостов используем динамический URL
                wsUrl = `${protocol}//${host}:8081/ws/`;
            }
            
            console.log('Connecting to WebSocket:', wsUrl);
            this.ws = new WebSocket(wsUrl);
            
            this.ws.onopen = () => {
                console.log('✅ Connected to WebSocket server');
                this.isWebSocketConnected = true;
                this.reconnectAttempts = 0;
                
                // Устанавливаем уровень по умолчанию
                this.setWebSocketLevel(this.cur_lvl);
                
                // Запрашиваем последние ловушки со всех уровней
                this.requestLastTraps();
                
                // Запрашиваем ловушки для текущего уровня
                this.requestWebSocketTraps();
            };

            this.ws.onmessage = (event) => {
                try {
                    const data = JSON.parse(event.data);
                    console.log('📨 WebSocket message received:', data);
                    
                    if (data.type === 'traps') {
                        this.handleWebSocketTrapsData(data);
                    } else if (data.type === 'traps_all_levels') {
                        this.handleWebSocketAllLevelsData(data);
                    }
                } catch (error) {
                    console.error('❌ Error parsing WebSocket message:', error);
                }
            };

            this.ws.onclose = () => {
                console.log('📱 Disconnected from WebSocket server');
                this.isWebSocketConnected = false;
                this.attemptWebSocketReconnect();
            };

            this.ws.onerror = (error) => {
                console.error('❌ WebSocket connection error:', error);
            };

        } catch (error) {
            console.error('❌ Failed to connect to WebSocket:', error);
            this.attemptWebSocketReconnect();
        }
    }

    // Метод для переподключения к WebSocket
    attemptWebSocketReconnect() {
        if (this.reconnectAttempts < 5) {
            this.reconnectAttempts++;
            console.log(`🔄 Attempting to reconnect (${this.reconnectAttempts}/5)...`);
            
            setTimeout(() => {
                this.connectWebSocket();
            }, 3000);
        } else {
            console.log('❌ Max reconnection attempts reached');
        }
    }

    // Метод для установки уровня в WebSocket
    setWebSocketLevel(level) {
        this.cur_lvl = level;
        if (this.isWebSocketConnected && this.ws && this.ws.readyState === WebSocket.OPEN) {
            this.sendWebSocketMessage({
                type: 'set_level',
                level: level
            });
        }
    }

    // Метод для запроса последних ловушек со всех уровней
    requestLastTraps() {
        if (this.isWebSocketConnected && this.ws && this.ws.readyState === WebSocket.OPEN) {
            this.sendWebSocketMessage({
                type: 'get_last_traps'
            });
        }
    }

    // Метод для запроса ловушек от WebSocket
    requestWebSocketTraps() {
        if (this.isWebSocketConnected && this.ws && this.ws.readyState === WebSocket.OPEN) {
            this.sendWebSocketMessage({
                type: 'request_traps'
            });
        }
    }

    // Метод для отправки сообщения в WebSocket
    sendWebSocketMessage(data) {
        if (this.isWebSocketConnected && this.ws && this.ws.readyState === WebSocket.OPEN) {
            this.ws.send(JSON.stringify(data));
        } else {
            console.error('❌ WebSocket not connected, cannot send message');
        }
    }

    // Метод для обработки данных ловушек от WebSocket
    handleWebSocketTrapsData(data) {
        console.log('🎯 Traps data received:', data);
        console.log('Current level in game:', this.cur_lvl);
        console.log('Level from WebSocket:', data.level);
        console.log('Current game status:', this.cur_status);
        
        // Если игра активна, НЕ обновляем данные - сохраняем состояние игры
        if (this.cur_status === 'game') {
            console.log('Game is active, ignoring WebSocket updates to preserve game state');
            return;
        }
        
        // Обновляем ловушки только если игра не активна
        if (data.traps && data.traps.length > 0) {
            if (this.cur_status === 'loading' || this.cur_status === 'ready') {
            this.traps = data.traps;
            this.localTraps = data.traps;
            console.log('Traps updated from WebSocket:', this.traps);
            } else {
                console.log('Game is active, not updating traps. Current status:', this.cur_status);
                console.log('Ignoring new traps:', data.traps);
            }
        }
        
        // Обновляем коэффициенты из sectors данных
        if (data.sectors && data.sectors.length > 0) {
            console.log('Processing sectors data from WebSocket:', data.sectors);
            this.websocketCoefficients = {};
            
            data.sectors.forEach(sector => {
                // sector.position это индекс массива (0-based)
                this.websocketCoefficients[sector.position] = sector.coefficient;
                console.log(`Sector ${sector.position + 1}: coefficient ${sector.coefficient}, isTrap: ${sector.isTrap}`);
            });
            
            console.log('WebSocket coefficients saved:', this.websocketCoefficients);
            console.log('Coefficients array:', Object.values(this.websocketCoefficients));
            
            // Пересоздаем доску только если игра не активна
            if (this.cur_status === 'loading' || this.cur_status === 'ready') {
            console.log('Recreating board with updated WebSocket coefficients...');
            this.createBoard();
            } else {
                console.log('Game is active, not recreating board. Current status:', this.cur_status);
            }
        } else {
            console.log('No sectors data received from WebSocket');
        }
        
        // Принудительно обновляем уровень если он изменился
        if (data.level && data.level !== this.cur_lvl) {
            console.log('Level changed from', this.cur_lvl, 'to', data.level);
            this.cur_lvl = data.level;
        }
    }

    // Метод для обработки данных всех уровней от WebSocket
    handleWebSocketAllLevelsData(data) {
        console.log('🎯 All levels traps data received:', data);
        this.updateAllLevelsTrapsFromWebSocket(data.traps);
    }

    // Метод для периодического получения ловушек от WebSocket
    startWebSocketTrapPolling() {
        console.log('Starting WebSocket trap polling...');
        
        // Сервер автоматически отправляет новые ловушки каждые 15 секунд
        // если нет активных игр, поэтому клиенту не нужно их запрашивать
        console.log('Relying on server automatic broadcasts instead of polling');
        
        // Оставляем интервал для возможных будущих нужд, но не запрашиваем ловушки
        this.trapPollingInterval = setInterval(() => {
            if (this.isWebSocketConnected) {
                console.log('WebSocket connection is active, waiting for server broadcasts...');
            } else {
                console.log('WebSocket not connected');
            }
        }, 15000); // 15 секунд
        
        // Также запрашиваем ловушки при смене уровня
        this.originalSetLevel = this.setLevel;
        this.setLevel = (level) => {
            console.log('=== SETLEVEL CALLED ===');
            console.log('Level changed to:', level);
            console.log('Previous level:', this.cur_lvl);
            this.cur_lvl = level;
            
            // Очищаем существующие WebSocket данные для нового уровня
            this.websocketCoefficients = {};
            this.traps = [];
            this.localTraps = [];
            this.pendingWebSocketData = null;
            console.log('Cleared old data for new level');
            
            // НЕ устанавливаем локальные коэффициенты - ждем WebSocket
            
            if (this.isWebSocketConnected) {
                console.log('WebSocket connected, requesting traps for level:', level);
                this.setWebSocketLevel(level);
                this.requestWebSocketTraps();
            } else {
                // Если WebSocket не подключен, ждем подключения
                console.log('WebSocket not connected, waiting for connection for level:', level);
                this.waitForWebSocketConnection();
            }
            
            // Обновляем активные классы для radio кнопок
            $('input[name="difficulity"]').each(function(){
                var $label = $(this).closest('label');
                $label.removeClass('active selected');
                console.log('setLevel: Removed active classes from:', $label.find('span').text());
            });
            var $selectedLabel = $(`input[name="difficulity"][value="${level}"]`).closest('label');
            $selectedLabel.addClass('active selected');
            console.log('setLevel: Added active classes to:', $selectedLabel.find('span').text());
            console.log('Radio button active classes updated for level:', level);
            
            // Дополнительно принудительно сбрасываем все классы и устанавливаем только нужный
            $('input[name="difficulity"]').prop('checked', false);
            $(`input[name="difficulity"][value="${level}"]`).prop('checked', true);
            
            // Убираем все активные классы со всех лейблов - более агрессивно
            $('#dificulity .radio_buttons label').removeClass('active selected');
            $('#dificulity .radio_buttons label span').css({
                'background': 'transparent',
                'color': 'rgb(142, 143, 154)'
            });
            
            // Добавляем активные классы только к выбранному лейблу
            var $selectedLabel = $(`input[name="difficulity"][value="${level}"]`).closest('label');
            $selectedLabel.addClass('active selected');
            $selectedLabel.find('span').css({
                'background': 'rgb(95, 97, 113)',
                'color': 'rgb(255, 255, 255)'
            });
            
            console.log('Force updated radio button states for level:', level);
            console.log('Selected label:', $selectedLabel.find('span').text());
            
            // Не пересоздаем доску здесь - это будет сделано после получения данных от WebSocket
            console.log('Waiting for WebSocket data for level:', level);
            
            // НЕ используем локальные коэффициенты - ждем данные от WebSocket
            // Коэффициенты будут установлены в handleWebSocketTrapsData когда придут от сервера
            
            console.log('=== SETLEVEL COMPLETED ===');
        };
    }
    
    // Метод для принудительного сброса всех кнопок уровней
    resetAllLevelButtons() {
        console.log('=== RESETTING ALL LEVEL BUTTONS ===');
        
        // Сбрасываем все radio кнопки
        $('input[name="difficulity"]').prop('checked', false);
        
        // Убираем все активные классы
        $('#dificulity .radio_buttons label').removeClass('active selected');
        
        // Принудительно устанавливаем прозрачный фон для всех кнопок
        $('#dificulity .radio_buttons label span').css({
            'background': 'transparent',
            'color': 'rgb(142, 143, 154)'
        });
        
        // Устанавливаем только Easy как активную
        $('input[name="difficulity"][value="easy"]').prop('checked', true);
        $('input[name="difficulity"][value="easy"]').closest('label').addClass('active selected');
        $('input[name="difficulity"][value="easy"]').closest('label').find('span').css({
            'background': 'rgb(95, 97, 113)',
            'color': 'rgb(255, 255, 255)'
        });
        
        console.log('All level buttons reset, Easy set as active');
    }
    
    // Метод для инициализации кнопок уровней
    initializeLevelButtons() {
        console.log('=== INITIALIZING LEVEL BUTTONS ===');
        
        // Проверяем какие кнопки уровней существуют в DOM
        var levelSelectors = [
            '.level-btn',
            '[data-level]',
            '.difficulty-btn',
            '.level-button',
            'button[data-level]',
            '.btn[data-level]'
        ];
        
        levelSelectors.forEach(function(selector) {
            var elements = $(selector);
            console.log(`Selector "${selector}": found ${elements.length} elements`);
            if (elements.length > 0) {
                elements.each(function(index) {
                    var level = $(this).data('level') || $(this).attr('data-level');
                    console.log(`  Element ${index}: level="${level}", text="${$(this).text()}"`);
                });
            }
        });
        
        // Устанавливаем активную radio кнопку для текущего уровня
        $('input[name="difficulity"]').prop('checked', false);
        
        // Снимаем активные классы со всех radio кнопок
        $('input[name="difficulity"]').each(function(){
            var $label = $(this).closest('label');
            $label.removeClass('active selected');
            console.log('Initialization: Removed active classes from:', $label.find('span').text());
        });
        
        var currentLevelRadio = $(`input[name="difficulity"][value="${this.cur_lvl}"]`);
        if (currentLevelRadio.length > 0) {
            currentLevelRadio.prop('checked', true);
            // Добавляем активный класс к выбранной кнопке
            var $selectedLabel = currentLevelRadio.closest('label');
            $selectedLabel.addClass('active selected');
            console.log('Initialization: Added active classes to:', $selectedLabel.find('span').text());
            console.log('Active level radio button set for:', this.cur_lvl);
        } else {
            console.log('No level radio button found for level:', this.cur_lvl);
        }
        
        // Также обновляем обычные кнопки если они есть
        $('.level-btn').removeClass('selected').css({
            'background': '#333',
            'color': '#fff',
            'border-color': '#666'
        });
        
        var currentLevelBtn = $(`.level-btn[data-level="${this.cur_lvl}"]`);
        if (currentLevelBtn.length > 0) {
            currentLevelBtn.addClass('selected').css({
                'background': '#00ff88',
                'color': '#000',
                'border-color': '#00ff88'
            });
            console.log('Active level button set for:', this.cur_lvl);
        }
        
        console.log('Level buttons initialized for level:', this.cur_lvl);
        console.log('=== LEVEL BUTTONS INITIALIZATION COMPLETED ===');
    }
    
    // Метод для остановки периодического получения ловушек
    stopWebSocketTrapPolling() {
        if (this.trapPollingInterval) {
            clearInterval(this.trapPollingInterval);
            this.trapPollingInterval = null;
        }
    }
    
    // Метод для настройки демо режима
    setupDemoMode(country) {
        console.log('=== SETUP DEMO MODE START ===');
        console.log('Country parameter:', country);
        
        const demoConfigs = {
            'Colombia': {
                currency: 'COP',
                balance: 250000,
                quick_bets: [2500, 5000, 10000, 35000],
                min_bet: 100,
                max_bet: 70000,
                default_bet: 2500
            },
            'Paraguay': {
                currency: 'PYG',
                balance: 5000000,
                quick_bets: [50000, 100000, 200000, 700000],
                min_bet: 1000,
                max_bet: 1500000,
                default_bet: 50000
            },
            'Ecuador': {
                currency: 'USD',
                balance: 500,
                quick_bets: [0.5, 1, 2, 7],
                min_bet: 0.5,
                max_bet: 150,
                default_bet: 0.5
            },
            'Brazil': {
                currency: 'BRL',
                balance: 2000,
                quick_bets: [20, 50, 100, 350],
                min_bet: 10,
                max_bet: 1000,
                default_bet: 20
            },
            'Argentina': {
                currency: 'ARS',
                balance: 15000,
                quick_bets: [150, 300, 600, 2100],
                min_bet: 50,
                max_bet: 5000,
                default_bet: 150
            },
            'Mexico': {
                currency: 'MXN',
                balance: 10000,
                quick_bets: [100, 200, 400, 1400],
                min_bet: 50,
                max_bet: 3000,
                default_bet: 100
            },
            'Peru': {
                currency: 'PEN',
                balance: 2000,
                quick_bets: [20, 50, 100, 350],
                min_bet: 10,
                max_bet: 1000,
                default_bet: 20
            },
            'Chile': {
                currency: 'CLP',
                balance: 500000,
                quick_bets: [5000, 10000, 20000, 70000],
                min_bet: 1000,
                max_bet: 200000,
                default_bet: 5000
            },
            'Uruguay': {
                currency: 'UYU',
                balance: 20000,
                quick_bets: [200, 400, 800, 2800],
                min_bet: 100,
                max_bet: 10000,
                default_bet: 200
            },
            'Bolivia': {
                currency: 'BOB',
                balance: 3500,
                quick_bets: [35, 70, 140, 490],
                min_bet: 10,
                max_bet: 2000,
                default_bet: 35
            },
            'Venezuela': {
                currency: 'VES',
                balance: 5000000,
                quick_bets: [50000, 100000, 200000, 700000],
                min_bet: 10000,
                max_bet: 2000000,
                default_bet: 50000
            },
            'Guyana': {
                currency: 'GYD',
                balance: 100000,
                quick_bets: [1000, 2000, 4000, 14000],
                min_bet: 500,
                max_bet: 50000,
                default_bet: 1000
            },
            'Suriname': {
                currency: 'SRD',
                balance: 200000,
                quick_bets: [2000, 4000, 8000, 28000],
                min_bet: 1000,
                max_bet: 100000,
                default_bet: 2000
            },
            'Kenya': {
                currency: 'KES',
                balance: 10000,
                quick_bets: [150, 300, 1000, 5000],
                min_bet: 500,
                max_bet: 10000,
                default_bet: 150
            },
            'Nigeria': {
                currency: 'NGN',
                balance: 150000,
                quick_bets: [1500, 3000, 10000, 20000],
                min_bet: 1500,
                max_bet: 50000,
                default_bet: 1500
            },
            'Zimbabwe': {
                currency: 'ZWL',
                balance: 5000,
                quick_bets: [500, 1000, 5000, 10000],
                min_bet: 500,
                max_bet: 100000,
                default_bet: 500
            },
            'default': {
                currency: 'USD',
                balance: 500,
                quick_bets: [0.5, 1, 2, 7],
                min_bet: 0.5,
                max_bet: 150,
                default_bet: 0.5
            }
        };
        
        const config = demoConfigs[country] || demoConfigs['default'];
        console.log('Selected config:', config);
        
        // Устанавливаем демо конфигурацию
        window.GAME_CONFIG = {
            is_real_mode: false,
            is_demo_mode: true,
            user_country: country || 'default',
            currency_symbol: config.currency,
            initial_balance: config.balance,
            demo_config: config
        };
        
        // Устанавливаем глобальную переменную для быстрой проверки
        window.IS_DEMO_MODE = true;
        
        // Устанавливаем баланс
        this.balance = config.balance;
        console.log('Balance set in setupDemoMode:', this.balance);
        
        // Обновляем настройки игры
        SETTINGS.currency = config.currency;
        SETTINGS.min_bet = config.min_bet;
        SETTINGS.max_bet = config.max_bet;
        
        // ВАЖНО: Обновляем валюту в экземпляре игры
        this.currency = config.currency;
        
        console.log('Demo mode configured:', {
            country: country,
            currency: config.currency,
            this_currency: this.currency,
            balance: config.balance,
            config: config,
            GAME_CONFIG: window.GAME_CONFIG,
            SETTINGS_currency: SETTINGS.currency
        });
        
        // Обновляем интерфейс
        this.updateDemoInterface(config);
        
        // Принудительно обновляем баланс в интерфейсе
        this.updateBalanceDisplay();
        
        // Create board for demo mode
        this.create();
        
        console.log('=== SETUP DEMO MODE END ===');
    }
    
    // Метод для обновления интерфейса в демо режиме
    updateDemoInterface(config) {
        console.log('=== UPDATE DEMO INTERFACE START ===');
        console.log('Config:', config);
        
        // Обновляем отображение баланса
        var formattedBalance = this.formatBalance(config.balance, config.currency);
        console.log('Formatted balance:', formattedBalance);
        // Не обновляем здесь, чтобы избежать дублирования с updateBalanceDisplay
        
        // Обновляем SVG символы валюты
        $('svg use').attr('xlink:href', '/assets/images/chicken/currency.svg#' + config.currency);
        console.log('Updated currency SVG to:', config.currency);
        
        // Обновляем быстрые ставки
        this.updateQuickBets(config.currency, config.quick_bets);
        
        // Обновляем кнопки MIN/MAX
        this.updateMinMaxButtons(config);
        
        // Обновляем поле ставки
        $('#bet_size').val(config.default_bet);
        
        console.log('Demo interface updated for currency:', config.currency);
        console.log('=== UPDATE DEMO INTERFACE END ===');
    }
    
    // Метод для форматирования баланса в зависимости от валюты
    formatBalance(balance, currency) {
        if (currency === 'USD') {
            return balance.toFixed(2);
        } else {
            // Для COP и PYG показываем полное число без десятичных знаков
            return balance.toLocaleString('en-US', { 
                minimumFractionDigits: 0, 
                maximumFractionDigits: 0,
                useGrouping: true
            });
        }
    }
    
    // Метод для обновления отображения баланса с правильным форматированием
    updateBalanceDisplay() {
        // Принудительно синхронизируем валюту
        this.currency = SETTINGS.currency;
        var currency = SETTINGS.currency;
        var formattedBalance = this.formatBalance(this.balance, currency);
        
        // Проверяем лимиты баланса для автоматической перезагрузки
        this.checkBalanceLimit(currency, this.balance);
        
        // Обновляем только если значение изменилось
        var currentDisplay = $('[data-rel="menu-balance"] span').html();
        if (currentDisplay !== formattedBalance) {
            console.log('Updating balance display:', {
                old: currentDisplay,
                new: formattedBalance,
                balance: this.balance,
                currency: currency,
                this_currency: this.currency,
                is_demo: window.IS_DEMO_MODE
            });
            $('[data-rel="menu-balance"] span').html(formattedBalance);
        }
    }
    
    // Метод для проверки лимита баланса и автоматической перезагрузки страницы
    checkBalanceLimit(currency, balance) {
        // Определяем лимиты для разных валют
        const balanceLimits = {
            'COP': 40000000,  // 40 миллионов песо (Колумбия)
            'USD': 12000,     // 12000$ (Эквадор)
            'PYG': 120000000  // 120 миллионов гуарани (Парагвай)
        };
        
        // Проверяем, достиг ли баланс лимита для текущей валюты
        if (balanceLimits[currency] && balance >= balanceLimits[currency]) {
            console.log(`⚠️ Balance limit reached for ${currency}: ${balance} >= ${balanceLimits[currency]}`);
            console.log('Sending postMessage to parent window to reload page...');
            
            // Отправляем сообщение родительскому окну для перезагрузки
            try {
                window.top.postMessage({
                    type: 'reloadPage',
                    reason: 'balanceLimit',
                    currency: currency,
                    balance: balance,
                    limit: balanceLimits[currency]
                }, '*');
                console.log('PostMessage sent to parent window');
            } catch (error) {
                console.error('Error sending postMessage:', error);
                // Если postMessage не работает, перезагружаем напрямую
                setTimeout(function() {
                    window.location.reload();
                }, 1000);
            }
        }
    }
    
    // Метод для обновления настроек из конфигурации (упрощенный)
    updateSettingsFromConfig() {
        if (window.GAME_CONFIG && window.GAME_CONFIG.is_real_mode) {
            // Реальный режим - настройки уже установлены в fetchUserInfo
            console.log('Real mode - settings already configured from API');
        } else {
            // Демо режим - используем дефолтные настройки USD
            console.log('Demo mode - using default USD settings');
            SETTINGS.min_bet = 0.5;
            SETTINGS.max_bet = 150;
            SETTINGS.currency = 'USD';
            this.currency = 'USD';
            this.updateMinMaxButtons();
            this.updateQuickBets('USD');
        }
    }
    
    // Метод для получения конфигурации ставок для страны
    getBetConfigForCountry(country) {
        const betConfigs = {
            'Colombia': {
                currency: 'COP',
                quick_bets: [2500, 5000, 10000, 35000],
                min_bet: 1000,
                max_bet: 700000,
                default_bet: 2500
            },
            'Paraguay': {
                currency: 'PYG',
                quick_bets: [5000, 10000, 20000, 70000],
                min_bet: 1000,
                max_bet: 1500000,
                default_bet: 5000
            },
            'Nigeria': {
                currency: 'NGN',
                quick_bets: [800, 1600, 3200, 11200],
                min_bet: 400,
                max_bet: 240000,
                default_bet: 800
            },
            'default': {
                currency: 'USD',
                quick_bets: [0.5, 1, 2, 7],
                min_bet: 0.5,
                max_bet: 150,
                default_bet: 0.5
            },
            'Kenya': {
                currency: 'KES',
                balance: 10000,
                quick_bets: [150, 300, 1000, 5000],
                min_bet: 500,
                max_bet: 10000,
                default_bet: 150
            },
            'Nigeria': {
                currency: 'NGN',
                balance: 150000,
                quick_bets: [1500, 3000, 10000, 20000],
                min_bet: 1500,
                max_bet: 50000,
                default_bet: 1500
            },
            'Zimbabwe': {
                currency: 'ZWL',
                balance: 5000,
                quick_bets: [500, 1000, 5000, 10000],
                min_bet: 500,
                max_bet: 100000,
                default_bet: 500
            },
        };
        
        return betConfigs[country] || betConfigs['default'];
    }
    
    // Метод для обновления быстрых ставок
    updateQuickBets(currency, customQuickBets = null) {
        var country = window.GAME_CONFIG ? window.GAME_CONFIG.user_country : 'default';
        var betConfig = this.getBetConfigForCountry(country);
        var quickBets = customQuickBets || betConfig.quick_bets;
        
        console.log('Updating quick bets for country:', country, 'currency:', currency, 'bets:', quickBets);
        
        // Обновляем быстрые ставки в интерфейсе
        $('.basic_radio').empty();
        
        quickBets.forEach((betValue, index) => {
            var formattedValue = this.formatBetValue(betValue, currency);
            var quickBetHtml = `
                <label class="gray_input">
                    <input type="radio" name="bet_value" value="${betValue}" autocomplete="off" ${index === 0 ? 'checked' : ''}>
                    <span>${formattedValue}</span>
                    <svg width="18" height="18" viewBox="0 0 18 18" style="fill: rgb(255, 255, 255);">
                        <use xlink:href="/assets/images/chicken/currency.svg#${currency}"></use>
                    </svg>
                </label>
            `;
            $('.basic_radio').append(quickBetHtml);
        });
        
        // Переустанавливаем обработчики событий для новых быстрых ставок
        this.bindQuickBetHandlers();
        
        console.log('Quick bets updated successfully');
    }
    
    // Метод для форматирования значения ставки
    formatBetValue(value, currency) {
        if (currency === 'USD') {
            return value.toFixed(2);
        } else {
            // Для COP и PYG показываем полное число без десятичных знаков
            return value.toLocaleString('en-US', { 
                minimumFractionDigits: 0, 
                maximumFractionDigits: 0,
                useGrouping: true
            });
        }
    }
    
    // Метод для установки обработчиков быстрых ставок
    bindQuickBetHandlers() {
        $('.basic_radio input[name="bet_value"]').off().on('change', function(){
            if( GAME.cur_status == 'loading' ){
                if( SETTINGS.volume.sound ){ SOUNDS.button.play(); } 
                var $self=$(this); 
                var $val = parseFloat($self.val());
                $('#bet_size').val( $val );
                console.log('Quick bet selected:', $val);
            }
        });
    }
    
    // Метод для обновления кнопок MIN/MAX
    updateMinMaxButtons(customConfig = null) {
        // Получаем значения из API или data-атрибутов HTML
        var country = window.GAME_CONFIG ? window.GAME_CONFIG.user_country : 'default';
        var betConfig = customConfig || this.getBetConfigForCountry(country);
        
        var minBet = betConfig.min_bet;
        var maxBet = betConfig.max_bet;
        var defaultBet = betConfig.default_bet;
        
        // Обновляем data-атрибуты с новыми значениями
        $('#bet_size').attr('data-min-bet', minBet);
        $('#bet_size').attr('data-max-bet', maxBet);
        $('#bet_size').attr('data-default-bet', defaultBet);
        
        // Обновляем поле ввода ставки правильным значением по умолчанию
        $('#bet_size').val(defaultBet);
        
        // Обновляем обработчики кнопок с новыми значениями
        $('.bet_value_wrapper button[data-rel="min"]').off('click').on('click', function() {
            if (GAME.cur_status == 'loading') {
                if (SETTINGS.volume.sound) SOUNDS.button.play();
                $('#bet_size').val(minBet);
                console.log('MIN button clicked, setting bet to:', minBet);
            }
        });
        
        $('.bet_value_wrapper button[data-rel="max"]').off('click').on('click', function() {
            if (GAME.cur_status == 'loading') {
                if (SETTINGS.volume.sound) SOUNDS.button.play();
                var finalMaxBet = Math.min(maxBet, GAME.balance);
                $('#bet_size').val(finalMaxBet);
                console.log('MAX button clicked, setting bet to:', finalMaxBet);
            }
        });
        
        console.log('Min/Max buttons updated with values from HTML:', {
            min: minBet,
            max: maxBet,
            default: defaultBet,
            country: window.GAME_CONFIG ? window.GAME_CONFIG.user_country : 'unknown'
        });
    } 
    // Отключено - используем только WebSocket ловушки
    generateLocalTraps() {
        console.log('Local trap generation disabled - using only WebSocket traps');
        // Не генерируем локальные ловушки
            return;
        }
        
    // Генерируем сложные ловушки для Hard, Medium и Hardcore уровней
    generateFallbackTraps() {
        console.log('Generating difficult traps for level:', this.cur_lvl);
        
        // Генерируем ловушки только для сложных уровней
        if (['medium', 'hard', 'hardcore'].includes(this.cur_lvl)) {
            var chanceSettings = SETTINGS.chance[this.cur_lvl];
            var traps = [];
            
            // Генерируем основную ловушку
            var mainTrap = Math.ceil(Math.random() * (chanceSettings[1] - chanceSettings[0] + 1)) + chanceSettings[0] - 1;
            traps.push(mainTrap);
            
            // Для Hard добавляем дополнительную ловушку (50% шанс)
            if (this.cur_lvl === 'hard' && Math.random() < 0.5) {
                var secondTrap = Math.ceil(Math.random() * (chanceSettings[1] - chanceSettings[0] + 1)) + chanceSettings[0] - 1;
                // Убеждаемся, что вторая ловушка не совпадает с первой
                while (secondTrap === mainTrap) {
                    secondTrap = Math.ceil(Math.random() * (chanceSettings[1] - chanceSettings[0] + 1)) + chanceSettings[0] - 1;
                }
                traps.push(secondTrap);
            }
            
            // Для Hardcore добавляем вторую ловушку (70% шанс)
            if (this.cur_lvl === 'hardcore' && Math.random() < 0.7) {
                var secondTrap = Math.ceil(Math.random() * (chanceSettings[1] - chanceSettings[0] + 1)) + chanceSettings[0] - 1;
                // Убеждаемся, что вторая ловушка не совпадает с первой
                while (secondTrap === mainTrap) {
                    secondTrap = Math.ceil(Math.random() * (chanceSettings[1] - chanceSettings[0] + 1)) + chanceSettings[0] - 1;
                }
                traps.push(secondTrap);
            }
            
            this.traps = traps;
            this.localTraps = traps;
            console.log(`Generated ${traps.length} traps for ${this.cur_lvl}:`, traps);
        } else {
            console.log('Easy level - no additional traps generated');
        }
    }
    
    
    waitForWebSocketConnection() {
        console.log('Waiting for WebSocket connection...');
        
        // Проверяем подключение каждые 500ms
        const checkConnection = () => {
            if (this.isWebSocketConnected) {
                console.log('WebSocket connected! Requesting traps...');
                this.requestWebSocketTraps();
            } else {
                // Продолжаем ждать подключения
                setTimeout(checkConnection, 500);
            }
        };
        
        checkConnection();
    }
    
    getCoefficientArray() {
        var level = this.cur_lvl || 'easy';
        return SETTINGS.cfs[level] || SETTINGS.cfs['easy'];
    }
    
    getCoefficient(step) {
        if (step < 0) step = 0;
        var arr = this.getCoefficientArray();
        return arr[step] || 0;
    }
    
    // Метод для правильного позиционирования курицы
    positionChicken() {
        // Ждем, пока DOM обновится
        setTimeout(() => {
            // Сначала удаляем все существующие элементы курицы
            $('#chick').remove();
            
            // Создаем новую курицу
            this.wrap.append(`<div id="chick" state="idle"><div class="inner"></div></div>`);
            
            const $chick = $('#chick');
            if ($chick.length) {
                // Получаем адаптивный масштаб
                var scale = this.getChickenScale();
                
                // Устанавливаем правильное позиционирование
                $chick.css({
                    'position': 'absolute',
                    'bottom': '50px',
                    'left': (SETTINGS.segw / 2) + 'px',
                    'z-index': '10'
                });
                
                // Применяем масштабирование
                $chick.find('.inner').css('transform', 'translateX(-50%) scale(' + scale + ')');
                
                // Убеждаемся, что курица видна и в правильном состоянии
                $chick.show().attr('state', 'idle');
                console.log('Chicken positioned at:', $chick.css('left'), $chick.css('bottom'), 'Scale:', scale);
            } else {
                console.error('Chicken element not found in DOM');
            }
        }, 100);
    }
    
    // Метод для очистки дубликатов курицы
    cleanupDuplicateChickens() {
        var $allChicks = $('#chick');
        if ($allChicks.length > 1) {
            console.warn('Found', $allChicks.length, 'chicken elements, removing duplicates');
            $allChicks.slice(1).remove(); // Удаляем все кроме первого
            return true; // Возвращаем true если были найдены дубликаты
        }
        return false; // Возвращаем false если дубликатов не было
    }
    
    create(){
        console.log('Creating game board (API Mode)...');
        this.traps = null;
        this.isMoving = false;
        this.wrap.html('').css('left', 0);
        
        $('#chick').remove();
        $('#fire').remove();
        
        this.createBoard();
        console.log('Game board creation completed');
    }
    createBoard() {
        console.log('=== CREATEBOARD CALLED (API Mode) ===');
        console.log('Current level:', this.cur_lvl);
        
        // Use static coefficients via getCoefficientArray
        var $arr = this.getCoefficientArray(); 
        
        this.stp = 0; // Reset step on new board
        this.alife = 0;
        this.win = 0;
        this.fire = 0;
        // Remove old chick and fire if present
        $('#chick').remove();
        $('#fire').remove();
        this.wrap.html('');
        this.wrap.append(`<div class="sector start" data-id="0">
                                <div class="breaks" breaks="3"></div>
                                <div class="breaks" breaks="2"></div>
                                <img src="/assets/images/chicken/arc.png" class="entry" alt="">
                                <div class="border"></div>
                            </div>`); 
        var flameSegments = [];
        console.log('Current traps array:', this.traps);
        console.log('Current localTraps array:', this.localTraps);
        
        // Используем WebSocket ловушки или генерируем локальные для сложных уровней
        if (this.traps && this.traps.length > 0) {
            flameSegments = this.traps;
            this.fire = this.traps[0];
            console.log('Using traps from WebSocket:', flameSegments);
        } else if (['medium', 'hard', 'hardcore'].includes(this.cur_lvl)) {
            // Генерируем локальные ловушки для сложных уровней
            this.generateFallbackTraps();
            if (this.traps && this.traps.length > 0) {
                flameSegments = this.traps;
                this.fire = this.traps[0];
                console.log('Using generated difficult traps:', flameSegments);
            } else {
                flameSegments = [];
                this.fire = 0;
                console.log('Failed to generate difficult traps');
            }
        } else {
            // Для Easy уровня создаем доску без ловушек
            flameSegments = [];
            this.fire = 0;
            console.log('Easy level - creating board without traps');
        }
        
        console.log('Fire position:', this.fire, 'Flame segments:', flameSegments);
        
        for( var $i=0; $i<$arr.length; $i++ ){
            // Determine if this sector is a flame - сектора нумеруются с 1, но массив с 0
            var sectorId = $i + 1;
            // Проверяем ловушки: flameSegments содержит позиции ловушек (1-based)
            var isFlame = flameSegments.includes(sectorId);
            var coeff = $arr[$i];
            console.log('Sector', sectorId, 'isFlame:', isFlame, 'coeff:', coeff, 'flameSegments:', flameSegments);
            this.wrap.append(`<div class="sector${ $i == $arr.length-1 ? ' finish' : ($i ? ' far' : '') }" data-id="${ $i+1 }"${ isFlame ? ' flame="1"' : '' } style="position: relative;">
                <div class="coincontainer" style="position: absolute; bottom: 45%; left: 0; width: 100%;">
                    ${$i == $arr.length-1 ? `
                        <img src="/assets/images/chicken/bet5.png" alt="" class="coin e">
                        <img src="/assets/images/chicken/bet6.png" alt="" class="coin f">
                        <img src="/assets/images/chicken/bet7.png" alt="" class="coin g">
                    ` : `
                        <img src="/assets/images/chicken/betbg.png" alt="" class="coinwrapper">
                        <img src="/assets/images/chicken/bet1.png" alt="" class="coin a" data-id="1">
                        <img src="/assets/images/chicken/bet2.png" alt="" class="coin b" data-id="2">
                        <img src="/assets/images/chicken/bet3.png" alt="" class="coin c" data-id="3">
                        <img src="/assets/images/chicken/bet4.png" alt="" class="coin d" data-id="4">
                    `}
                    <span>${ coeff }x</span>
                </div>
                ${$i == $arr.length-1 ? `
                    <div class="breaks" breaks="6"></div>
                    <div class="breaks" breaks="5"></div>
                    <img src="/assets/images/chicken/arc2.png" class="arc" alt="">
                    <img src="/assets/images/chicken/stand.png" class="cup" alt="">
                    <div class="finish_light"></div>
                    <img src="/assets/images/chicken/trigger.png" class="trigger" alt="">
                    <div class="flame"></div>
                    <div class="border"></div>
                ` : `
                    <div class="breaks" breaks="4"></div>
                    <div class="breaks" breaks="5"></div>
                    <div class="breaks"></div>
                    <img src="/assets/images/chicken/frame.png" class="frame" alt="">
                    <img src="/assets/images/chicken/trigger.png" class="trigger" alt="">
                    <div class="place_light"></div>
                    <div class="flame"></div>
                    <div class="border"></div>
                `}
            </div>`);
        }
        this.wrap.append(`<div class="sector closer" data-id="${ $arr.length+1 }">
                            <div class="border"></div>
                        </div>`); 

        // Курица уже создана в методе create(), не создаем повторно
        this.wrap.append(`<div id="fire"></div>`); 
        var $flame_x = document.querySelector('.sector[flame="1"]'); 
        $flame_x = $flame_x ? $flame_x.offsetLeft : 0; 
        $('#fire').css('left', $flame_x +'px')

        SETTINGS.segw = parseInt( $('#battlefield .sector').css('width') );
        
        // Убеждаемся, что курица правильно позиционирована
        this.positionChicken(); 

        var $scale = this.getChickenScale();
        $('#chick').css( 'left', ( SETTINGS.segw / 2 )+'px' );
        $('#chick .inner').css( 'transform', 'translateX(-50%) scale('+ $scale +')' ); 
        var $bottom = 50; 
        if( SETTINGS.w <= 1200 ){ $bottom = 35; }
        if( SETTINGS.w <= 1100 ){ $bottom = 30; }
        if( SETTINGS.w <= 1000 ){ $bottom = 25; }
        if( SETTINGS.w <= 900 ){ $bottom = 5; }
        if( SETTINGS.w <= 800 ){ $bottom = -15; }
        $('#chick').css('bottom', $bottom+'px');

        // Reset all sector classes
        $('.sector').removeClass('active complete dead win lose');
        // Set start sector as active
        $('.sector.start').addClass('active');

        $('.sector').each(function(){
            var $self = $(this); 
            var $id = $self.data('id');
            $('.breaks', $self).each(function(){
                var $br = $id ? ( Math.round( Math.random() * 12 ) + 4 ) : ( Math.round( Math.random() * 3 ) );
                $(this).attr('breaks', $br );
            });
        });
    }
    createFallback(){
        // Используем только WebSocket коэффициенты
        var $arr = this.getCoefficientArray(); 
        if ($arr.length === 0) {
            console.log('No WebSocket coefficients available for createFallback - skipping');
            return;
        } 
        this.wrap.append(`<div class="sector start" data-id="0">
                                <div class="breaks" breaks="3"></div>
                                <div class="breaks" breaks="2"></div>
                                <img src="/assets/images/chicken/arc.png" class="entry" alt="">
                                <div class="border"></div>
                            </div>`); 
        // Используем WebSocket ловушки или генерируем локальные для сложных уровней
        var flameSegments = [];
        if (this.traps && this.traps.length > 0) {
            flameSegments = this.traps;
            this.fire = this.traps[0];
            console.log('createFallback - Using traps from WebSocket:', flameSegments);
        } else if (['medium', 'hard', 'hardcore'].includes(this.cur_lvl)) {
            // Генерируем локальные ловушки для сложных уровней
            this.generateFallbackTraps();
            if (this.traps && this.traps.length > 0) {
                flameSegments = this.traps;
                this.fire = this.traps[0];
                console.log('createFallback - Using generated difficult traps:', flameSegments);
        } else {
                flameSegments = [];
                this.fire = 0;
                console.log('createFallback - Failed to generate difficult traps');
            }
        } else {
            // Для Easy уровня создаем доску без ловушек
            flameSegments = [];
            this.fire = 0;
            console.log('createFallback - Easy level, creating board without traps');
        }
        
        console.log('createFallback - Fire position:', this.fire, 'Flame segments:', flameSegments); 
        for( var $i=0; $i<$arr.length; $i++ ){
            // Проверяем, является ли этот сектор ловушкой
            var sectorId = $i + 1;
            var isFlame = flameSegments.includes(sectorId);
            
            if( $i == $arr.length - 1 ){
                this.wrap.append(`<div class="sector finish" data-id="${ $i+1 }" ${ isFlame ? 'flame="1"' : '' } style="position: relative;">
                                        <div class="coincontainer" style="position: absolute; bottom: 45%; left: 0; width: 100%;">
                                            <img src="/assets/images/chicken/bet5.png" alt="" class="coin e">
                                            <img src="/assets/images/chicken/bet6.png" alt="" class="coin f">
                                            <img src="/assets/images/chicken/bet7.png" alt="" class="coin g">
                                            <span>${ $arr[ $i ] }x</span>
                                        </div>
                                        <div class="breaks" breaks="6"></div>
                                        <div class="breaks" breaks="5"></div>
                                        <img src="/assets/images/chicken/arc2.png" class="arc" alt="">
                                        <img src="/assets/images/chicken/stand.png" class="cup" alt="">
                                        <div class="finish_light"></div>
                                        <img src="/assets/images/chicken/trigger.png" class="trigger" alt="">
                                        <div class="flame"></div>
                                        <div class="border"></div>
                                    </div>`);
            } 
            else {
                this.wrap.append(`<div class="sector ${ $i ? 'far' : '' }" data-id="${ $i+1 }" ${ isFlame ? 'flame="1"' : '' } style="position: relative;">
                                        <div class="breaks" breaks="4"></div>
                                        <div class="breaks" breaks="5"></div>
                                        <div class="coincontainer" style="position: absolute; bottom: 45%; left: 0; width: 100%;">
                                            <img src="/assets/images/chicken/betbg.png" alt="" class="coinwrapper">
                                            <img src="/assets/images/chicken/bet1.png" alt="" class="coin a" data-id="1">
                                            <img src="/assets/images/chicken/bet2.png" alt="" class="coin b" data-id="2">
                                            <img src="/assets/images/chicken/bet3.png" alt="" class="coin c" data-id="3">
                                            <img src="/assets/images/chicken/bet4.png" alt="" class="coin d" data-id="4"> 
                                            <span>${ $arr[ $i ] }x</span>
                                        </div>
                                        <div class="breaks"></div>
                                        <img src="/assets/images/chicken/frame.png" class="frame" alt="">
                                        <img src="/assets/images/chicken/trigger.png" class="trigger" alt="">
                                        <!--img src="/assets/images/chicken/lights2.png" class="lights" alt=""-->
                                        <div class="place_light"></div>
                                        <div class="flame"></div>
                                        <div class="border"></div>
                                    </div>`); 
            }
        } 
        this.wrap.append(`<div class="sector closer" data-id="${ $arr.length+1 }">
                            <div class="border"></div>
                        </div>`); 

        // Курица уже создана в методе create(), не создаем повторно
        this.wrap.append(`<div id="fire"></div>`); 
        var $flame_x = document.querySelector('.sector[flame="1"]'); 
        $flame_x = $flame_x ? $flame_x.offsetLeft : 0; 
        $('#fire').css('left', $flame_x +'px')

        SETTINGS.segw = parseInt( $('#battlefield .sector').css('width') ); 

        var $scale = this.getChickenScale();
        $('#chick').css( 'left', ( SETTINGS.segw / 2 )+'px' );
        $('#chick .inner').css( 'transform', 'translateX(-50%) scale('+ $scale +')' ); 
        var $bottom = 50; 
        if( SETTINGS.w <= 1200 ){ $bottom = 35; }
        if( SETTINGS.w <= 1100 ){ $bottom = 30; }
        if( SETTINGS.w <= 1000 ){ $bottom = 25; }
        if( SETTINGS.w <= 900 ){ $bottom = 5; }
        if( SETTINGS.w <= 800 ){ $bottom = -15; }
        $('#chick').css('bottom', $bottom+'px');

        $('.sector').each(function(){
            var $self = $(this); 
            var $id = $self.data('id');
            $('.breaks', $self).each(function(){
                var $br = $id ? ( Math.round( Math.random() * 12 ) + 4 ) : ( Math.round( Math.random() * 3 ) );
                $(this).attr('breaks', $br );
            });
        });
    }
    refreshBalance() {
        // Не обновляем баланс из DOM если активен демо режим
        if (window.IS_DEMO_MODE || (window.GAME_CONFIG && window.GAME_CONFIG.is_demo_mode)) {
            console.log('Demo mode active, skipping balance refresh from DOM');
            return this.balance;
        }
        
        // Дополнительная проверка - если баланс больше 1000, вероятно это демо режим
        if (this.balance && this.balance > 1000) {
            console.log('Large balance detected, likely demo mode - skipping refresh');
            return this.balance;
        }
        
        const balanceElement = $('[data-rel="menu-balance"] span');
        const balanceText = balanceElement.length > 0 ? balanceElement.html() : '0';
        this.balance = parseFloat(balanceText) || 0;
        console.log('Balance refreshed from DOM:', this.balance);
        return this.balance;
    }
    
    start(){ 
        console.log('GAME.start() called');
        // Refresh balance from DOM before starting (only if not in demo mode)
        if (!window.IS_DEMO_MODE && (!window.GAME_CONFIG || !window.GAME_CONFIG.is_demo_mode)) {
        this.refreshBalance();
        } else {
            console.log('Demo mode active, skipping balance refresh in start()');
        }
        this.current_bet = +$('#bet_size').val();
        
        // Проверяем и исправляем баланс в демо режиме
        if (window.IS_DEMO_MODE && (!this.balance || this.balance === undefined)) {
            console.log('Balance is undefined in demo mode, fixing...');
            this.balance = this.getDefaultBalanceForCountry();
            this.updateBalanceDisplay();
        }
        
        console.log('Current bet:', this.current_bet, 'Balance:', this.balance);
        if( this.current_bet && this.current_bet <= this.balance && this.current_bet > 0 ){ 
            console.log('Starting game...');
        // Проверяем, есть ли уже полученные WebSocket данные
        if (this.websocketCoefficients && Object.keys(this.websocketCoefficients).length > 0 && this.traps && this.traps.length > 0) {
            console.log('Using existing WebSocket data for new game');
            console.log('WebSocket coefficients:', this.websocketCoefficients);
            console.log('WebSocket traps:', this.traps);
        } else if (this.pendingWebSocketData) {
            console.log('Using pending WebSocket data for new game');
            this.updateTrapsFromWebSocket(this.pendingWebSocketData);
            this.pendingWebSocketData = null;
        } else {
            // Генерируем локальные трапы перед началом игры
            this.generateLocalTraps();
        }
            
            // Устанавливаем pendingGameStart для actuallyStartGame
            this.pendingGameStart = {
                current_bet: this.current_bet,
                balance: this.balance
            };
            
            this.actuallyStartGame();
        } else {
            console.log('Cannot start game: invalid bet or insufficient balance');
        }
    }
    
    async actuallyStartGame(){
        console.log('actuallyStartGame() called (API version)');
        if (!this.pendingGameStart) return;
        
        // 1. Call START API
        const response = await this.callApi('start', 'POST', {
            bet_amount: this.pendingGameStart.current_bet,
            difficulty: this.cur_lvl
        });

        if (!response || !response.success) {
            console.error('Failed to start game via API', response);
            return;
        }

        // 2. Initialize Game State
        this.gameId = response.game_id;
        this.current_bet = this.pendingGameStart.current_bet;
        this.balance = parseFloat(response.balance); 
        this.cur_status = 'game';
        this.stp = 0;
        this.alife = 1;
        CHICKEN.alife = 1;
        this.game_result_saved = false;
        
        this.updateBalanceDisplay();
        $('#close_bet').prop('disabled', false).show();
        // Hide "0 USD" initially
        $('#close_bet span').html((this.current_bet).toFixed(2) + ' ' + SETTINGS.currency).show();

        // 3. Bind click events (if not already bound)
        $('.sector').off().on('click', function(){ 
            if (GAME.cur_status === 'game' && GAME.alife && CHICKEN.alife) {
                GAME.move(); 
            }
        });

        this.pendingGameStart = null;
        this.positionChicken();

        // 4. Auto-move to first step (entry)
        setTimeout(() => {
            this.move();
        }, 100);
    } 
    async finish( $win, skipApi = false, apiResponse = null ){
        console.log('=== FINISH ===', { win: $win, skipApi, apiResponse });
        
        if (this.cur_status === 'finish') return;

        var $award = 0;
        
        if( $win ){ 
            this.win = 1; 
            $('#fire').addClass('active');
            
            if (!skipApi) {
                // Manual Cashout
                const response = await this.callApi('cashout', 'POST', { game_id: this.gameId });
                if (!response || !response.success) {
                    console.error("Cashout failed", response);
                    return; 
                }
                this.balance = parseFloat(response.balance);
                $award = parseFloat(response.win_amount);
            } else {
                // Already processed (Auto-win or passed from move)
                if (apiResponse) {
                    this.balance = parseFloat(apiResponse.balance || this.balance); // Update if available
                    $award = parseFloat(apiResponse.win_amount || apiResponse.potential_win);
                }
            }

            this.updateBalanceDisplay();
            if( SETTINGS.volume.sound ){ SOUNDS.win.play(); } 
            $('#win_modal').css('display', 'flex');
            
            var coeff = this.getCoefficient( Math.max(0, this.stp - 1) );
            $('#win_modal h3').html( 'x'+ coeff );
            $('#win_modal h4 span').html( $award.toFixed(2) );
        } 
        else {
            // Lose
            if( SETTINGS.volume.sound ){ SOUNDS.lose.play(); } 
            $('#close_bet').hide().prop('disabled', true);
        }
        
        this.cur_status = 'finish';
        this.gameId = null;
        
        setTimeout(function(){ 
            $('#overlay').hide(); 
            $('#win_modal').hide(); 
            GAME.updateBalanceDisplay();
            GAME.cur_status = "loading"; 
            GAME.create();  
        }, 2000); 
    }

    getCoefficientArray() {
        // Use static settings based on current level
        var level = this.cur_lvl || 'easy';
        return SETTINGS.cfs[level] || SETTINGS.cfs['easy'];
    }
    
    getCoefficient(step) {
        if (step < 0) step = 0;
        var arr = this.getCoefficientArray();
        return arr[step] || 0;
    }
    async move(){
        // Basic checks
        if (this.cur_status !== 'game' || !this.alife || !CHICKEN.alife || this.isMoving) return;
        
        var $chick = $('#chick'); 
        if (!$chick.length) return;
        var $state = $chick.attr('state'); 
        if( $state !== "idle" ) return;

        this.isMoving = true;
        
        // Call API
        const response = await this.callApi('play', 'POST', { game_id: this.gameId });
        
        if (!response || !response.success) {
            console.error('API Play Error', response);
            this.isMoving = false;
            return;
        }

        // --- Visual Update Logic ---
        var $cur_x = parseInt( $chick.css('left') );
        
        // Update local step from API response
        this.stp = response.step; 
        
        if( SETTINGS.volume.sound ){ SOUNDS.step.play(); }
        $chick.attr('state', "go");
        
        var $sector = $('.sector').eq(this.stp); // Sector we moved TO
        
        // Move chick visually
        var $nx = $cur_x + SETTINGS.segw + 'px';
        $chick.css('left', $nx);
        $chick.css('bottom', '50px');
        
        // Highlight sectors
        $('.sector').removeClass('active');
        if(this.stp > 0) $('.sector').eq(this.stp-1).addClass('complete');
        $sector.addClass('active');
        $sector.next().removeClass('far');
        $('.trigger', $sector).addClass('activated');
        
        // --- Handle Result ---
        if (response.status === 'lost') {
            // LOSE
            var $flame_x = $sector[0].offsetLeft;
            $('#fire').css('left', $flame_x + 'px').addClass('active');
            
            CHICKEN.alife = 0;
            $chick.attr('state', 'dead');
            $sector.removeClass('active').removeClass('complete').addClass('dead');
            $('.sector.finish').addClass('lose');
            
            // Show trap if available
            // if (response.trap_position) ...
            
            this.finish(false, true); // skipApi = true
        } else {
            // CONTINUE / WIN
            // Check if it's the finish line
            if( $sector.hasClass('finish') || response.status === 'won' ){
                $sector.addClass('win');
                // If status is won, backend auto-cashed out
                this.finish(true, true, response); 
            } else {
                // Just playing
                // Update cashout button value
                if (response.potential_win) {
                    $('#close_bet span').html(parseFloat(response.potential_win).toFixed(2) +' '+ SETTINGS.currency);
                }
            }
        }
        
        // Reset state after animation
        setTimeout(function(){
            if( CHICKEN.alife ){
                $chick.attr('state', 'idle');
            }
            GAME.isMoving = false;
            // Scroll battlefield if needed
            if(
                parseInt( $chick.css('left') ) > ( SETTINGS.w / 3 ) &&
                parseInt( $('#battlefield').css('left') ) > -( parseInt( $('#battlefield').css('width') ) - SETTINGS.w -SETTINGS.segw )
            ){
                var $field_x = parseInt( $('#battlefield').css('left') );
                var $nfx = $field_x - SETTINGS.segw +'px';
                $('#battlefield').css('left', $nfx);
            }
            
            GAME.update();
        }, 500);
    }
    getCurrentSector() { 
        var parent = document.querySelector('#battlefield'); 
        var player = document.querySelector('#chick'); 
        if (!player) return null;
        var sectors = document.querySelectorAll('#battlefield .sector'); 
        var playerRect = player.getBoundingClientRect();
        var parentRect = parent.getBoundingClientRect(); 
        var playerPosX = playerRect.left - parentRect.left;
        var sectorIndex = Math.floor( playerPosX / SETTINGS.segw ); 
        if( sectorIndex >= 0 && sectorIndex < sectors.length ){ 
            return sectorIndex; 
        } 
        else { return null; }
    } 
    random_str( length = 8 ){
        var chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
        var result = '';
        for (let i = 0; i < length; i++) {
            result += chars.charAt( Math.floor( Math.random() * chars.length ) );
        }
        return result;
    } 
    random_bet(){
        var $user_id = Math.ceil( Math.random() * 70 ); 
        var $user_name = this.random_str(); 
        var $user_win = Math.random() * 1000; 
        var $tmps = `<div class="inner">
                        <img src="/assets/images/chicken/users/av-${ $user_id }.png" alt="">
                        <h2>${ $user_name }</h2>
                        <h3>+${ $user_win.toFixed(2) } ${ SETTINGS.currency }</h3>
                    </div>`; 
        $('#random_bet').html( $tmps ).css('height', '40px'); 
        setTimeout( function(){ $('#random_bet').html('').css('height', '0px'); }, 6000 );
    }
 
    selectValue(mainArray, chanceArray) {
        var randomChance = Math.random();
        var limit = randomChance <= 0.1 ? chanceArray[1] : chanceArray[0];
        var filteredArray = mainArray.filter(value => value <= limit); 
        if( filteredArray.length === 0 ){
           return null;
        }
        var randomIndex = Math.floor( Math.random() * filteredArray.length );
        return randomIndex;
    } 
    selectValueHybridIndex(mainArray, chanceArray) {
        var limit = Math.random() <= 0.1 ? chanceArray[1] : chanceArray[0]; 
        var filteredIndices = mainArray
            .map( ( val, index) => ( { val, index } ) ) 
            .filter( ( { val, index } ) => val <= limit && ( index <= 1 || Math.random() < 0.3 ) )
            .map( ( { index } ) => index ); 
        if( filteredIndices.length === 0 ){
            var fallbackIndex = mainArray.findIndex( val => val <= limit );
            return fallbackIndex !== -1 ? fallbackIndex : null;
        } 
        console.log( filteredIndices[ Math.floor( Math.random() * filteredIndices.length ) ] );
        return filteredIndices[ Math.floor( Math.random() * filteredIndices.length ) ];
    }
    update(){
        switch( this.cur_status ){
            case 'loading': 
                $('#close_bet').css('display', 'none');
                $('#close_bet span').html( 0+' '+GAME.currency ).css('display', 'none');
                $('#start').html( window.LOCALIZATION.TEXT_BETS_WRAPPER_PLAY );
                $('#dificulity i').hide(); 
                break; 
            case 'game': 
                // Показываем кнопку CASH OUT только после первого шага курицы (stp > 0)
                if (this.stp > 0) {
                $('#close_bet').css('display', 'flex'); 
                var $award = ( this.current_bet * this.getCoefficient( Math.max(0, this.stp - 1) ) ); 
                    $award = $award ? $award.toFixed(2) : 0; 
                $('#close_bet span').html( $award +' '+ SETTINGS.currency ).css('display', 'flex');
                } else {
                    // Скрываем кнопку CASH OUT до первого шага
                    $('#close_bet').css('display', 'none');
                    $('#close_bet span').css('display', 'none');
                }
                $('#start').html( window.LOCALIZATION.TEXT_BETS_WRAPPER_GO ); 
                $('#dificulity i').show();
                break; 
            case 'finish': 
                $('#close_bet').css('display', 'none');
                $('#close_bet span').html( 0+' '+GAME.currency ).css('display', 'none'); 
                $('#start').html( window.LOCALIZATION.TEXT_BETS_WRAPPER_WAIT ); 
                $('#dificulity i').hide();
                break;  
        } 
        // Обновляем отображение баланса только если игра не в состоянии финиша
        if( this.cur_status !== 'finish' && this.balance !== undefined && this.balance !== null ){
            this.updateBalanceDisplay();
        } 

        var $sector = GAME.getCurrentSector(); 
        if( $sector > 1 ){ 
            $('.sector').eq( $sector-1 ).removeClass('active').addClass('complete'); 
        }
        $('.sector').each(function(){
            var $self=$(this);
            if( !$self.hasClass('flame') && !$self.hasClass('closer') && !$self.hasClass('start') && !$self.hasClass('active') ){
                var $start = Math.round( Math.random() * 1000 ) > 997 ? true : false; 
                if( $start ){
                    $self.addClass('flame');
                    setTimeout( function(){ $self.removeClass('flame') }, 1000 );
                }
            }
        });

        if( Math.round( Math.random() * 100 ) > 99 ){ 
            // Плавное изменение онлайн счетчика без анимации
            const currentOnline = parseInt($('#stats span.online').text().replace(/\D/g, '')) || 8768;
            // Более реалистичные изменения: ±10-500 от текущего значения
            const change = Math.round((Math.random() - 0.5) * 1000); // от -500 до +500
            const targetOnline = Math.max(1000, Math.min(15000, currentOnline + change)); // ограничиваем диапазон
            $('#stats span.online').html(window.LOCALIZATION.TEXT_LIVE_WINS_ONLINE + ': ' + targetOnline);
            GAME.random_bet(); 
        } 
    }
    bind(){
        $(document).ready(function(){ 
            // переключение звука 
            $('#switch_sound').off().on('change', function(){
                var $self=$(this); 
                var $val = $self.is(':checked'); 
                if( !$val ){ 
                    // Выключаем все звуки
                    SETTINGS.volume.sound = 0;
                    SOUNDS.button.volume(0);
                    SOUNDS.win.volume(0);
                    SOUNDS.lose.volume(0);
                    SOUNDS.step.volume(0);
                } 
                else { 
                    // Включаем все звуки
                    SETTINGS.volume.sound = 0.9;
                    SOUNDS.button.volume(0.9);
                    SOUNDS.win.volume(0.9);
                    SOUNDS.lose.volume(0.9);
                    SOUNDS.step.volume(0.9);
                } 
            });
            $('#switch_music').off().on('change', function(){
                var $self=$(this); 
                var $val = $self.is(':checked'); 
                if( !$val ){
                    SOUNDS.music.stop(); 
                    SETTINGS.volume.music = 0;
                    SOUNDS.music.volume(0);
                } 
                else {
                    SOUNDS.music.play(); 
                    SETTINGS.volume.music = 0.2;
                    SOUNDS.music.volume(0.2);
                } 
                
            });
            
            // переключение звука через кнопку в хедере
            $('#sound_switcher').off().on('click', function(){
                var $self=$(this); 
                $self.toggleClass('off'); 
                if( $self.hasClass('off') ){
                    // Выключаем ВСЕ звуки
                    SOUNDS.music.stop(); 
                    SETTINGS.volume.active = 0;
                    SETTINGS.volume.sound = 0;
                    SETTINGS.volume.music = 0;
                    SOUNDS.button.volume(0);
                    SOUNDS.win.volume(0);
                    SOUNDS.lose.volume(0);
                    SOUNDS.step.volume(0);
                    SOUNDS.music.volume(0);
                } 
                else {
                    // Включаем ВСЕ звуки
                    SETTINGS.volume.active = 1;
                    SETTINGS.volume.sound = 0.9;
                    SETTINGS.volume.music = 0.2;
                    SOUNDS.button.volume(0.9);
                    SOUNDS.win.volume(0.9);
                    SOUNDS.lose.volume(0.9);
                    SOUNDS.step.volume(0.9);
                    SOUNDS.music.volume(0.2);
                    SOUNDS.music.play(); 
                }
                // Сохраняем настройки
                $('body').attr('data-sound', SETTINGS.volume.active ? '1' : '0');
            });
            
            // Универсальные обработчики для кнопок уровней сложности
            // Пробуем разные селекторы для кнопок уровней
            var levelSelectors = [
                '.level-btn',
                '[data-level]',
                '.difficulty-btn',
                '.level-button',
                'button[data-level]',
                '.btn[data-level]'
            ];
            
            levelSelectors.forEach(function(selector) {
                $(selector).off().on('click', function(){
                    var level = $(this).data('level') || $(this).attr('data-level');
                    if (!level) return; // Пропускаем если нет уровня
                    
                    console.log('=== LEVEL BUTTON CLICKED ===');
                    console.log('Selector:', selector);
                    console.log('Level button clicked:', level);
                    console.log('GAME object exists:', !!GAME);
                    console.log('GAME.setLevel exists:', !!(GAME && GAME.setLevel));
                    
                    // Обновляем визуальное состояние кнопок
                    $(selector).removeClass('selected').css({
                        'background': '#333',
                        'color': '#fff',
                        'border-color': '#666'
                    });
                    $(this).addClass('selected').css({
                        'background': '#00ff88',
                        'color': '#000',
                        'border-color': '#00ff88'
                    });
                    console.log('Visual state updated for level:', level);
                    
                    // Вызываем setLevel для обновления коэффициентов
                    if (GAME && GAME.setLevel) {
                        console.log('Calling GAME.setLevel with level:', level);
                        GAME.setLevel(level);
                    } else {
                        console.log('ERROR: GAME or GAME.setLevel not available!');
                    }
                    console.log('=== LEVEL BUTTON CLICK COMPLETED ===');
                });
            });
            
            // Также добавляем обработчик через делегирование событий
            $(document).off('click.level').on('click.level', '[data-level]', function(){
                var level = $(this).data('level');
                console.log('=== DELEGATED LEVEL BUTTON CLICKED ===');
                console.log('Level:', level);
                console.log('Element:', this);
                
                if (GAME && GAME.setLevel) {
                    console.log('Calling GAME.setLevel via delegation with level:', level);
                    GAME.setLevel(level);
                }
            });
            
            // Добавляем универсальный обработчик для всех возможных кнопок уровней
            $(document).off('click.levelUniversal').on('click.levelUniversal', function(e){
                var $target = $(e.target);
                var level = null;
                
                // Проверяем разные способы определения уровня
                if ($target.hasClass('level-btn') || $target.hasClass('difficulty-btn')) {
                    level = $target.data('level') || $target.attr('data-level');
                } else if ($target.text().toLowerCase() === 'easy') {
                    level = 'easy';
                } else if ($target.text().toLowerCase() === 'medium') {
                    level = 'medium';
                } else if ($target.text().toLowerCase() === 'hard') {
                    level = 'hard';
                } else if ($target.text().toLowerCase() === 'hardcore') {
                    level = 'hardcore';
                }
                
                if (level && GAME && GAME.setLevel) {
                    console.log('=== UNIVERSAL LEVEL BUTTON CLICKED ===');
                    console.log('Level detected:', level);
                    console.log('Element:', e.target);
                    console.log('Element text:', $target.text());
                    console.log('Element classes:', $target.attr('class'));
                    
                    // Обновляем визуальное состояние кнопок
                    $('.level-btn, .difficulty-btn, [data-level]').removeClass('selected active').css({
                        'background': '#333',
                        'color': '#fff',
                        'border-color': '#666'
                    });
                    $target.addClass('selected active').css({
                        'background': '#00ff88',
                        'color': '#000',
                        'border-color': '#00ff88'
                    });
                    
                    console.log('Calling GAME.setLevel with level:', level);
                    GAME.setLevel(level);
                }
            });
            
            // Специальный обработчик для radio кнопок уровней
            $('input[name="difficulity"]').off().on('change', function(){
                var level = $(this).val();
                console.log('=== RADIO LEVEL BUTTON CHANGED ===');
                console.log('Level:', level);
                console.log('Element:', this);
                
                // Снимаем активные классы со всех radio кнопок
                $('input[name="difficulity"]').each(function(){
                    var $label = $(this).closest('label');
                    $label.removeClass('active selected');
                    console.log('Removed active classes from:', $label.find('span').text());
                });
                
                // Добавляем активный класс к выбранной кнопке
                var $selectedLabel = $(this).closest('label');
                $selectedLabel.addClass('active selected');
                console.log('Added active classes to:', $selectedLabel.find('span').text());
                
                console.log('Active classes updated for level:', level);
                
                if (GAME && GAME.setLevel) {
                    console.log('Calling GAME.setLevel with level:', level);
                    GAME.setLevel(level);
                }
            });
            
            // установка ставки в инпуте
            $('#bet_size').off().on('change', function(){ 
                if( GAME.cur_status == 'loading' ){
                    var $self=$(this); 
                    var $val= +$self.val(); 
                    var country = window.GAME_CONFIG ? window.GAME_CONFIG.user_country : 'default';
                    var betConfig = GAME.getBetConfigForCountry(country);
                    var minBet = betConfig.min_bet;
                    var maxBet = betConfig.max_bet;
                    $val = $val < minBet ? minBet : ( $val > maxBet ? maxBet : $val ); 
                    $val = $val > GAME.balance ? GAME.balance : $val; 
                    $self.val( $val ); 
                }
            });
            // установка ставки кнопками min max - обработчики будут установлены в updateMinMaxButtons()
            // установка ставки кнопками со значением
            $('.basic_radio input[name="bet_value"]').off().on('change', function(){ 
                if( GAME.cur_status == 'loading' ){
                    if( SETTINGS.volume.sound ){ SOUNDS.button.play(); } 
                    var $self=$(this); 
                    var $val = +$self.val();  
                    $val = $val > GAME.balance ? GAME.balance : $val;
                    $('#bet_size').val( $val ); 
                }
            }); 
            // установка уровня сложности
            $('[name="difficulity"]').off().on('change', function(){ 
                if( GAME.cur_status == 'loading' ){ 
                    if( SETTINGS.volume.sound ){ SOUNDS.button.play(); } 
                    var $self=$(this); 
                    var $val = $self.val(); 
                    GAME.cur_lvl = $val; 
                    // Генерируем новые трапы для нового уровня
                    GAME.generateLocalTraps();
                    GAME.create(); 
                } 
                else {
                    return false; 
                }
            });
            // забрать ставку
            $('#close_bet').off().on('click', function(){ 
                if( GAME.stp ){ 
                    if( SETTINGS.volume.sound ){ SOUNDS.button.play(); } 
                    var $self=$(this); 
                    $self.hide(); 
                    GAME.finish(1); 
                }
            });
            // начать игру или сделать ход
            $('#start').off().on('click', function(){ 
                console.log('Start button clicked (v2), GAME.cur_status:', GAME.cur_status);
                if( SETTINGS.volume.sound ){ SOUNDS.button.play(); } 
                var $self=$(this);
                switch( GAME.cur_status ){
                    case 'loading': 
                        $self.html( window.LOCALIZATION.TEXT_BETS_WRAPPER_GO ); 
                        if( +$('#bet_size').val() > 0 ){ 
                            GAME.start(); 
                        }
                        break; 
                    case 'game': 
                        if( CHICKEN.alife ){ 
                            $self.html( window.LOCALIZATION.TEXT_BETS_WRAPPER_GO ); 
                            // Вызываем move() при клике на GO во время игры
                            GAME.move();
                        }
                        break; 
                    case 'finish': 
                        $self.html( window.LOCALIZATION.TEXT_BETS_WRAPPER_WAIT );
                        //GAME.cur_status = "loading";
                        break;  
                }
            }); 
            $('window').on('resize', function(){
                $('#game_container').hide();
                $('#game_container').css('min-height', parseInt( $('#main').css('height') )+'px' );
                $('#game_container').show(); 
                SETTINGS.w = document.querySelector('#game_container').offsetWidth; 
                SETTINGS.segw = parseInt( $('.sector').eq(0).css('width') );
                var $scale = GAME.getChickenScale();
                $('#chick').css( 'left', ( SETTINGS.segw / 2 )+'px' ); 
                $('#chick .inner').css( 'transform', 'translateX(-50%) scale('+ $scale +')' ); 
                var $bottom = 50; 
                if( SETTINGS.w <= 1200 ){ $bottom = 35; }
                if( SETTINGS.w <= 1100 ){ $bottom = 30; }
                if( SETTINGS.w <= 1000 ){ $bottom = 25; }
                if( SETTINGS.w <= 900 ){ $bottom = 5; }
                if( SETTINGS.w <= 800 ){ $bottom = -15; }
                $('#chick').css('bottom', $bottom+'px');

            });
        }); 
    }
    updateTraps(){
        // Empty as we rely on API status for traps
    }
    
    // WebSocket methods - DISABLED
    updateTrapsFromWebSocket(websocketData) {}
    updateSectorCoefficients(sectors) {}
    requestTrapsFromWebSocket(level = null) {}
    toggleWebSocketMode() { return false; }
    updateAllLevelsTrapsFromWebSocket(allLevelsData) {}

    // Метод для отправки запроса к API после игры
    sendGameResultToAPI(gameResult, betAmount, winAmount, finalBalance) {
        // Empty as we don't want to send API requests
    }
    
    // Метод для получения информации о пользователе и обновления баланса
    async fetchUserInfo() {
        console.log('Fetching user info via API...');
        if (!window.API_TOKEN) {
            console.error('No API Token found in fetchUserInfo');
            return null;
        }
        
        try {
            // Fetch user data from Laravel API
            const response = await fetch('/api/user', {
                headers: {
                    'Authorization': 'Bearer ' + window.API_TOKEN,
                    'Accept': 'application/json'
                }
            });
            
            if (response.ok) {
                const data = await response.json();
                console.log('User info received:', data);
                
                // Update balance if available (assuming data.wallet.balance or similar)
                if (data.wallet && data.wallet.balance !== undefined) {
                    this.balance = parseFloat(data.wallet.balance);
                } else if (data.balance !== undefined) {
                    this.balance = parseFloat(data.balance);
                }
                
                this.updateBalanceDisplay();
                
                // Create game board after successful user info fetch
                this.create();
                
                return data;
            } else {
                console.error('Failed to fetch user info:', response.status);
                return null;
            }
        } catch (error) {
            console.error('Error fetching user info:', error);
            return null;
        }
    }
}

var GAME = new Game({}); 

class Chicken{
    constructor( $obj ){
        this.x = $obj.x ? $obj.x : 0; 
        this.y = $obj.y ? $obj.y : 0; 
        this.w = $obj.w ? $obj.w : SETTINGS.segw * 0.9; 
        this.h = $obj.h ? $obj.w : this.w; 
        this.alife = 0; 
        this.state = 'idle'; 
        this.wrapper = $('#chick');
    }  
}

var CHICKEN = new Chicken({}); 

function open_game(){ 
    // Обновляем размеры контейнера
    SETTINGS.w = document.querySelector('#game_container').offsetWidth;
    SETTINGS.h = document.querySelector('#game_container').offsetHeight;
    SETTINGS.segw = parseInt( $('#battlefield .sector').css('width') );
    
    // Refresh balance from DOM when game opens (only if not in demo mode)
    if (GAME && typeof GAME.refreshBalance === 'function') {
        if (!window.IS_DEMO_MODE && (!window.GAME_CONFIG || !window.GAME_CONFIG.is_demo_mode)) {
        GAME.refreshBalance();
        console.log('Balance refreshed in open_game():', GAME.balance);
        } else {
            console.log('Demo mode active, skipping balance refresh in open_game()');
        }
    }
    
    $('#splash').addClass('show_modal');
    var $music_settings = SETTINGS.volume.music; 
    var $sound_settings = SETTINGS.volume.sound; 
    $('#splash button').off().on('click', function(){
        $('#splash').remove(); 
        if( SETTINGS.volume.sound ){ 
            SOUNDS.button.play(); 
            $('#switch_sound').removeAttr('checked'); 
        } 
        else {
            $('#switch_sound').attr('checked', 'checked'); 
        }
        if( SETTINGS.volume.music ){ 
            SOUNDS.music.play(); 
            $('#switch_music').removeAttr('checked'); 
        }
        else {
            $('#switch_music').attr('checked', 'checked'); 
        }
    }); 
} 

function render(){ 
    if( GAME ){
        GAME.update(); 
        // Периодически проверяем и очищаем дубликаты курицы
        GAME.cleanupDuplicateChickens();
    }

    requestAnimationFrame( render );
}

render(); 

function saveGameResult(result, bet, award, balance) {
    // Empty as we don't want to save game results
}

// WebSocket методы для генерации ловушек
Game.prototype.updateTrapsFromWebSocket = function(websocketData) {
    // Empty as we don't want to update traps from WebSocket
}

Game.prototype.updateSectorCoefficients = function(sectors) {
    // Empty as we don't want to update sector coefficients
}

Game.prototype.requestTrapsFromWebSocket = function(level = null) {
    // Empty as we don't want to request traps from WebSocket
}

Game.prototype.toggleWebSocketMode = function() {
    // Empty as we don't want to toggle WebSocket mode
    return false;
}

Game.prototype.updateAllLevelsTrapsFromWebSocket = function(allLevelsData) {
    // Empty as we don't want to update all levels traps from WebSocket
}

// Инициализация состояния кнопки звука
$(document).ready(function(){
    if (SETTINGS.volume.active) {
        $('#sound_switcher').removeClass('off');
    } else {
        $('#sound_switcher').addClass('off');
    }
});

setTimeout( function(){ open_game(); }, 1000 );

// Обновляем кнопки MIN/MAX после загрузки конфигурации
$(document).ready(function(){
    // Ждем загрузки GAME_CONFIG и обновляем кнопки
    setTimeout(function() {
        if (window.GAME_CONFIG && window.GAME) {
            window.GAME.updateMinMaxButtons();
            console.log('Min/Max buttons updated after page load');
        }
    }, 1500);
});

