<template><div class="page table-page"><h1>菜单管理</h1><div class="toolbar"><button>刷新</button></div><table><thead><tr><th>ID</th><th>Name</th><th>Path</th><th>Permission</th><th>操作</th></tr></thead><tbody><tr v-for="item in list" :key="item.id"><td>{{ item.id }}</td><td>{{ item.name }}</td><td>{{ item.path }}</td><td>{{ item.permission_code }}</td><td><button class="secondary" @click="detailText = JSON.stringify(item, null, 2)">详情</button></td></tr></tbody></table><div class="panel"><h2>菜单详情</h2><pre>{{ detailText }}</pre></div></div></template>
<script setup lang="ts">
import { onMounted, ref } from 'vue';
import { getAdminMenus } from '../../api/admin';
const list = ref<any[]>([]); const detailText = ref('请选择一个菜单');
onMounted(async () => { const { data } = await getAdminMenus(); list.value = data.data?.list || []; });
</script>
