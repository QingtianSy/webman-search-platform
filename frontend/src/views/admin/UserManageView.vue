<template>
  <div class="page table-page">
    <h1>用户管理</h1>
    <table>
      <thead><tr><th>ID</th><th>账号</th><th>昵称</th><th>状态</th></tr></thead>
      <tbody>
        <tr v-for="item in list" :key="item.id">
          <td>{{ item.id }}</td><td>{{ item.username }}</td><td>{{ item.nickname }}</td><td>{{ item.status }}</td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue';
import { getAdminUsers } from '../../api/admin';
const list = ref<any[]>([]);
onMounted(async () => { const { data } = await getAdminUsers(); list.value = data.data?.list || []; });
</script>
