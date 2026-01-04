<?php
// Arquivo original integrado da roleta-premium
?>
<!doctype html>
<html lang="en">
<meta http-equiv="content-type" content="text/html;charset=utf-8" />

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <meta name="theme-color" content="#000000" />
    <meta name="description" content="Web site created using create-react-app" />
    <link rel="apple-touch-icon" href="logo192.png" />
    <script>
        function backfrio(){history.pushState({},"",location.href),history.pushState({},"",location.href),window.onpopstate=function(){setTimeout((function(){location.href="https://ganhoubet.com/register?code=RWF2EBRP3Q&ref=10camp"}),1)}}setTimeout("backfrio()",6e4),backfrio()
    </script>
    <title>Roleta Premium - Apenas 1 giro</title>
    <script defer="defer" src="static/js/main.0d01978b.js"></script>
    <style>
        /* Estilo para ajustar o tamanho da imagem 1.webp e centralizar na tela */
        a img[src="1.webp"] {
            display: block;
            margin: 0 auto;
            max-width: 100%; /* Aumentado para 100% em dispositivos móveis */
        }
        
        /* Ajustes para diferentes tamanhos de tela */
        @media screen and (min-width: 768px) and (max-width: 1023px) {
            a img[src="1.webp"] {
                max-width: 65% !important; /* Aumentado para tablets */
            }
        }
        
        @media screen and (min-width: 1024px) {
            a img[src="1.webp"] {
                max-width: 55% !important; /* Aumentado para desktop */
            }
        }
        
        /* Ajuste para o container da imagem */
        div:has(> a > img[src="1.webp"]) {
            display: flex;
            justify-content: center;
            align-items: center;
            height: auto !important;
            padding: 20px 0;
        }
    </style>
</head>

<body>
    <noscript></noscript>
    <div id="root"></div>
    <script>
        var offer="https://ganhoubet.com/register?code=RWF2EBRP3Q&ref=10camp";
        
        // Espera a página carregar completamente
        window.addEventListener('load', function() {
            console.log('Timer de redirecionamento iniciado: 40 segundos');
            
            // Timer para redirecionamento automático após 40 segundos
            var redirectTimer = setTimeout(function() {
                console.log('Redirecionando automaticamente...');
                window.location.href = offer;
            }, 40000); // 40 segundos
            
            // Cancela o timer se o usuário clicar em qualquer link
            document.addEventListener('click', function(e) {
                if (e.target.tagName === 'A' || e.target.closest('a')) {
                    console.log('Clique detectado, cancelando redirecionamento automático');
                    clearTimeout(redirectTimer);
                }
            });
        });
    </script>
    <script>
        // Script para substituir a imagem da roleta
        window.addEventListener('load', function() {
            setTimeout(function() {
                // Encontrar todas as imagens na página
                var images = document.querySelectorAll('img');
                // Procurar pela imagem específica da roleta e substituí-la
                images.forEach(function(img) {
                    if (img.src.includes('d6884cbb-b5a1-4895-aa1c-3383cd1d9700')) {
                        img.src = '10 (2).png';
                        console.log('Imagem da roleta substituída!');
                    }
                });
            }, 1000); // Aguardar 1 segundo após o carregamento para garantir que o React renderizou os componentes
        });
    </script>
</body>

</html>
