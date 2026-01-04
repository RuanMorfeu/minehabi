/**
 * Script para capturar e armazenar o Facebook Click ID (fbc)
 * Este script deve ser incluído no layout principal do site
 */

// Função para obter parâmetros da URL
function getUrlParameter(name) {
    name = name.replace(/[\[]/, '\\[').replace(/[\]]/, '\\]');
    const regex = new RegExp('[\\?&]' + name + '=([^&#]*)');
    const results = regex.exec(location.search);
    return results === null ? '' : decodeURIComponent(results[1].replace(/\+/g, ' '));
}

// Função para definir um cookie
function setCookie(name, value, days) {
    let expires = '';
    if (days) {
        const date = new Date();
        date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
        expires = '; expires=' + date.toUTCString();
    }
    document.cookie = name + '=' + (value || '') + expires + '; path=/; SameSite=Lax';
}

// Capturar o fbclid da URL (se presente)
const fbclid = getUrlParameter('fbclid');

// Se o fbclid estiver presente, criar o cookie _fbc
if (fbclid) {
    // Formato do _fbc: fb.1.{timestamp}.{fbclid}
    const timestamp = Math.floor(Date.now() / 1000);
    const fbc = `fb.1.${timestamp}.${fbclid}`;
    
    // Armazenar o _fbc como cookie (válido por 90 dias)
    setCookie('_fbc', fbc, 90);
    
    console.log('Facebook Click ID capturado e armazenado:', fbc);
}
