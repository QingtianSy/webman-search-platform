<template>
  <div class="page">
    <h1>钱包与套餐</h1>
    <pre>{{ text }}</pre>
  </div>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue';
import { getCurrentPlan, getWalletDetail } from '../../api/user';

const text = ref('加载中...');

onMounted(async () => {
  try {
    const [wallet, plan] = await Promise.all([getWalletDetail(), getCurrentPlan()]);
    text.value = JSON.stringify({ wallet: wallet.data, plan: plan.data }, null, 2);
  } catch (error: any) {
    text.value = String(error);
  }
});
</script>
