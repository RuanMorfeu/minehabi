<template>
  <div class="containerPrincipal">
    <!-- Cabeçalho com multiplicador e bombas -->
    <div class="header-top">
      <div class="bombas-header">
        <span class="label-minas">Minas:</span>
        <div class="select-wrapper">
          <span class="valor-minas">{{ jogo.quantidadeDeMinas }}</span>
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="icon-arrow">
            <path d="m6 9 6 6 6-6"/>
          </svg>
          <select 
            class="bombas-select-overlay"
            @change="escolherQuantidadeDeMinas"
            :value="jogo.quantidadeDeMinas"
            :disabled="jogo.estadojogo == 'iniciou'"
          >
            <option
              v-for="numero in jogo.numeros.filter(n => n < 24)"
              :key="numero"
              :value="numero + 1"
            >
              {{ numero + 1 }}
            </option>
          </select>
        </div>
      </div>

      <div class="multiplicador-header">
        <span class="next-label">Seguinte:</span>
        <span class="next-value">{{ jogo.multiplicador.toFixed(2) }}x</span>
      </div>
    </div>

    <Saldo
      :jogo="jogo"
      :wallet="wallet"
      @refresh="refreshBalance"
    />

    <ContainerMines>
      <MinesGrid
        :jogo="jogo"
        @clicarNoCard="clicarNoCard"
      />

      <div></div>
    </ContainerMines>

    <div class="footer-controls">
      <div class="acoes-jogo">
        <BotaoMines
          :jogo="jogo"
          @iniciarPartida="iniciarPartida"
          @darCashOut="darCashOut"
        />
      </div>

      <div class="area-aposta">
        <Bet
          :jogo="jogo"
          @alterarValor="alterarValor"
          :visibilidadeValoresPredefinidos="visibilidadeValoresPredefinidos"
          @setVisibilidadeValoresPredefinidos="setVisibilidadeValoresPredefinidos"
          @setValorBet="setValorBet"
        />
      </div>
    </div>

    <!-- Barra Inferior Mobile Fixa -->
    <div class="barra-inferior-mobile">
      <div class="grupo-esquerda">
        <button class="btn-voltar" @click="$router.go(-1)">
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
            <path d="m15 18-6-6 6-6"/>
          </svg>
          Voltar
        </button>
        <button class="btn-ajuda">
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="10"/>
            <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/>
            <path d="M12 17h.01"/>
          </svg>
        </button>
      </div>

      <div class="grupo-direita">
        <div v-if="jogo.estadojogo == 'finalizou' && !jogo.indiceGameOver" class="lucro-mobile">
          +{{ Number(jogo.valorCashOut).toFixed(2) }} EUR
        </div>
        <div class="info-saldo">
          <span class="valor-saldo">{{ (wallet && wallet.total_balance != null) ? Number(wallet.total_balance).toFixed(2) : Number(jogo.saldo).toFixed(2) }}</span>
          <span class="moeda-saldo">EUR</span>
        </div>
        <button class="btn-menu-hamburger">
          <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <line x1="3" y1="12" x2="21" y2="12"/>
            <line x1="3" y1="6" x2="21" y2="6"/>
            <line x1="3" y1="18" x2="21" y2="18"/>
          </svg>
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import Saldo from './Saldo.vue'
import SeletorQuantidadeDeMinas from './SeletorQuantidadeDeMinas.vue'
import ContainerMines from './ContainerMines.vue'
import MinesGrid from './MinesGrid.vue'
import Bet from './Bet.vue'
import BotaoMines from './BotaoMines.vue'
import ValoresPreDefinidos from './ValoresPreDefinidos.vue'
import { useMines } from '@/composables/useMines.js'

const {
  jogo, wallet, isLoadingWallet, visibilidadeValoresPredefinidos, 
  setVisibilidadeValoresPredefinidos, setValorBet, alterarValor, 
  escolherQuantidadeDeMinas, iniciarPartida, clicarNoCard, darCashOut,
  refreshBalance
} = useMines()
</script>

