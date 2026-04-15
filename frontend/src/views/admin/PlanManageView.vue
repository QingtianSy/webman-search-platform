<template><div class="page table-page"><h1>套餐管理</h1><table><thead><tr><th>名称</th><th>剩余额度</th><th>已用额度</th></tr></thead><tbody><tr v-for="item in list" :key="item.id || item.name"><td>{{ item.name }}</td><td>{{ item.remain_quota }}</td><td>{{ item.used_quota }}</td></tr></tbody></table></div></template>
<script setup lang="ts">
import { onMounted, ref } from 'vue';
import { getAdminPlans } from '../../api/admin';
const list = ref<any[]>([]);
onMounted(async () => { const { data } = await getAdminPlans(); list.value = data.data?.list || []; });
</script>
