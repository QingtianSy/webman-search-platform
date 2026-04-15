<template>
  <div class="page">
    <h1>采集任务</h1>
    <pre>{{ text }}</pre>
  </div>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue';
import { getCollectTasks } from '../../api/user';

const text = ref('加载中...');

onMounted(async () => {
  try {
    const { data } = await getCollectTasks();
    text.value = JSON.stringify(data, null, 2);
  } catch (error: any) {
    text.value = String(error);
  }
});
</script>
