<template>
  <div class="page table-page">
    <h1>用户管理</h1>
    <div class="toolbar">
      <input v-model="keyword" placeholder="输入账号或昵称" />
      <button @click="filterData">查询</button>
    </div>
    <table>
      <thead><tr><th>ID</th><th>账号</th><th>昵称</th><th>状态</th><th>操作</th></tr></thead>
      <tbody>
        <tr v-for="item in list" :key="item.id">
          <td>{{ item.id }}</td><td>{{ item.username }}</td><td>{{ item.nickname }}</td><td>{{ item.status }}</td>
          <td><button class="secondary" @click="detailText = JSON.stringify(item, null, 2)">详情</button></td>
        </tr>
      </tbody>
    </table>
    <div class="panel"><h2>用户详情</h2><pre>{{ detailText }}</pre></div>
  </div>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue';
import { getAdminUsers } from '../../api/admin';
const list = ref<any[]>([]);
const raw = ref<any[]>([]);
const detailText = ref('请选择一个用户');
const keyword = ref('');
async function loadData() { const { data } = await getAdminUsers(); raw.value = data.data?.list || []; list.value = raw.value; }
function filterData() { list.value = raw.value.filter((item: any) => String(item.username).includes(keyword.value) || String(item.nickname).includes(keyword.value)); }
onMounted(loadData);
</script>
