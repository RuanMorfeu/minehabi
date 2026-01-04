{{-- Sistema de Notificações de Ganho nos Jogos --}}
<link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/toastify-js@1.12.0/src/toastify.min.css">

<script>
// Carregar Toastify dinamicamente para evitar conflitos
(function() {

    
    // Verificar se já existe
    if (window.gameNotificationsLoaded) {

        return;
    }
    window.gameNotificationsLoaded = true;
    
    // Carregar Toastify se não estiver disponível
    function loadToastify(callback) {
        if (typeof Toastify !== 'undefined') {

            callback();
            return;
        }
        

        const script = document.createElement('script');
        script.src = 'https://cdn.jsdelivr.net/npm/toastify-js@1.12.0/src/toastify.min.js';
        script.onload = function() {

            callback();
        };
        script.onerror = function() {

        };
        document.head.appendChild(script);
    }
    
    // Função principal de notificações
    function initGameNotifications() {
        const URLParams = new URLSearchParams(window.location.search);
        const winAmount = URLParams.get('win_amount');
        


        if (!winAmount) {

            return;
        }


        
        let toastOptions = {};
        if (Number(winAmount) > 0) {
            const formattedWin = Number(winAmount)
                .toLocaleString('pt-BR', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
            toastOptions = {
                text: `Parabéns! Você ganhou € ${formattedWin}!`,
                style: {
                    background: "linear-gradient(to right, #3b82f6, #1d4ed8)",
                },
            };
        } else {
            toastOptions = {
                text: `Infelizmente você não ganhou... Mas não se preocupe! Você pode tentar outra vez!`,
                style: {
                    background: "linear-gradient(to right, #ff7b72, #c32a22)",
                },
            };
        }
        

        
        // Mostrar notificação
        function showNotification() {

            
            if (typeof Toastify === 'undefined') {

                return;
            }
            
            try {
                Toastify({
                    text: toastOptions.text,
                    duration: 4000,
                    close: true,
                    gravity: "top",
                    position: "center",
                    style: toastOptions.style,
                    stopOnFocus: true,
                    onClick: function(){}
                }).showToast();
                

            } catch (error) {

            }
        }
        
        // Aguardar um pouco antes de mostrar
        setTimeout(showNotification, 500);
    }
    
    // Inicializar quando Toastify estiver carregado
    loadToastify(function() {
        // Aguardar DOM estar pronto
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initGameNotifications);
        } else {
            initGameNotifications();
        }
    });
})();
</script>
