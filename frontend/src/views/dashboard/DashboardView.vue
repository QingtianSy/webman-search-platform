<template>
  <div class="page">
    <h1>工作台</h1>
    <p>统一用户体系下，按 roles / permissions / menus 动态展示内容。</p>
    <pre>{{ dataText }}</pre>
  </div>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue';
import { getDashboardOverview } from '../../api/business';

const dataText = ref('加载中...');

onMounted(async () => {
  try {
    const { data } = await getDashboardOverview();
    dataText.value = JSON.stringify(data, null, 2);
  } catch (error: any) {
    dataText.value = String(error);
  }
});
</script>
