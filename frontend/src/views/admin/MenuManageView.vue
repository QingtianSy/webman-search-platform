<template><div class="page table-page"><h1>菜单管理</h1><table><thead><tr><th>ID</th><th>Name</th><th>Path</th><th>Permission</th></tr></thead><tbody><tr v-for="item in list" :key="item.id"><td>{{ item.id }}</td><td>{{ item.name }}</td><td>{{ item.path }}</td><td>{{ item.permission_code }}</td></tr></tbody></table></div></template>
<script setup lang="ts">
import { onMounted, ref } from 'vue';
import { getAdminMenus } from '../../api/admin';
const list = ref<any[]>([]);
onMounted(async () => { const { data } = await getAdminMenus(); list.value = data.data?.list || []; });
</script>
