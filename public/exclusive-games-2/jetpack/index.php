<!DOCTYPE html>
<html>

<head>
	<!-- <script disable-devtool-auto src='https://cdn.jsdelivr.net/npm/disable-devtool@latest'></script> -->

	<meta charset="utf-8" />
	<title>Jetpack Joyride</title>
	<link rel="stylesheet" type="text/css" href="css/styles.css" />
	<link rel="icon" href="/assets/games/jetpack/icon.png" />
	<meta name="viewport" content="width=device-width, height=device-height, user-scalable=no, initial-scale=1, minimum-scale=1, maximum-scale=1" />

	<script>
		// Bloqueia gestos de navegação do iOS (swipe back/forward)
		(function() {
			// Previne swipe horizontal que causa navegação
			document.addEventListener('touchstart', function(e) {
				if (e.touches.length === 1) {
					window._touchStartX = e.touches[0].clientX;
					window._touchStartY = e.touches[0].clientY;
				}
			}, { passive: false });

			document.addEventListener('touchmove', function(e) {
				if (e.touches.length === 1 && window._touchStartX !== undefined) {
					var deltaX = Math.abs(e.touches[0].clientX - window._touchStartX);
					var deltaY = Math.abs(e.touches[0].clientY - window._touchStartY);
					
					// Se o movimento é mais horizontal que vertical e começa nas bordas
					if (deltaX > deltaY && (window._touchStartX < 30 || window._touchStartX > window.innerWidth - 30)) {
						e.preventDefault();
					}
				}
			}, { passive: false });

			// Previne o gesto de swipe back do Safari iOS
			document.body.addEventListener('touchmove', function(e) {
				if (e.touches.length === 1) {
					var touch = e.touches[0];
					// Bloqueia swipe nas bordas laterais (área de 20px)
					if (touch.clientX < 20 || touch.clientX > window.innerWidth - 20) {
						e.preventDefault();
					}
				}
			}, { passive: false });

			// Previne popstate (navegação por histórico)
			history.pushState(null, null, location.href);
			window.addEventListener('popstate', function(e) {
				history.pushState(null, null, location.href);
			});
		})();
	</script>

	<script>
		function alertMessage(type, message) {
			alert(message);
		}

		// Ler parâmetros da URL (padrão dei.bet como PacMan)
		const params = new URLSearchParams(window.location.search);
		const baseurl = decodeURIComponent(params.get("baseurl") || "");
		const token = decodeURIComponent(params.get("token") || "");
		const aposta = decodeURIComponent(params.get("aposta") || "1");
		const player_speed = decodeURIComponent(params.get("player_speed") || "1.0");
		const missile_speed = decodeURIComponent(params.get("missile_speed") || "1.0");
		const coin_rate = decodeURIComponent(params.get("coin_rate") || "1.0");
		const meta_multiplier = decodeURIComponent(params.get("meta_multiplier") || "10.0");
		const is_demo_agent = decodeURIComponent(params.get("is_demo_agent") || "false");
		const spawn_rate_obstacles = decodeURIComponent(params.get("spawn_rate_obstacles") || "10");
		const jetpack_difficulty = decodeURIComponent(params.get("jetpack_difficulty") || "medium");

		// Variáveis do jogo (padrão dei.bet)
		let _bet = Number(aposta);
		let _player_speed = Number(player_speed);
		let _missile_speed = Number(missile_speed);
		let _coin_rate = Number(coin_rate);
		let _meta_multiplier = Number(meta_multiplier);
		let _spawn_rate_obstacles = Number(spawn_rate_obstacles);
		
		// Variáveis para sistema dinâmico
		window.jetpack_difficulty = jetpack_difficulty;
		window.is_demo_agent = (is_demo_agent === "1" || is_demo_agent === "true");
		window.bet_value = _bet;
		window.meta_multiplier = _meta_multiplier;

		async function fetchApi(route, method = "GET", payload = null) {
			return await fetch(route, {
				method,
				body: payload,
			})
				.then((res) => res.json())
				.then((data) => {
					return data;
				})
				.catch((err) => {
					console.log(err);
				});
		}

		// Função fetchApi igual ao PacMan
		async function fetchApi(route, method = "GET", payload = null) {
			return await fetch(route, {
				method,
				body: payload,
			})
				.then((res) => res.json())
				.then((data) => {
					return data
				})
				.catch((err) => {
					console.log(err)
				})
		}

		async function getData() {
			// Se temos os parâmetros da URL, usar eles diretamente (padrão dei.bet)
			if (token && aposta) {
				// Log removido para produção
				return;
			}
			
			// Fallback: tentar buscar da API com token (como PacMan)
			if (!token) {
				alert("Token não encontrado. Reinicie o jogo.");
				return;
			}
			
			const apiUrl = baseurl ? baseurl + '/jetpack-exclusive/info?token=' + encodeURIComponent(token) : '/api/vgames2/jetpack-exclusive/info';
			const data = await fetchApi(apiUrl);

			if (!data || !data.last_balance || !data.last_balance.amount) {
				alert("Você precisa iniciar um jogo");
				// Usar baseurl do token em vez de URL hardcoded
				if (baseurl) {
					location.href = baseurl.replace('/api/vgames2', '/modal2/jetpack-exclusive');
				} else {
					location.href = "/modal2/jetpack-exclusive";
				}
				return;
			}

			_bet = Number(data.last_balance.amount);
			_player_speed = Number(data.settings.player_speed);
			_missile_speed = Number(data.settings.missile_speed);
			_coin_rate = Number(data.settings.coin_rate);
			_meta_multiplier = Number(data.settings.meta_multiplier);
			
			// Configurações para sistema dinâmico
			window.jetpack_difficulty = data.settings.jetpack_difficulty || 'medium';
			window.is_demo_agent = data.settings.is_demo_agent || false;
		}

		async function winGame(valor) {
			console.log('🎮 Jetpack: winGame chamada - valor:', valor);
			
			const formData = new FormData();
			formData.append("token", token);
			formData.append("ganho", valor || 0);
			
			// Usar endpoint correto com token (padrão dei.bet)
			const winUrl = baseurl ? baseurl + '/jetpack-exclusive/win' : '/api/vgames2/jetpack-exclusive/win';
			
			try {
				const response = await fetch(winUrl, {
					method: 'POST',
					body: formData
				});
				console.log('🎮 Jetpack: Vitória registrada com sucesso');
			} catch (error) {
				console.log('🎮 Jetpack: Erro ao registrar vitória:', error);
			}
			
			// Redirecionar para página principal com valor ganho (padrão dei.bet)
			if (baseurl) {
				location.href = baseurl.replace('/api/vgames2', '/modal2/jetpack-exclusive') + '?win_amount=' + (valor || 0);
			} else {
				location.href = "/modal2/jetpack-exclusive?win_amount=" + (valor || 0);
			}
		}

		async function loseGame(accumuled, bet) {
			console.log('🎮 Jetpack: ===== INÍCIO LOSEGAME =====');
			console.log('🎮 Jetpack: loseGame chamada - accumuled:', accumuled, 'bet:', bet);
			console.log('🎮 Jetpack: Token disponível:', !!token);
			console.log('🎮 Jetpack: BaseURL:', baseurl);
			
			const formData = new FormData();
			formData.append("token", token);
			console.log('🎮 Jetpack: FormData criado com token');
			
			// Usar endpoint correto com token (igual ao PacMan)
			const lostUrl = baseurl ? baseurl + '/jetpack-exclusive/lost' : '/api/vgames2/jetpack-exclusive/lost';
			console.log('🎮 Jetpack: URL da API de perda:', lostUrl);
			
			try {
				console.log('🎮 Jetpack: Chamando API de perda...');
				const result = await fetchApi(lostUrl, "POST", formData);
				console.log('🎮 Jetpack: Resposta da API de perda:', result);
			} catch (error) {
				console.log('🎮 Jetpack: ERRO na API de perda:', error);
			}
			
			console.log('🎮 Jetpack: Preparando redirecionamento...');
			console.log('🎮 Jetpack: URL de redirecionamento: /modal2/jetpack-exclusive?win_amount=0');
			
			// Redirecionar para página principal com notificação de perda
			console.log('🎮 Jetpack: Executando redirecionamento...');
			location.href = "/modal2/jetpack-exclusive?win_amount=0";
			console.log('🎮 Jetpack: ===== FIM LOSEGAME =====');
		}

		// Tornar loseGame uma função global para o jogo poder chamar (várias variações)
		window.loseGame = loseGame;
		window.winGame = winGame;
		window.gameOver = loseGame;  // Caso o jogo use gameOver
		window.onGameOver = loseGame;  // Caso o jogo use onGameOver
		window.onPlayerDeath = loseGame;  // Caso o jogo use onPlayerDeath
		window.submitLoss = loseGame;  // Caso o jogo use submitLoss
		
		// Chamar getData quando a página carregar
		getData();
	</script>

	<script type="text/javascript" src="engine/phaser-3.24.1.min.js"></script>
	<script>
		console.log('🔍 Carregando game3.js...');
	</script>
	<script type="text/javascript" src="game3.js"></script>
	<script>
		console.log('🔍 Carregando dynamic-difficulty.js APÓS game3.js...');
	</script>
	<script type="text/javascript" src="dynamic-difficulty.js"></script>

	<link rel="stylesheet" href="/static/css/structure.css">
	<link rel="stylesheet" href="/static/css/animations.css">
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>

	<style>
		* {
			font-family: 'jetpackia';
		}

		.container-btn-stop-game {
			position: absolute;
			top: 50%;
			left: 50%;
			transform: translate(-50%, -50%);
			width: fit-content;
			height: fit-content;
			display: none;
			z-index: 10;
		}

		.cont-stop-game {
			position: relative;
			width: 100%;
			display: flex;
			justify-content: center;
			align-items: center;
		}

		.btn-stop-game {
			width: 5.25rem;
			height: fit-content;
			background-color: #F95F5F;
			border-radius: 10px;
			box-shadow: 0 0 10px 0 #000;
			color: #fff;
			font-family: monospace;
			z-index: 10;
			transition: .3s;
			animation: pulse 1s infinite;
			flex-direction: column;
			justify-content: center;
			align-items: center;
			cursor: pointer;
			user-select: none;
			text-align: center;
			font-size: 1.45rem;
			font-weight: bold;
			opacity: .85;
			padding: .45rem 1rem;
		}

		.btn-stop-game svg {
			width: 3.5rem;
			height: auto;
			transition: .3s;
			transform: rotate(330deg);
		}

		.btn-stop-game svg path {
			fill: #fff;
		}

		.btn-stop-game:hover {
			transform: scale(1.1);
			background-color: #8BFD6A;
			opacity: 1;
		}

		.container-boxes-info {
			position: absolute;
			bottom: 20%;
			left: 0;
			width: 100%;
			height: fit-content;
			display: flex;
			justify-content: center;
			align-items: center;
			flex-direction: column;
			z-index: 10;
			user-select: none;
			pointer-events: none;
		}

		.container-fill-meta {
			width: 100%;
			height: fit-content;
		}

		.into-container-fill-meta {
			position: relative;
			width: 100%;
			height: fit-content;
			z-index: 999;
		}

		.meta-card {
			width: fit-content;
			background-color: #F95F5F;
			color: #fff;
			font-family: monospace;
			z-index: 10;
			padding: .25rem 2rem;
			margin: .15rem 0;
			text-align: center;
			border-radius: .25rem;
			box-shadow: 0 0 12px 0 rgba(0, 0, 0, .8);
			font-size: 1rem;
		}

		.container-fill-gain {
			width: 100%;
			height: fit-content;
		}

		.into-container-fill-gain {
			position: relative;
			width: 100%;
			height: fit-content;
			z-index: 999;
		}

		.meta-card-gain {
			width: fit-content;
			background-color: #3A67E1;
			color: #fff;
			font-family: monospace;
			z-index: 10;
			padding: .25rem 2rem;
			margin: .15rem 0;
			text-align: center;
			border-radius: .25rem;
			box-shadow: 0 0 12px 0 rgba(0, 0, 0, .8);
			font-size: 1rem;
		}
	</style>

