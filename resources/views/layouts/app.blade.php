<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
        <meta http-equiv="X-UA-Compatible" content="ie=edge">

        <!-- Meta Pixel Code (Facebook) -->
        @php
            $setting = \App\Models\Setting::first();
            $pixelId = $setting->facebook_pixel_id ?? '641305108716070';
            $accessToken = $setting->facebook_access_token ?? 'EAAO9hYqUMOYBO428jfPpkLxvSrapZAfFeFkunEg23z7e5GmAHt3LX386zZCDvxdxXpf4M41KnwuXl9kZCqSW6sShtD5vrcZCRYxzBKQv4ba8g65yE0ll9zh5D2ZASZABb1BkWhl0qXi5ZAbQalxbtWhVH3LsrzTZBKomAFolxzvb1MClKULBBwwHLM3YJPXhcyVftQZDZD';
        @endphp
        <script>
            // Definir variáveis globais para o Facebook Pixel
            window.facebookPixelId = '{{ $pixelId }}';
            window.facebookAccessToken = '{{ $accessToken }}';
        </script>
        <script>
            !function(f,b,e,v,n,t,s)
            {if(f.fbq)return;n=f.fbq=function(){n.callMethod?
            n.callMethod.apply(n,arguments):n.queue.push(arguments)};
            if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
            n.queue=[];t=b.createElement(e);t.async=!0;
            t.src=v;s=b.getElementsByTagName(e)[0];
            s.parentNode.insertBefore(t,s)}(window, document,'script',
            'https://connect.facebook.net/en_US/fbevents.js');
            fbq('init', '{{ $pixelId }}');
            fbq('track', 'PageView');
        </script>
        <noscript>
            <img height="1" width="1" style="display:none" 
                src="https://www.facebook.com/tr?id={{ $pixelId }}&ev=PageView&noscript=1"/>
        </noscript>
        <!-- End Meta Pixel Code -->

        @php $setting = \Helper::getSetting() @endphp
        @if(!empty($setting['software_favicon']))
            <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('/storage/' . $setting['software_favicon']) }}">
        @endif

        <link rel="stylesheet" href="{{ asset('assets/css/fontawesome.min.css') }}">
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@100;200;300;400;500;600;700&family=Roboto+Condensed:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;1,100&display=swap" rel="stylesheet">        <title>{{ env('APP_NAME') }}</title>

        <!-- CSRF Token -->
        <meta name="csrf-token" content="{{ csrf_token() }}">

        @php $custom = \Helper::getCustom() @endphp
        <style>
            body{
                font-family: {{ $custom['font_family_default'] ?? "'Roboto Condensed', sans-serif" }};
            }
            :root {
                --ci-primary-color: {{ $custom['primary_color'] }};
                --ci-primary-opacity-color: {{ $custom['primary_opacity_color'] }};
                --ci-secundary-color: {{ $custom['secundary_color'] }};
                --ci-gray-dark: {{ $custom['gray_dark_color'] }};
                --ci-gray-light: {{ $custom['gray_light_color'] }};
                --ci-gray-medium: {{ $custom['gray_medium_color'] }};
                --ci-gray-over: {{ $custom['gray_over_color'] }};
                --title-color: {{ $custom['title_color'] }};
                --text-color: {{ $custom['text_color'] }};
                --sub-text-color: {{ $custom['sub_text_color'] }};
                --placeholder-color: {{ $custom['placeholder_color'] }};
                --background-color: {{ $custom['background_color'] }};
                --standard-color: #1C1E22;
                --shadow-color: #111415;
                --page-shadow: linear-gradient(to right, #111415, rgba(17, 20, 21, 0));
                --autofill-color: #f5f6f7;
                --yellow-color: #FFBF39;
                --yellow-dark-color: #d7a026;
                --border-radius: {{ $custom['border_radius'] }};
                --tw-border-spacing-x: 0;
                --tw-border-spacing-y: 0;
                --tw-translate-x: 0;
                --tw-translate-y: 0;
                --tw-rotate: 0;
                --tw-skew-x: 0;
                --tw-skew-y: 0;
                --tw-scale-x: 1;
                --tw-scale-y: 1;
                --tw-scroll-snap-strictness: proximity;
                --tw-ring-offset-width: 0px;
                --tw-ring-offset-color: #fff;
                --tw-ring-color: rgba(59,130,246,.5);
                --tw-ring-offset-shadow: 0 0 #0000;
                --tw-ring-shadow: 0 0 #0000;
                --tw-shadow: 0 0 #0000;
                --tw-shadow-colored: 0 0 #0000;

                --input-primary: {{ $custom['input_primary'] }};
                --input-primary-dark: {{ $custom['input_primary_dark'] }};

                --carousel-banners: {{ $custom['carousel_banners'] }};
                --carousel-banners-dark: {{ $custom['carousel_banners_dark'] }};


                --sidebar-color: {{ $custom['sidebar_color'] }} !important;
                --sidebar-color-dark: {{ $custom['sidebar_color_dark'] }} !important;


                --navtop-color {{ $custom['navtop_color'] }};
                --navtop-color-dark: {{ $custom['navtop_color_dark'] }};


                --side-menu {{ $custom['side_menu'] }};
                --side-menu-dark: {{ $custom['side_menu_dark'] }};

                --footer-color {{ $custom['footer_color'] }};
                --footer-color-dark: {{ $custom['footer_color_dark'] }};

                --card-color {{ $custom['card_color'] }};
                --card-color-dark: {{ $custom['card_color_dark'] }};
            }
            .navtop-color{
                background-color: {{ $custom['sidebar_color'] }} !important;
            }
            :is(.dark .navtop-color) {
                background-color: {{ $custom['sidebar_color_dark'] }} !important;
            }

            .bg-base {
                background-color: {{ $custom['background_base'] }};
            }
            :is(.dark .bg-base) {
                background-color: {{ $custom['background_base_dark'] }};
            }
        </style>

        @if(!empty($custom['custom_css']))
            <style>
                {!! $custom['custom_css'] !!}
            </style>
        @endif

        @if(!empty($custom['custom_header']))
            {!! $custom['custom_header'] !!}
        @endif

        @vite(['resources/css/app.css', 'resources/js/app.js'])
        
        {{-- Sistema de Notificações de Ganho nos Jogos --}}
        <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/toastify-js@1.12.0/src/toastify.min.css">
        <script src="https://cdn.jsdelivr.net/npm/toastify-js@1.12.0/src/toastify.min.js"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                console.log('🎮 Game Notifications: DOM carregado');
                
                const URLParams = new URLSearchParams(window.location.search);
                const winAmount = URLParams.get('win_amount');
                
                console.log('🎮 Game Notifications: win_amount =', winAmount);
                
                if (winAmount) {
                    console.log('🎮 Game Notifications: Processando notificação');
                    
                    setTimeout(function() {
                        let message, background;
                        
                        if (Number(winAmount) > 0) {
                            const formattedWin = Number(winAmount).toLocaleString('pt-BR', {
                                minimumFractionDigits: 2,
                                maximumFractionDigits: 2
                            });
                            message = `Parabéns! Você ganhou € ${formattedWin}!`;
                            background = "linear-gradient(to right, #3b82f6, #1d4ed8)";
                        } else {
                            message = "Infelizmente você não ganhou... Mas não se preocupe! Você pode tentar outra vez!";
                            background = "linear-gradient(to right, #ff7b72, #c32a22)";
                        }
                        
                        console.log('🎮 Game Notifications: Mostrando toast:', message);
                        
                        if (typeof Toastify !== 'undefined') {
                            Toastify({
                                text: message,
                                duration: 4000,
                                close: true,
                                gravity: "top",
                                position: "center",
                                style: {
                                    background: background
                                },
                                stopOnFocus: true
                            }).showToast();
                            console.log('🎮 Game Notifications: Toast exibido!');
                        } else {
                            console.error('🎮 Game Notifications: Toastify não disponível');
                        }
                    }, 1000);
                }
            });
        </script>
    </head>
    <body color-theme="light" class="bg-base text-gray-800">
        <div id="ganhoubet"></div>

        <script src="https://cdnjs.cloudflare.com/ajax/libs/flowbite/2.0.0/datepicker.min.js"></script>
        <script>
            window.Livewire?.on('copiado', (texto) => {
                navigator.clipboard.writeText(texto).then(() => {
                    Livewire.emit('copiado');
                });
            });

            window._token = '{{ csrf_token() }}';
            
            // Forçar modo light
            document.documentElement.classList.remove('dark');
            document.documentElement.classList.add('light');
            localStorage.setItem('color-theme', 'light');
        </script>

        @if(!empty($custom['custom_js']))
            <script>
                {!! $custom['custom_js'] !!}
            </script>
        @endif

        @if(!empty($custom['custom_body']))
            {!! $custom['custom_body'] !!}
        @endif

        @if(!empty($custom))
            <script>
                const custom = {!! json_encode($custom)  !!};
            </script>
        @endif
        
        <!-- Script de suporte Hoory (carregado sob demanda) -->
        <script src="{{ asset('js/hoory-support.js') }}" defer></script>
    </body>
</html>
