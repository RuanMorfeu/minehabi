<template>
  <!-- Este componente não renderiza nada visualmente, apenas gerencia os pop-ups -->
</template>

<script>
import { onMounted, watch } from 'vue';
import { usePopupStore } from '@/Stores/PopupStore.js';
import { useAuthStore } from '@/Stores/Auth.js';
import { useRoute } from 'vue-router';
import HttpApi from '@/Services/HttpApi.js';

export default {
  name: 'PopupManager',
  
  setup() {
    const popupStore = usePopupStore();
    const authStore = useAuthStore();
    const route = useRoute();

    function getUserId() {
      return authStore.user?.id || 'guest';
    }

    function hasPopupBeenShown(popup) {
      if (!popup || !popup.id) return false;
      const popupId = Number(popup.id);

      try {
        if (popup.browser_persistent) {
          const key = 'browser_viewed_popups';
          const viewedPopups = JSON.parse(localStorage.getItem(key)) || [];
          return viewedPopups.includes(popupId);
        }

        const userId = getUserId();
        if (userId === 'guest') return false;

        const key = `viewed_popups_${userId}`;
        const viewedPopups = JSON.parse(localStorage.getItem(key)) || [];
        return viewedPopups.includes(popupId);

      } catch (error) {
        console.error(`Erro ao verificar se o popup ${popupId} foi mostrado:`, error);
        return false;
      }
    }

    function hasPopupBeenRedeemed(popup) {
      if (!popup || !popup.id) return false;
      const popupId = Number(popup.id);

      try {
        if (popup.browser_persistent) {
          const key = 'browser_redeemed_popups';
          const redeemedPopups = JSON.parse(localStorage.getItem(key)) || [];
          return redeemedPopups.includes(popupId);
        }

        const userId = getUserId();
        if (userId === 'guest') return false;

        const key = `redeemed_popups_${userId}`;
        const redeemedPopups = JSON.parse(localStorage.getItem(key)) || [];
        return redeemedPopups.includes(popupId);

      } catch (error) {
        console.error(`Erro ao verificar se o popup ${popupId} foi resgatado:`, error);
        return false;
      }
    }

    async function checkUserDeposits() {
      if (!authStore.isAuth) return false;
      try {
        const response = await HttpApi.get('wallet/deposit/has-deposits');
        return response.data.has_deposits;
      } catch (error) {
        console.error('Erro ao verificar depósitos do usuário via API. Usando fallback.', error);
        return authStore.user?.has_deposit || false;
      }
    }

    function getInfluencerCode() {
      const urlParams = new URLSearchParams(window.location.search);
      const codeFromUrl = urlParams.get('ref') || urlParams.get('influencer') || urlParams.get('aff');
      if (codeFromUrl) {
        localStorage.setItem('influencer_code', codeFromUrl);
        return codeFromUrl;
      }
      return localStorage.getItem('influencer_code');
    }

    async function checkPopups() {
      if (!authStore.isAuth) {
        return;
      }

      const influencerCode = getInfluencerCode();
      const activePopups = await popupStore.fetchAllActivePopups({}, influencerCode);
      if (!activePopups || activePopups.length === 0) {
        return;
      }

      const hasDeposit = await checkUserDeposits();

      const eligiblePopups = activePopups.filter(popup => {
        if (popup.influencer_code && popup.influencer_code !== influencerCode) return false;
        if (popup.target_user_type === 'with_deposit' && !hasDeposit) return false;
        if (popup.target_user_type === 'without_deposit' && hasDeposit) return false;
        if (popup.target_user_type === 'affiliate' && !authStore.user?.inviter_code) return false;
        return true;
      });

      if (eligiblePopups.length === 0) {
        return;
      }

      const unviewedPopups = eligiblePopups.filter(popup => {
        if (popup.require_redemption) {
          return !hasPopupBeenRedeemed(popup);
        }
        if (popup.show_only_once) {
          return !hasPopupBeenShown(popup);
        }
        return true;
      });

      if (unviewedPopups.length === 0) {
        return;
      }

      const popupToShow = unviewedPopups[0];

      popupStore.showPopup({
        id: popupToShow.id,
        title: popupToShow.title,
        message: popupToShow.message,
        image: popupToShow.image,
        buttonText: popupToShow.button_text,
        redirectUrl: popupToShow.redirect_url,
        showOnlyOnce: popupToShow.show_only_once,
        requireRedemption: popupToShow.require_redemption,
        browser_persistent: popupToShow.browser_persistent,
        game_free_rounds_active_popup: popupToShow.game_free_rounds_active_popup ?? false,
        game_code_rounds_free_popup: popupToShow.game_code_rounds_free_popup ?? null,
        game_name_rounds_free_popup: popupToShow.game_name_rounds_free_popup ?? null,
        rounds_free_popup: popupToShow.rounds_free_popup ?? 0
      });
    }

    onMounted(() => {
      setTimeout(checkPopups, 1500);
    });

    watch(() => authStore.isAuth, (newValue, oldValue) => {
      if (newValue && !oldValue) {
        setTimeout(checkPopups, 1500);
      }
    });

    watch(() => route.path, () => {
        setTimeout(checkPopups, 1500);
    });

    return {};
  }
};
</script>
