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
          <button class="secondary" @click="loadDetail(item.slug || 'search-api')">{{ item.name }}</button>
        </li>
      </ul>
    </div>
    <div class="panel">
      <h2>文档详情</h2>
      <pre>{{ detailText }}</pre>
    </div>
  </div>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue';
import { getDocArticleDetail, getDocCategories, getDocConfig } from '../../api/user';

const configText = ref('加载中...');
const detailText = ref('请选择一个文档分类查看详情');
const categories = ref<any[]>([]);

async function loadDetail(slug: string) {
  const { data } = await getDocArticleDetail(slug);
  detailText.value = JSON.stringify(data, null, 2);
}

onMounted(async () => {
  try {
    const [configRes, categoryRes] = await Promise.all([
      getDocConfig(),
      getDocCategories(),
    ]);
    configText.value = JSON.stringify(configRes.data, null, 2);
    categories.value = categoryRes.data?.data?.list || [];
  } catch (error: any) {
    configText.value = String(error);
  }
});
</script>
