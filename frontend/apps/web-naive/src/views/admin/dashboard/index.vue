<script lang="ts" setup>
import { computed } from 'vue';

import { useAccessStore, useUserStore } from '@vben/stores';

const userStore = useUserStore();
const accessStore = useAccessStore();
const realName = computed(() => userStore.userInfo?.realName ?? '管理员');
const permissionCount = computed(() => accessStore.accessCodes?.length ?? 0);
</script>

<template>
  <div class="p-6">
    <h1 class="text-2xl font-semibold mb-2">管理端控制台 · {{ realName }}</h1>
    <p class="text-muted-foreground mb-4">
      这是管理端首页占位。后续 P3 将替换为：平台运行状态、配额监控、最近告警等。
    </p>
    <div class="text-sm text-muted-foreground">
      当前持有权限码：{{ permissionCount }} 项
    </div>
  </div>
</template>
