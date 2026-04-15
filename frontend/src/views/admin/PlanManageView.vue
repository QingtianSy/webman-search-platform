<template><div class="page table-page"><h1>套餐管理</h1><div class="toolbar"><button>新增套餐</button></div><table><thead><tr><th>名称</th><th>剩余额度</th><th>已用额度</th><th>操作</th></tr></thead><tbody><tr v-for="item in list" :key="item.id || item.name"><td>{{ item.name }}</td><td>{{ item.remain_quota }}</td><td>{{ item.used_quota }}</td><td><button class="secondary" @click="detailText = JSON.stringify(item, null, 2)">详情</button></td></tr></tbody></table><div class="panel"><h2>套餐详情</h2><pre>{{ detailText }}</pre></div></div></template>
<script setup lang="ts">
import { onMounted, ref } from 'vue';
import { getAdminPlans } from '../../api/admin';
const list = ref<any[]>([]); const detailText = ref('请选择一个套餐');
onMounted(async () => { const { data } = await getAdminPlans(); list.value = data.data?.list || []; });
</script>
