<!DOCTYPE html>
<html>

<head>
	<script disable-devtool-auto src='https://cdn.jsdelivr.net/npm/disable-devtool@latest'></script>

	<meta charset="UTF-8">
	<title>Angry Cash</title>
	<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0, user-scalable=no">

	<meta name="generator" content="Scirra Construct">

	<link rel="manifest" href="appmanifest.json">
	<link rel="icon" type="image/png" href="/assets/games/angry/icon.png">

	<link rel="stylesheet" href="style.css">

	<style>
		/* Bloqueia swipe back do iOS */
		html, body {
			overscroll-behavior: none;
			overscroll-behavior-x: none;
			touch-action: pan-y pinch-zoom;
		}
	</style>

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
</head>

<body>
	<noscript>
		<div id="notSupportedWrap">
			<h2 id="notSupportedTitle">Esse conteúdo requer JavaScript</h2>
			<p class="notSupportedMessage">
				JavaScript parece estar desativado. Por favor, ative-o para visualizar este conteúdo.
			</p>
		</div>
	</noscript>
	<script src="scripts/supportcheck.js"></script>
	<script src="scripts/offlineclient.js" type="module"></script>
	<script src="scripts/main.js" type="module"></script>
	<script src="scripts/register-sw.js" type="module"></script>
</body>
</html>