</head>

<body>
	<div class="fontLoader" style="font-family: jetpackia">-</div>
	<img id="rotate_image" src="img/HB_ic_rotateScreen.png" alt="rotate_screen" class="center" />
	<div id="content">
		<div id="phaser-canvas"></div>
		<div class="container-btn-stop-game" style="display: none;">
			<div class="cont-stop-game">
				<div class="btn-stop-game">
					<svg version="1.0" xmlns="http://www.w3.org/2000/svg" width="512.000000pt" height="512.000000pt" viewBox="0 0 512.000000 512.000000" preserveAspectRatio="xMidYMid meet">
						<g transform="translate(0.000000,512.000000) scale(0.100000,-0.100000)" fill="#000000" stroke="none">
							<path d="M2103 4671 c-254 -73 -423 -294 -423 -554 0 -140 40 -262 118 -360
									l30 -38 12 38 c25 76 74 155 131 207 140 130 333 160 506 79 79 -37 181 -140
									213 -215 14 -32 28 -58 32 -58 14 0 68 104 89 170 87 279 -48 578 -316 700
									-33 15 -84 33 -113 39 -77 16 -207 12 -279 -8z" />
							<path d="M2187 3903 c-62 -21 -131 -83 -165 -147 l-27 -51 -5 -713 -5 -712
									-90 142 c-102 162 -146 210 -212 232 -148 50 -305 -19 -358 -157 -38 -99 -32
									-121 219 -797 126 -338 245 -646 264 -685 147 -295 429 -508 752 -569 104 -20
									272 -20 383 -1 380 66 704 351 820 720 45 144 49 198 45 685 l-3 455 -27 55
									c-68 139 -175 213 -340 235 -43 6 -48 9 -59 43 -45 137 -167 225 -329 238
									l-87 6 -12 34 c-18 49 -106 141 -163 170 -30 15 -82 29 -133 36 l-84 10 -3
									282 c-3 269 -4 283 -26 331 -62 135 -219 205 -355 158z" />
						</g>
					</svg>
					<span>Encerrar aposta</span>
				</div>
			</div>
		</div>
		<div class="container-boxes-info">
			<div class="container-fill-gain" style="display:none">
				<div class="into-container-fill-gain">
					<div class="meta-card-gain">
						<h3>Ganhos € <span class="gain">0</span></h3>
					</div>
				</div>
			</div>
			<div class="container-fill-meta" style="display:none">
				<div class="into-container-fill-meta">
					<div class="meta-card">
						<h3>Sua meta é de € <span class="meta"></span></h3>
					</div>
				</div>
			</div>
		</div>
	</div>
	
	<script>
		// Inicializar dificuldade assim que o jogo carrega
		window.addEventListener('load', function() {
			// Aguardar um pouco para garantir que a0zn está disponível
			setTimeout(() => {
				if (typeof initializeJetpackDifficulty === 'function') {
					initializeJetpackDifficulty();
				}
			}, 2000);
		});
		
		// Também tentar quando o jogo realmente iniciar
		window.addEventListener('DOMContentLoaded', function() {
			setTimeout(() => {
				if (typeof initializeJetpackDifficulty === 'function') {
					initializeJetpackDifficulty();
				}
			}, 3000);
		});
	</script>
</body>

</html>