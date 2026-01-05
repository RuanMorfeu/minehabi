import { ref, reactive } from 'vue'
import { useAuthStore } from '@/Stores/Auth.js'
import HttpApi from '@/Services/HttpApi.js'

export function useMines() {
  const authStore = useAuthStore()
  const user = authStore.user
  
  // Wallet reativo como nos outros jogos
  const wallet = ref(null)
  const isLoadingWallet = ref(true)
  
  // Funções para tocar som
  const playSound = (soundFile) => {
    const audio = new Audio(`/assets/assets/sounds/${soundFile}`)
    audio.play().catch(e => console.log('Erro ao tocar som:', e))
  }
  
  // Tabela de multiplicadores iniciais (Next) baseada na quantidade de bombas
  const multiplicadoresIniciais = {
    1: 1.01,
    2: 1.05,
    3: 1.10,
    4: 1.15,
    5: 1.21,
    6: 1.28,
    7: 1.35,
    8: 1.43,
    9: 1.52,
    10: 1.62,
    11: 1.73,
    12: 1.87,
    13: 2.02,
    14: 2.20,
    15: 2.43,
    16: 2.69,
    17: 3.03,
    18: 3.46,
    19: 4.04,
    20: 4.85,
    21: 6.06, // Estimativa para valores acima de 20 não mostrados na imagem
    22: 8.08,
    23: 12.12,
    24: 24.25
  }

  // Função para calcular combinação C(n, k)
  const combination = (n, k) => {
    if (k > n || k < 0) {
      return 0
    }
    
    if (k === 0 || k === n) {
      return 1
    }
    
    // Otimização: usa o menor valor entre k e n-k
    k = Math.min(k, n - k)
    
    let result = 1
    for (let i = 0; i < k; i++) {
      result = result * (n - i) / (i + 1)
    }
    
    return result
  }

  // Função para calcular o próximo multiplicador (Spribe formula)
  const calcularProximoMultiplicador = (bombas, revelados) => {
    if (revelados === 0) {
      return 1.00
    }
    
    // Para o primeiro clique, usa a tabela
    if (revelados === 1 && multiplicadoresIniciais[bombas]) {
      return multiplicadoresIniciais[bombas]
    }
    
    const totalEstrelas = 25 - bombas
    
    if (revelados > totalEstrelas) {
      return 0.00
    }
    
    // Aplica fórmula da Spribe: 0.97 × [C(25, k) / C(25 - N, k)]
    const combinacoes25k = combination(25, revelados)
    const combinacoesEstrelas = combination(totalEstrelas, revelados)
    
    const multiplicador = 0.97 * (combinacoes25k / combinacoesEstrelas)
    
    return Math.round(multiplicador * 100) / 100 // Arredonda para 2 casas decimais
  }

  const getWallet = async () => {
    try {
      const response = await HttpApi.get('profile/wallet')
      wallet.value = response.data.wallet
      isLoadingWallet.value = false
      
      // Atualiza o saldo no jogo
      if (wallet.value && wallet.value.balance !== undefined) {
        jogo.saldo = Number(wallet.value.balance)
      }
    } catch (error) {
      console.error('Erro ao buscar wallet:', error)
      isLoadingWallet.value = false
    }
  }

  // Jogo
  const jogo = reactive({
    intervaloDeValores: [1, 2, 3, 4, 5, 6, 7, 8, 12, 20, 50, 100],
    valorBet: 1.00,
    numeros: [0,1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24],
    estadojogo: "pendente",
    acertos: [],
    indiceGameOver: null,
    quantidadeDeMinas: 2,
    indicesMinas: [],
    saldo: 0.00, // Será atualizado pelo fetchBalance
    valorCashOut: 0.00,
    valorProximoCashOut: 0.00,
    multiplicador: multiplicadoresIniciais[2], // Inicializa com valor da tabela para 2 bombas
    girar: false,
    gameId: null
  })

  const visibilidadeValoresPredefinidos = ref(false)

  // Inicializa o jogo buscando o wallet igual aos outros jogos
  const initializeGame = async () => {
    if (authStore.isAuth) {
      await getWallet()
    } else {
      isLoadingWallet.value = false
      jogo.saldo = 0.00
    }
  }

  // Chama a inicialização imediatamente
  initializeGame()

  const setVisibilidadeValoresPredefinidos = (valor) => {
    visibilidadeValoresPredefinidos.value = valor
  }

  const alterarValor = (acao) => {
    let proximoValor = 0
    let intervaloDeValores = jogo.intervaloDeValores
    let valorBet = jogo.valorBet

    for(let i = 0; i <= intervaloDeValores.length; i++){
      if(acao == "aumentar" && intervaloDeValores[i] > valorBet){
        proximoValor = intervaloDeValores[i]
        break
      }
      else if(acao == "diminuir" && intervaloDeValores[i] >= valorBet){
        proximoValor = intervaloDeValores[i]
        break
      }
    }
  
    let indexProximoValor = intervaloDeValores.findIndex((valor) => valor == proximoValor)
    jogo.valorBet = (acao == "aumentar") ? intervaloDeValores[indexProximoValor] : intervaloDeValores[indexProximoValor - 1]
  }

  const setValorBet = (valor) => {
    jogo.valorBet = valor
  }

  const escolherQuantidadeDeMinas = (e) => {
    jogo.quantidadeDeMinas = e.target.value
    // Calcular multiplicador usando a fórmula da Spribe
    jogo.multiplicador = calcularProximoMultiplicador(jogo.quantidadeDeMinas, 1)
  }

  const iniciarPartida = async () => {
    try {
      if (isLoadingWallet.value) {
        return
      }

      // Verifica se o usuário está autenticado
      if (!authStore.isAuth) {
        console.error('Usuário não autenticado - redirecionando para login')
        window.location.href = '/login'
        return
      }

      playSound('play.mp3')
      
      const response = await HttpApi.post('mines/start', {
        bet_amount: jogo.valorBet,
        mines_count: jogo.quantidadeDeMinas
      })
      
      const data = response.data
      
      if (data.success) {
        jogo.estadojogo = "iniciou"
        jogo.indicesMinas = data.mine_positions
        jogo.valorCashOut = jogo.valorBet
        
        // Calcular próximo valor de cashout usando a fórmula da Spribe
        const nextMultiplier = calcularProximoMultiplicador(jogo.quantidadeDeMinas, 1)
        jogo.valorProximoCashOut = jogo.valorBet * nextMultiplier
        
        jogo.saldo = data.balance
        jogo.gameId = data.game_id
        
        // Atualizar multiplicador inicial usando a tabela fixa
        jogo.multiplicador = nextMultiplier
        
        // Atualiza a wallet global também
        if (wallet.value) {
            wallet.value.balance = data.balance
        }
      } else {
        // Mostrar erro
        console.error(data.message)
      }
    } catch (error) {
      console.error('Erro ao iniciar partida:', error)
      // Se houver erro, tenta atualizar o saldo
      await getWallet()
    }
  }

  const clicarNoCard = async (indice) => {
    if(jogo.indicesMinas.includes(indice)){
      playSound('erro.mp3')
      girarCards(indice)
    }else if(!jogo.acertos.includes(indice)){
      try {
        playSound('acerto.mp3')
        
        const response = await HttpApi.post('mines/reveal', {
          game_id: jogo.gameId,
          position: indice
        })
        
        const data = response.data
        
        if (data.success) {
          if (data.is_mine) {
            // Game over
            jogo.indiceGameOver = indice
            jogo.estadojogo = "finalizou"
            jogo.girar = true
            reiniciarJogo(data.balance)
            
            // Atualiza wallet
            if (wallet.value) wallet.value.balance = data.balance
          } else {
            // Continua jogando
            jogo.acertos.push(indice)
            jogo.valorCashOut = data.potential_win
            
            // Calcular próximo payout usando a fórmula da Spribe
            const revealedCount = data.revealed_count
            const proximoMultiplicador = calcularProximoMultiplicador(jogo.quantidadeDeMinas, revealedCount + 1)
            jogo.valorProximoCashOut = jogo.valorBet * proximoMultiplicador
            
            jogo.multiplicador = data.multiplier
          }
        }
      } catch (error) {
        console.error('Erro ao revelar célula:', error)
      }
    }
  }

  const darCashOut = async () => {
    try {
      playSound('cashout.mp3')
      
      const response = await HttpApi.post('mines/cashout', {
        game_id: jogo.gameId
      })
      
      const data = response.data
      
      if (data.success) {
        jogo.saldo = data.balance
        jogo.indiceGameOver = null
        jogo.estadojogo = "finalizou"
        jogo.girar = true
        jogo.indicesMinas = data.mine_positions
        
        // Atualiza wallet
        if (wallet.value) wallet.value.balance = data.balance
        
        reiniciarJogo(data.balance)
      }
    } catch (error) {
      console.error('Erro ao fazer cashout:', error)
    }
  }

  const girarCards = (indiceGameOver) => {
    let novoSaldo = jogo.saldo + ((indiceGameOver) ? 0 : jogo.valorCashOut)

    jogo.indiceGameOver = indiceGameOver
    jogo.estadojogo = "finalizou"
    jogo.girar = true
    jogo.saldo = novoSaldo

    reiniciarJogo(novoSaldo)
  }

  const reiniciarJogo = (novoSaldo) => {
    setTimeout(() => {
      jogo.saldo = novoSaldo
      jogo.estadojogo = "pendente"
      jogo.acertos = []
      jogo.indiceGameOver = null
      jogo.indicesMinas = []
      jogo.girar = false
      jogo.valorCashOut = 0.0
      jogo.multiplicador = multiplicadoresIniciais[jogo.quantidadeDeMinas] || 1.00
      jogo.gameId = null
    }, 4000)
  }

  const gerarindiceDasMinas = () => {
    let indices = []

     while(indices.length < jogo.quantidadeDeMinas){
      let numeroRandomico = Math.floor(Math.random() * 25)
      if (!indices.includes(numeroRandomico)) indices.push(numeroRandomico)
    }

    return indices
  }

  // Função para forçar atualização do saldo
  const refreshBalance = async () => {
    await getWallet()
  }

  return {
    jogo,
    wallet,
    isLoadingWallet,
    visibilidadeValoresPredefinidos, 
    setVisibilidadeValoresPredefinidos,
    setValorBet, 
    alterarValor, 
    escolherQuantidadeDeMinas, 
    iniciarPartida, 
    clicarNoCard, 
    darCashOut, 
    gerarindiceDasMinas,
    getWallet,
    initializeGame,
    refreshBalance,
    calcularProximoMultiplicador
  }
}
