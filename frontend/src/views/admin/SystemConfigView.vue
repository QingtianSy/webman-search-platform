<template>
  <div class="page">
    <h1>系统配置</h1>
    <p>管理端页面骨架。</p>
    <pre>{{ text }}</pre>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue';
const text = ref('后续接入 /api/v1/admin/system-config/list 与 update');
</script>
