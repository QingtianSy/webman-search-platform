<template>
  <div class="page">
    <h1>采集任务管理</h1>
    <p>管理端页面骨架。</p>
    <pre>{{ text }}</pre>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue';
const text = ref('后续接入 /api/v1/admin/collect/task/list / detail / stop / retry');
</script>
