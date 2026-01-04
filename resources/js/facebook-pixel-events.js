/**
 * Script para enviar eventos do Facebook Pixel
 * Este arquivo contém funções para rastrear eventos específicos do Facebook Pixel
 */

// Importa o ID do pixel e o token de acesso das configurações globais
// Estes valores serão definidos pela aplicação Laravel
let pixelId = window.facebookPixelId || '';
let accessToken = window.facebookAccessToken || '';

// Função para inicializar o Facebook Pixel
export function initFacebookPixel(id, token) {
    if (id) pixelId = id;
    if (token) accessToken = token;
    
    // Verifica se o Facebook Pixel já está carregado
    if (!window.fbq) {
        console.log('Inicializando Facebook Pixel com ID:', pixelId);
        
        // Código padrão de inicialização do Facebook Pixel
        !function(f,b,e,v,n,t,s) {
            if(f.fbq) return;
            n=f.fbq=function(){n.callMethod?
                n.callMethod.apply(n,arguments):n.queue.push(arguments)};
            if(!f._fbq) f._fbq=n;
            n.push=n;n.loaded=!0;n.version='2.0';
            n.queue=[];t=b.createElement(e);t.async=!0;
            t.src=v;s=b.getElementsByTagName(e)[0];
            s.parentNode.insertBefore(t,s)
        }(window, document,'script','https://connect.facebook.net/en_US/fbevents.js');
        
        // Inicializa o pixel com o ID
        fbq('init', pixelId);
    }
}

// Função para rastrear evento de registro
export function trackRegistration(userData = {}) {
    if (!window.fbq) initFacebookPixel();
    
    console.log('Enviando evento de registro para o Facebook Pixel');
    fbq('track', 'CompleteRegistration', userData);
    
    // Envia evento para a API de Conversões do Facebook se tiver token de acesso
    if (accessToken) {
        sendServerEvent('CompleteRegistration', userData);
    }
}

// Função para rastrear evento de depósito
export function trackDeposit(value, currency = 'BRL', additionalData = {}) {
    if (!window.fbq) initFacebookPixel();
    
    const eventData = {
        value: value,
        currency: currency,
        ...additionalData
    };
    
    console.log('Enviando evento de depósito para o Facebook Pixel', eventData);
    fbq('track', 'Purchase', eventData);
    
    // Envia evento para a API de Conversões do Facebook se tiver token de acesso
    if (accessToken) {
        sendServerEvent('Purchase', eventData);
    }
}

// Função para rastrear evento de visualização de página
export function trackPageView() {
    if (!window.fbq) initFacebookPixel();
    
    console.log('Enviando evento de visualização de página para o Facebook Pixel');
    fbq('track', 'PageView');
}

// Função para enviar eventos para a API de Conversões do Facebook
function sendServerEvent(eventName, eventData) {
    // Verifica se temos o token de acesso e o ID do pixel
    if (!accessToken || !pixelId) {
        console.warn('Token de acesso ou ID do pixel não definidos para envio de evento ao servidor');
        return;
    }
    
    // Obtém o _fbc do cookie se existir
    const fbcCookie = document.cookie.split('; ').find(row => row.startsWith('_fbc='));
    const fbc = fbcCookie ? fbcCookie.split('=')[1] : null;
    
    // Adiciona o _fbc aos dados do evento se disponível
    if (fbc) {
        eventData.fbc = fbc;
    }
    
    // Envia o evento para o backend que irá repassar para a API do Facebook
    fetch('/api/facebook-pixel/event', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
        },
        body: JSON.stringify({
            event_name: eventName,
            event_data: eventData,
            pixel_id: pixelId,
            access_token: accessToken
        })
    })
    .then(response => response.json())
    .then(data => {
        console.log('Evento enviado para a API de Conversões do Facebook:', data);
    })
    .catch(error => {
        console.error('Erro ao enviar evento para a API de Conversões do Facebook:', error);
    });
}

// Exporta um objeto com todas as funções
export default {
    initFacebookPixel,
    trackRegistration,
    trackDeposit,
    trackPageView
};
