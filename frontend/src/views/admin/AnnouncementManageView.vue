<template>
  <div class="page table-page">
    <h1>公告管理</h1>
    <div class="toolbar">
      <button>新增公告</button>
      <button>刷新</button>
    </div>
    <table>
      <thead>
        <tr>
          <th>ID</th>
          <th>标题</th>
          <th>内容</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="item in list" :key="item.id">
          <td>{{ item.id }}</td>
          <td>{{ item.title }}</td>
          <td>{{ item.content }}</td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue';
import { getAnnouncementList } from '../../api/admin';

const list = ref<any[]>([]);

onMounted(async () => {
  try {
    const { data } = await getAnnouncementList();
    list.value = data.data?.list || [];
  } catch (error) {
    console.error(error);
  }
});
</script>
