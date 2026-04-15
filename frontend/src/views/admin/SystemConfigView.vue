<template>
  <div class="page table-page">
    <h1>系统配置</h1>
    <div class="toolbar">
      <button class="secondary" @click="loadData">刷新</button>
    </div>
    <table>
      <thead>
        <tr>
          <th>分组</th>
          <th>Key</th>
          <th>Value</th>
          <th>类型</th>
          <th>操作</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="item in list" :key="item.id">
          <td>{{ item.group_code }}</td>
          <td>{{ item.config_key }}</td>
          <td>
            <input v-model="item.config_value" />
          </td>
          <td>{{ item.value_type }}</td>
          <td><button @click="saveItem(item)">保存</button></td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue';
import { getSystemConfigList, updateSystemConfig } from '../../api/admin';

const list = ref<any[]>([]);

async function loadData() {
  const { data } = await getSystemConfigList();
  list.value = data.data?.list || [];
}

async function saveItem(item: any) {
  await updateSystemConfig({ config_key: item.config_key, config_value: item.config_value });
  await loadData();
}

onMounted(loadData);
</script>
