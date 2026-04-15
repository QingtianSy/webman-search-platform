<template><div class="page table-page"><h1>权限管理</h1><table><thead><tr><th>ID</th><th>Code</th><th>Name</th></tr></thead><tbody><tr v-for="item in list" :key="item.id"><td>{{ item.id }}</td><td>{{ item.code }}</td><td>{{ item.name }}</td></tr></tbody></table></div></template>
<script setup lang="ts">
import { onMounted, ref } from 'vue';
import { getAdminPermissions } from '../../api/admin';
const list = ref<any[]>([]);
onMounted(async () => { const { data } = await getAdminPermissions(); list.value = data.data?.list || []; });
</script>
