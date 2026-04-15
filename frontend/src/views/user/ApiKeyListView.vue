<template>
  <div class="page">
    <h1>API Key 列表</h1>
    <pre>{{ text }}</pre>
  </div>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue';
import { getApiKeyList } from '../../api/user';

const text = ref('加载中...');

onMounted(async () => {
  try {
    const { data } = await getApiKeyList();
    text.value = JSON.stringify(data, null, 2);
  } catch (error: any) {
    text.value = String(error);
  }
});
</script>
