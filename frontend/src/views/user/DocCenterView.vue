<template>
  <div class="page panel-grid">
    <div class="panel">
      <h1>文档中心</h1>
      <h2>配置与帮助</h2>
      <pre>{{ configText }}</pre>
    </div>
    <div class="panel">
      <h2>文档分类</h2>
      <ul>
        <li v-for="item in categories" :key="item.id">
          {{ item.name }}
        </li>
      </ul>
    </div>
  </div>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue';
import { http } from '../../api/http';
import { getDocConfig } from '../../api/user';

const configText = ref('加载中...');
const categories = ref<any[]>([]);

onMounted(async () => {
  try {
    const [configRes, categoryRes] = await Promise.all([
      getDocConfig(),
      http.get('/user/doc/category/list'),
    ]);
    configText.value = JSON.stringify(configRes.data, null, 2);
    categories.value = categoryRes.data?.data?.list || [];
  } catch (error: any) {
    configText.value = String(error);
  }
});
</script>
