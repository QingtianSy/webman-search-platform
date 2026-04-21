<script lang="ts" setup>
import { computed } from 'vue';

import { useUserStore } from '@vben/stores';

const userStore = useUserStore();
const realName = computed(() => userStore.userInfo?.realName ?? '访客');
</script>

<template>
  <div class="p-6">
    <h1 class="text-2xl font-semibold mb-2">欢迎回来，{{ realName }}</h1>
    <p class="text-muted-foreground">
      这是用户端首页占位。后续 P1 将替换为：账户概览、搜题配额、最近活动等。
    </p>
  </div>
</template>