<style scoped>
.containerPrincipal {
  width: 100%;
  min-height: 100vh;
  min-height: 100dvh; /* Mobile Fullscreen */
  display: flex;
  flex-direction: column;
  align-items: center;
  background-image: linear-gradient(to right, #0484cc, #0454dc);
  padding-bottom: 20px; /* Espaço para o footer não colar */
}

/* Cabeçalho topo com multiplicador e bombas */
.header-top {
  width: 100%;
  max-width: 415px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 10px 15px;
  background: transparent; /* Fundo transparente ou ajustado conforme necessário */
  margin-top: 10px;
}

.bombas-header {
  display: flex;
  align-items: center;
  background-color: #0c4c84; /* Azul escuro da pílula */
  padding: 8px 15px;
  border-radius: 20px;
  border: 1px solid rgba(255, 255, 255, 0.1);
  box-shadow: inset 0 0 5px rgba(0,0,0,0.2);
  color: white;
  font-weight: 600;
  gap: 5px;
  position: relative;
  min-width: 110px;
  justify-content: center;
}

.label-minas {
  font-size: 14px;
}

.select-wrapper {
  display: flex;
  align-items: center;
  gap: 3px;
  position: relative;
}

.valor-minas {
  font-size: 14px;
  font-weight: 700;
}

.icon-arrow {
  margin-top: 2px;
}

.bombas-select-overlay {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  opacity: 0;
  cursor: pointer;
}

.multiplicador-header {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 5px;
  background: #FFC107; /* Amarelo */
  padding: 8px 20px;
  border-radius: 20px;
  box-shadow: 0 2px 5px rgba(0,0,0,0.2);
  min-width: 140px;
}

.next-label {
  font-size: 14px;
  font-weight: 600;
  color: #0c4c84; /* Azul escuro para contraste no amarelo */
  text-transform: none;
  letter-spacing: 0;
}

.next-value {
  font-size: 14px;
  font-weight: 800;
  color: #0c4c84;
}

/* Ocultar barra inferior em desktop */
.barra-inferior-mobile {
  display: none;
}


.footer-controls {
  width: 100%;
  display: flex;
  flex-direction: column;
  gap: 15px;
  padding: 10px;
}

.acoes-jogo {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  width: 100%;
}

.btn-auto {
  width: 50px;
  height: 50px;
  border-radius: 50%;
  background: #0052cc; /* Azul mais escuro que o fundo */
  border: none;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #fff;
  box-shadow: 0 4px 0 #003380; /* Efeito 3D */
  cursor: pointer;
}

.btn-auto:active {
  transform: translateY(2px);
  box-shadow: 0 2px 0 #003380;
}

.area-aposta {
  width: 100%;
  display: flex;
  justify-content: center;
}

@media screen and (max-width: 1055px) {
  .containerPrincipal {
    padding-bottom: calc(80px + env(safe-area-inset-bottom)) !important; /* Garante espaço para a barra fixa + safe area */
  }

  .header-top {
    padding: 10px 15px;
    margin-top: 5px;
  }
  
  .bombas-header {
    min-width: 100px;
    padding: 6px 12px;
  }
  
  .multiplicador-header {
    min-width: 130px;
    padding: 6px 15px;
  }

  .footer-controls {
    padding: 10px 5%;
    margin-bottom: 0;
  }

  /* Mostrar barra inferior em mobile */
  .barra-inferior-mobile {
    display: flex !important; /* Forçar display flex */
    justify-content: space-between;
    align-items: center;
    position: fixed !important;
    bottom: 0 !important;
    left: 0 !important;
    width: 100% !important;
    min-height: 70px;
    background-color: #0267A5 !important;
    padding: 10px 20px;
    padding-bottom: calc(15px + env(safe-area-inset-bottom)) !important;
    z-index: 2147483647 !important; /* Z-index máximo permitido */
    box-shadow: 0 -4px 20px rgba(0,0,0,0.5);
    border-top: 1px solid rgba(255,255,255,0.2);
  }

  .grupo-esquerda, .grupo-direita {
    display: flex;
    align-items: center;
    gap: 15px;
  }

  .btn-voltar {
    background: #004a7c;
    border: 1px solid #005f9e;
    color: white;
    border-radius: 20px;
    padding: 0 15px;
    height: 36px;
    font-size: 14px;
    font-weight: 700;
    display: flex;
    align-items: center;
    gap: 6px;
    cursor: pointer;
    transition: all 0.2s;
  }

  .btn-voltar:hover {
    background: #005f9e;
    transform: translateY(-1px);
  }

  .btn-voltar:active {
    transform: translateY(0);
  }

  .btn-mines-menu {
    background: #004a7c;
    border: 1px solid #005f9e;
    color: white;
    border-radius: 20px;
    padding: 0 15px;
    font-size: 14px;
    font-weight: 800;
    display: flex;
    align-items: center;
    gap: 8px;
    height: 40px;
    box-shadow: inset 0 2px 4px rgba(0,0,0,0.3);
    white-space: nowrap;
  }

  .btn-ajuda {
    background: #FF9800;
    border: none;
    border-radius: 50%;
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #333;
    font-weight: bold;
    box-shadow: 0 2px 0 #E65100;
    flex-shrink: 0;
  }
  
  .info-saldo {
    display: flex;
    align-items: center;
    gap: 8px;
    color: white;
    font-weight: 800;
    font-size: 18px;
    white-space: nowrap;
    text-shadow: 0 1px 2px rgba(0,0,0,0.3);
  }

  .lucro-mobile {
    padding: 4px 10px;
    border-radius: 15px;
    background-color: #15902D;
    color: #fff;
    font-size: 13px;
    font-weight: bold;
    margin-right: 5px;
    white-space: nowrap;
    animation: fadeIn 0.3s ease;
    box-shadow: 0 2px 4px rgba(0,0,0,0.2);
  }

  @keyframes fadeIn {
    from { opacity: 0; transform: translateY(5px); }
    to { opacity: 1; transform: translateY(0); }
  }

  .moeda-saldo {
    color: #a3d4ff;
    font-size: 14px;
    font-weight: 700;
  }

  .btn-menu-hamburger {
    background: #004a7c;
    border: 1px solid #005f9e;
    border-radius: 50%;
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    box-shadow: 0 2px 4px rgba(0,0,0,0.3);
    flex-shrink: 0;
    cursor: pointer;
  }
}
</style>
