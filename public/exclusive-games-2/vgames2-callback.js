/**
 * VGames2 Callback System
 * Sistema de callback universal para jogos exclusive2
 */

window.VGames2Callback = {
    /**
     * Obter parâmetros da URL
     */
    getUrlParams: function() {
        const params = new URLSearchParams(window.location.search);
        return {
            baseurl: decodeURIComponent(params.get("baseurl") || ""),
            token: decodeURIComponent(params.get("token") || ""),
            aposta: decodeURIComponent(params.get("aposta") || "1"),
            velo: decodeURIComponent(params.get("velo") || "1"),
            xmeta: decodeURIComponent(params.get("xmeta") || "1"),
            coin_value: decodeURIComponent(params.get("coin_value") || "0.01"),
            meta_multiplier: decodeURIComponent(params.get("meta_multiplier") || "1.00"),
            game_difficulty: decodeURIComponent(params.get("game_difficulty") || "1"),
            crsf: decodeURIComponent(params.get("crsf") || ""),
            is_demo_agent: decodeURIComponent(params.get("is_demo_agent") || "false")
        };
    },

    /**
     * Fazer requisição para API
     */
    fetchApi: async function(url, method = 'GET', data = null) {
        try {
            const options = {
                method: method,
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            };

            if (data && method !== 'GET') {
                options.body = JSON.stringify(data);
            }

            const response = await fetch(url, options);
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }

            return await response.json();
        } catch (error) {
            console.error('Erro na API:', error);
            throw error;
        }
    },

    /**
     * Processar ganho híbrido (para jogos exclusive2)
     */
    processarGanhoHibrido: async function(valor, aposta, token, baseurl, gameSlug, callback) {
        try {
            console.log('VGames2Callback: Processando ganho híbrido...', {valor, aposta, token, gameSlug});

            const winUrl = baseurl ? 
                baseurl + '/' + gameSlug + '/win' : 
                '/api/vgames2/' + gameSlug + '/win';

            const data = await this.fetchApi(winUrl, 'POST', {
                token: token,
                win_amount: valor,
                bet_amount: aposta
            });

            console.log('VGames2Callback: Ganho processado com sucesso:', data);
            callback(true, data);

        } catch (error) {
            console.error('VGames2Callback: Erro ao processar ganho:', error);
            callback(false, error);
        }
    },

    /**
     * Processar perda híbrida (para jogos exclusive2)
     */
    processarPerdaHibrida: async function(aposta, token, baseurl, gameSlug, callback) {
        try {
            console.log('VGames2Callback: Processando perda híbrida...', {aposta, token, gameSlug});

            const lostUrl = baseurl ? 
                baseurl + '/' + gameSlug + '/lost' : 
                '/api/vgames2/' + gameSlug + '/lost';

            const data = await this.fetchApi(lostUrl, 'POST', {
                token: token,
                bet_amount: aposta
            });

            console.log('VGames2Callback: Perda processada com sucesso:', data);
            callback(true, data);

        } catch (error) {
            console.error('VGames2Callback: Erro ao processar perda:', error);
            callback(false, error);
        }
    }
};

console.log('✅ VGames2Callback carregado com sucesso!');
