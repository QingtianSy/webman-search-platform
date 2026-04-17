<template><div class="page table-page"><h1>角色管理</h1><div class="toolbar"><button>新增角色</button></div><table><thead><tr><th>ID</th><th>Code</th><th>Name</th><th>操作</th></tr></thead><tbody><tr v-for="item in list" :key="item.id"><td>{{ item.id }}</td><td>{{ item.code }}</td><td>{{ item.name }}</td><td><button class="secondary" @click="detailText = JSON.stringify(item, null, 2)">详情</button></td></tr></tbody></table><div class="panel"><h2>角色详情</h2><pre>{{ detailText }}</pre></div></div></template>
<script setup lang="ts">
import { onMounted, ref } from 'vue';
import { getAdminRoles } from '../../api/admin';
const list = ref<any[]>([]); const detailText = ref('请选择一个角色');
onMounted(async () => { const { data } = await getAdminRoles(); list.value = data.data?.list || []; });
</script>
