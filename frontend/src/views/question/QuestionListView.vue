<template>
  <div class="page">
    <h1>题目列表</h1>
    <p>管理员菜单示例页。</p>
    <pre>{{ dataText }}</pre>
  </div>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue';
import { getQuestionList } from '../../api/business';

const dataText = ref('加载中...');

onMounted(async () => {
  try {
    const { data } = await getQuestionList();
    dataText.value = JSON.stringify(data, null, 2);
  } catch (error: any) {
    dataText.value = String(error);
  }
});
</script>
