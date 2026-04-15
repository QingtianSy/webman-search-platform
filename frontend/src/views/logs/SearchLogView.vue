<template>
  <div class="page">
    <h1>搜题日志</h1>
    <p>用户侧日志页示例。</p>
    <pre>{{ dataText }}</pre>
  </div>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue';
import { getSearchLogs } from '../../api/business';

const dataText = ref('加载中...');

onMounted(async () => {
  try {
    const { data } = await getSearchLogs();
    dataText.value = JSON.stringify(data, null, 2);
  } catch (error: any) {
    dataText.value = String(error);
  }
});
</script>
