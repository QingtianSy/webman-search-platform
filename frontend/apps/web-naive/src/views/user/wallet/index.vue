<script lang="ts" setup>
import { computed, onMounted } from 'vue';

import { NAlert, NButton, NCard, NSpin, NStatistic, NTag } from 'naive-ui';

import { useWalletStore } from '#/store/wallet';

const walletStore = useWalletStore();

const loading = computed(() => walletStore.loading);
const wallet = computed(() => walletStore.wallet);
const plan = computed(() => walletStore.currentPlan);

const balance = computed(() => walletStore.balance);
const frozen = computed(() => Number(wallet.value?.frozen_balance ?? 0));
const totalRecharge = computed(() => Number(wallet.value?.total_recharge ?? 0));
const totalConsume = computed(() => Number(wallet.value?.total_consume ?? 0));

const remainingQuotaText = computed(() => {
  const q = walletStore.remainingQuota;
  if (q === null) return '—';
  if (q === Number.POSITIVE_INFINITY) return '不限';
  return String(q);
});

function refresh() {
  walletStore.invalidate();
  walletStore.ensureLoaded(true);
}

onMounted(() => walletStore.ensureLoaded());
</script>

<template>
  <div class="p-6">
    <NSpin :show="loading">
      <NCard title="我的钱包" class="mb-4">
        <template #header-extra>
          <NButton size="small" :loading="loading" @click="refresh">
            刷新
          </NButton>
        </template>

        <NAlert
          v-if="!walletStore.hasWallet"
          type="info"
          :show-icon="false"
          class="mb-3"
        >
          尚未开通钱包，首次充值时会自动创建。
        </NAlert>

        <div class="grid gap-4 md:grid-cols-4">
          <NStatistic label="可用余额" :value="balance" />
          <NStatistic label="冻结" :value="frozen" />
          <NStatistic label="累计充值" :value="totalRecharge" />
          <NStatistic label="累计消耗" :value="totalConsume" />
        </div>
      </NCard>

      <NCard title="当前套餐">
        <NAlert
          v-if="!walletStore.hasActivePlan"
          type="warning"
          :show-icon="false"
        >
          暂无活跃套餐。可前往「套餐中心」选购或联系管理员分配。
        </NAlert>

        <template v-else>
          <div class="mb-3 flex items-center gap-2">
            <span class="text-lg font-medium">{{ plan?.name ?? '—' }}</span>
            <NTag v-if="plan?.is_unlimited" type="success" size="small">
              不限次
            </NTag>
          </div>

          <div class="grid gap-4 md:grid-cols-3">
            <NStatistic label="剩余额度" :value="remainingQuotaText" />
            <NStatistic label="已用" :value="Number(plan?.used_quota ?? 0)" />
            <NStatistic
              label="到期时间"
              :value="plan?.expire_at ?? '永久'"
            />
          </div>
        </template>
      </NCard>
    </NSpin>
  </div>
</template>
