<template>
  <div class="page billing-page">
    <h1>钱包与套餐</h1>
    <div class="cards">
      <div class="stat-card">
        <div class="label">余额</div>
        <div class="value">¥ {{ wallet?.balance || '0.00' }}</div>
      </div>
      <div class="stat-card">
        <div class="label">当前套餐</div>
        <div class="value">{{ plan?.name || '无套餐' }}</div>
      </div>
      <div class="stat-card">
        <div class="label">剩余额度</div>
        <div class="value">{{ plan?.remain_quota ?? 0 }}</div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue';
import { getCurrentPlan, getWalletDetail } from '../../api/user';

const wallet = ref<any>(null);
const plan = ref<any>(null);

onMounted(async () => {
  try {
    const [walletRes, planRes] = await Promise.all([getWalletDetail(), getCurrentPlan()]);
    wallet.value = walletRes.data?.data || null;
    plan.value = planRes.data?.data || null;
  } catch (error) {
    console.error(error);
  }
});
</script>
