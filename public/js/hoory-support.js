// Script para carregar o chat de suporte Hoory apenas quando necessário
let hooryLoaded = false;

function loadHoorySupport() {
  // Verifica se existe token no localStorage
  const token = localStorage.getItem('token');
  
  if (!token) {
    // Se não há token, usuário não está logado
    if (window.$toast) {
      window.$toast.warning('Você precisa estar logado para acessar o suporte.', {
        timeout: 5000,
        position: 'top-right',
        closeOnClick: true,
        pauseOnHover: true,
        draggable: true
      });
    } else {
      console.error('Erro: Usuário não está logado');
    }
    return;
  }
  
  // Verifica se o usuário está logado usando JWT
  fetch('/api/auth/verify', {
    method: 'GET',
    headers: {
      'Content-Type': 'application/json',
      'Authorization': 'Bearer ' + token
    }
  })
    .then(response => {
      if (!response.ok) {
        throw new Error('Usuário não está logado');
      }
      return response.json();
    })
    .then(user => {
      // Se chegou aqui, o usuário está logado, agora verifica se o suporte está ativado
      return fetch('/api/check-support-status');
    })
    .then(response => response.json())
    .then(data => {
      if (!data.supportActive) {
        // Usa o Vue Toastification em vez do alerta padrão
        if (window.$toast) {
          window.$toast.error('O suporte está temporariamente indisponível.', {
            timeout: 5000,
            position: 'top-right',
            closeOnClick: true,
            pauseOnHover: true,
            draggable: true
          });
        } else {
          console.error('O suporte está temporariamente indisponível.');
        }
        return;
      }
      
      // Continua com o carregamento do suporte se estiver ativado
      if (hooryLoaded) {
        // Se o script já foi carregado, apenas mostra o chat
        if (window.hoorySDK) {
          window.hoorySDK.openChat();
        }
        return;
      }
      
      // Carrega o script Hoory
      const BASE_URL = "https://app.hoory.com";
      const g = document.createElement("script");
      const s = document.getElementsByTagName("script")[0];
      
      g.src = BASE_URL + "/packs/js/sdk.js";
      g.defer = true;
      g.async = true;
      s.parentNode.insertBefore(g, s);
      
      g.onload = function() {
        window.hoorySDK.run({
          websiteToken: 'SpQmfjCPonDFkh14cBbRQHYp',
          baseUrl: BASE_URL
        });
        
        // Abre o chat automaticamente após o carregamento
        window.hoorySDK.openChat();
        
        // Marca que o script foi carregado
        hooryLoaded = true;
      };
    })
    .catch(error => {
      // Se o usuário não está logado ou houve erro na verificação
      if (window.$toast) {
        window.$toast.warning('Você precisa estar logado para acessar o suporte.', {
          timeout: 5000,
          position: 'top-right',
          closeOnClick: true,
          pauseOnHover: true,
          draggable: true
        });
      } else {
        console.error('Erro: Usuário não está logado ou erro ao verificar status do suporte:', error);
      }
    });
}

// Exporta a função para uso global
window.loadHoorySupport = loadHoorySupport;
