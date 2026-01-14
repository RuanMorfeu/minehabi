import { ref, reactive } from 'vue'
import { useAuthStore } from '@/Stores/Auth.js'
import HttpApi from '@/Services/HttpApi.js'

export function useChicken() {
  const authStore = useAuthStore()
  
  // Wallet reativo
  const wallet = ref(null)
  const isLoadingWallet = ref(true)
  
  // Sons
  const playSound = (soundFile) => {
    // Ajustar caminho conforme a estrutura de pastas criada
    const audio = new Audio(`/assets/sounds/chicken/${soundFile}`)
    audio.play().catch(e => console.log('Erro ao tocar som:', e))
  }
  
  // Coeficientes (Copiados do Controller para preview)
  const coefficients = {
    easy: [ 1.03, 1.07, 1.12, 1.17, 1.23, 1.29, 1.36, 1.44, 1.53, 1.63, 1.74, 1.86, 1.99, 2.13, 2.29, 2.46, 2.64, 2.84, 3.06, 3.30, 3.56, 3.84, 4.15, 4.48, 4.84, 5.23, 5.65, 6.11, 6.61, 7.15 ],
    medium: [ 1.12, 1.28, 1.47, 1.70, 1.98, 2.33, 2.76, 3.32, 4.03, 4.96, 6.15, 7.70, 9.75, 12.45, 16.05, 20.90, 27.50, 36.50, 48.80, 65.70, 89.20, 121.80, 167.40, 231.20, 320.80, 447.50, 627.00, 881.00, 1243.00, 1758.00 ],
    hard: [ 1.23, 1.55, 1.98, 2.56, 3.36, 4.49, 5.49, 7.53, 10.56, 15.21, 22.35, 33.50, 50.80, 77.90, 120.50, 188.20, 296.50, 470.80, 752.50, 1210.00, 1956.00, 3180.00, 5190.00, 8500.00, 13970.00, 23000.00, 37950.00, 62750.00, 103900.00, 172200.00 ],
    hardcore: [ 1.63, 2.80, 4.95, 9.08, 15.21, 30.12, 62.96, 140.24, 337.19, 890.19, 2450.00, 6950.00, 20300.00, 61500.00, 190500.00, 603000.00, 1940000.00, 6320000.00, 20800000.00, 69100000.00, 231000000.00, 778000000.00, 2640000000.00, 9010000000.00, 30900000000.00, 106500000000.00, 368500000000.00, 1280000000000.00, 4460000000000.00, 15600000000000.00 ]
  }

  const getWallet = async () => {
    try {
      const response = await HttpApi.get('profile/wallet')
      wallet.value = response.data.wallet
      isLoadingWallet.value = false
      
      if (wallet.value) {
        jogo.saldo = parseFloat(wallet.value.total_balance || 0)
      } else {
        jogo.saldo = 0.00
      }
    } catch (error) {
      console.error('Erro ao buscar wallet:', error)
      isLoadingWallet.value = false
      jogo.saldo = 0.00
    }
  }

  // Estado do Jogo
  const jogo = reactive({
    intervaloDeValores: [1, 2, 3, 4, 5, 6, 7, 8, 12, 20, 50, 100],
    valorBet: 1.00,
    dificuldade: 'easy',
    estadojogo: "pendente", // pendente, iniciou, finalizou
    currentStep: 0, // 0 = início
    history: [], // Histórico de passos (sucesso/falha)
    trapPosition: null, // Onde estava a armadilha (revelado no final)
    saldo: 0.00,
    valorCashOut: 0.00,
    valorProximoCashOut: 0.00,
    multiplicador: 1.00,
    gameId: null,
    loading: false
  })

  const visibilidadeValoresPredefinidos = ref(false)

  const initializeGame = async () => {
    if (authStore.isAuth) {
      await getWallet()
    } else {
      isLoadingWallet.value = false
      jogo.saldo = 0.00
    }
    // Atualiza multiplicador inicial
    atualizarProximoMultiplicador()
  }

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
    
    // Atualiza projeção de ganhos
    atualizarProximoMultiplicador()
  }

  const setValorBet = (valor) => {
    jogo.valorBet = valor
    atualizarProximoMultiplicador()
  }

  const setDificuldade = (dificuldade) => {
    jogo.dificuldade = dificuldade
    atualizarProximoMultiplicador()
  }
  
  const atualizarProximoMultiplicador = () => {
    // Pega o multiplicador do primeiro passo (índice 0)
    const nextMult = coefficients[jogo.dificuldade][jogo.currentStep]
    jogo.valorProximoCashOut = jogo.valorBet * nextMult
  }

  const iniciarPartida = async () => {
    try {
      if (isLoadingWallet.value || jogo.loading) return

      if (!authStore.isAuth) {
        window.location.href = '/login'
        return
      }

      jogo.loading = true
      playSound('button.webm') // Som de botão do chicken
      
      const response = await HttpApi.post('chicken/start', {
        bet_amount: jogo.valorBet,
        difficulty: jogo.dificuldade
      })
      
      const data = response.data
      
      if (data.success) {
        jogo.estadojogo = "iniciou"
        jogo.gameId = data.game_id
        jogo.saldo = data.balance
        jogo.currentStep = 0
        jogo.history = []
        jogo.trapPosition = null
        jogo.valorCashOut = jogo.valorBet
        jogo.multiplicador = 1.00
        
        // Atualiza wallet global
        if (wallet.value) {
            wallet.value.balance = data.balance
            wallet.value.total_balance = data.balance
        }
        
        atualizarProximoMultiplicador()
      }
    } catch (error) {
      console.error('Erro ao iniciar partida:', error)
      await getWallet()
    } finally {
        jogo.loading = false
    }
  }

  const jogarProximoPasso = async () => {
    try {
        if (jogo.loading || jogo.estadojogo !== 'iniciou') return
        
        jogo.loading = true
        playSound('step.webm')

        const response = await HttpApi.post('chicken/play', {
            game_id: jogo.gameId
        })

        const data = response.data

        if (data.success) {
            if (data.status === 'lost') {
                // Perdeu
                jogo.estadojogo = "finalizou"
                jogo.trapPosition = data.trap_position
                playSound('lose.webm')
                reiniciarJogo(jogo.saldo) // Saldo não muda pois já descontou na aposta
            } else {
                // Ganhou o passo
                jogo.currentStep = data.step
                jogo.multiplicador = data.multiplier
                jogo.valorCashOut = data.potential_win
                
                // Próximo valor
                const nextStepIndex = data.step // Próximo índice (atual é step-1)
                const nextMult = coefficients[jogo.dificuldade][nextStepIndex] || 0
                jogo.valorProximoCashOut = jogo.valorBet * nextMult
                
                playSound('step.webm') // Ou win.webm se for relevante
            }
        }
    } catch (error) {
        console.error('Erro ao jogar passo:', error)
    } finally {
        jogo.loading = false
    }
  }

  const darCashOut = async () => {
    try {
        if (jogo.loading || jogo.estadojogo !== 'iniciou') return
        
        jogo.loading = true
        playSound('win.webm')
        
        const response = await HttpApi.post('chicken/cashout', {
            game_id: jogo.gameId
        })
        
        const data = response.data
        
        if (data.success) {
            jogo.saldo = data.balance
            jogo.estadojogo = "finalizou"
            jogo.winAmount = data.win_amount
            
            if (wallet.value) {
                wallet.value.balance = data.balance
                wallet.value.total_balance = data.balance
            }
            
            reiniciarJogo(data.balance)
        }
    } catch (error) {
        console.error('Erro ao fazer cashout:', error)
    } finally {
        jogo.loading = false
    }
  }

  const reiniciarJogo = (novoSaldo) => {
    setTimeout(() => {
      jogo.saldo = novoSaldo
      jogo.estadojogo = "pendente"
      jogo.currentStep = 0
      jogo.history = []
      jogo.trapPosition = null
      jogo.valorCashOut = 0.0
      jogo.multiplicador = 1.00
      jogo.gameId = null
      atualizarProximoMultiplicador()
    }, 3000)
  }

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
    setDificuldade, 
    iniciarPartida, 
    jogarProximoPasso, 
    darCashOut, 
    getWallet,
    initializeGame,
    refreshBalance,
    coefficients
  }
}
