<template>
  <div class="page table-page">
    <h1>系统配置</h1>
    <div class="toolbar">
      <button>刷新</button>
      <button>保存配置</button>
    </div>
    <table>
      <thead>
        <tr>
          <th>分组</th>
          <th>Key</th>
          <th>Value</th>
          <th>类型</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="item in list" :key="item.id">
          <td>{{ item.group_code }}</td>
          <td>{{ item.config_key }}</td>
          <td>{{ item.config_value }}</td>
          <td>{{ item.value_type }}</td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue';
import { getSystemConfigList } from '../../api/admin';

const list = ref<any[]>([]);

onMounted(async () => {
  try {
    const { data } = await getSystemConfigList();
    list.value = data.data?.list || [];
  } catch (error) {
    console.error(error);
  }
});
</script>
