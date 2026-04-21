<script lang="ts" setup>
import { useRoute } from 'vue-router';

const route = useRoute();
</script>

<template>
  <div class="p-6">
    <h1 class="text-2xl font-semibold mb-2">页面建设中</h1>
    <p class="text-muted-foreground mb-1">
      路径：<code>{{ route.path }}</code>
    </p>
    <p class="text-muted-foreground">
      菜单骨架已通过，后续分期会替换为实际业务页面。
    </p>
  </div>
</template>
