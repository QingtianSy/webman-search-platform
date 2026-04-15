<template>
  <div class="page table-page">
    <h1>文档管理</h1>
    <div class="toolbar">
      <button>新增文档</button>
      <button>刷新</button>
    </div>
    <table>
      <thead>
        <tr>
          <th>ID</th>
          <th>标题</th>
          <th>Slug</th>
          <th>摘要</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="item in list" :key="item.id">
          <td>{{ item.id }}</td>
          <td>{{ item.title }}</td>
          <td>{{ item.slug }}</td>
          <td>{{ item.summary }}</td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue';
import { getDocArticleList } from '../../api/admin';

const list = ref<any[]>([]);

onMounted(async () => {
  try {
    const { data } = await getDocArticleList();
    list.value = data.data?.list || [];
  } catch (error) {
    console.error(error);
  }
});
</script>
