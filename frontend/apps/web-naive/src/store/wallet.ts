import { acceptHMRUpdate, defineStore } from 'pinia';

import {
  getCurrentPlanApi,
  getWalletApi,
  type WalletApi,
} from '#/api/user/wallet';

interface WalletState {
  wallet: WalletApi.Wallet | null;
  currentPlan: WalletApi.CurrentPlan | null;
  /** 毫秒时间戳；用于短 TTL 缓存防止多处同时拉接口 */
  fetchedAt: number;
  loading: boolean;
}

const FIVE_MIN = 5 * 60 * 1000;

/**
 * 钱包/当前套餐 store。
 *
 * 为什么要建 store：搜索前额度预检 + dashboard 概览 + 钱包页三处要读同一份数据，
 * 直接在组件里各拉各的会造成 3 倍请求数且状态不一致。
 *
 * 刷新策略：
 *   - 默认 5 分钟 TTL（fetchedAt 比对）
 *   - 搜索/消费成功后主动调 invalidate()（配额变化）
 *   - 充值/续费成功后也应 invalidate()
 *
 * 空对象语义：后端在未开通钱包/无订阅时返回 {}，这里把"有 id"作为"真的有"的判断，
 * 而不是 null，避免把空对象误判成已开通。
 */
export const useWalletStore = defineStore('wallet', {
  state: (): WalletState => ({
    wallet: null,
    currentPlan: null,
    fetchedAt: 0,
    loading: false,
  }),
  getters: {
    isStale(state): boolean {
      return Date.now() - state.fetchedAt > FIVE_MIN;
    },
    hasWallet(state): boolean {
      return !!state.wallet?.id;
    },
    hasActivePlan(state): boolean {
      return !!state.currentPlan?.id;
    },
    balance(state): number {
      return Number(state.wallet?.balance ?? 0);
    },
    remainingQuota(state): number | null {
      if (!state.currentPlan?.id) return null;
      if (state.currentPlan.is_unlimited) return Number.POSITIVE_INFINITY;
      return Number(state.currentPlan.remain_quota ?? 0);
    },
  },
  actions: {
    async ensureLoaded(force = false) {
      if (!force && !this.isStale && this.fetchedAt > 0) return;
      await this.refresh();
    },
    async refresh() {
      this.loading = true;
      try {
        const [wallet, plan] = await Promise.all([
          getWalletApi().catch(() => null),
          getCurrentPlanApi().catch(() => null),
        ]);
        this.wallet = wallet ?? null;
        this.currentPlan = plan ?? null;
        this.fetchedAt = Date.now();
      } finally {
        this.loading = false;
      }
    },
    invalidate() {
      this.fetchedAt = 0;
    },
    $reset() {
      this.wallet = null;
      this.currentPlan = null;
      this.fetchedAt = 0;
      this.loading = false;
    },
  },
});

// Vite HMR：组件里持有 store 实例时也能热更
if (import.meta.hot) {
  import.meta.hot.accept(acceptHMRUpdate(useWalletStore, import.meta.hot));
}
