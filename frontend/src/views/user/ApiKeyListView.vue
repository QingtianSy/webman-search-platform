<template>
  <div class="page table-page">
    <h1>API Key 列表</h1>
    <div class="toolbar">
      <button>新增</button>
      <button>刷新</button>
    </div>

    <table>
      <thead>
        <tr>
          <th>ID</th>
          <th>应用名称</th>
          <th>API Key</th>
          <th>状态</th>
          <th>过期时间</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="item in list" :key="item.id">
          <td>{{ item.id }}</td>
          <td>{{ item.app_name }}</td>
          <td>{{ item.api_key }}</td>
          <td>{{ item.status }}</td>
          <td>{{ item.expire_at || '长期有效' }}</td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue';
import { getApiKeyList } from '../../api/user';

const list = ref<any[]>([]);

onMounted(async () => {
  try {
    const { data } = await getApiKeyList();
    list.value = data.data?.list || [];
  } catch (error) {
    console.error(error);
  }
});
</script>
