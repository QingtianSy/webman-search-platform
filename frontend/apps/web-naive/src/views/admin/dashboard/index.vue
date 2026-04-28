<script lang="ts" setup>
// 管理端 · 控制台。docs/07 §3.2.1。
// 当前仅保留 overview 4 色统计条；trend/todo/activity 接口后端未实现，已下线。
// 20s 自动刷新 overview。
import { computed, onMounted, onUnmounted, ref } from 'vue';

import { useUserStore } from '@vben/stores';

import { NAlert, NButton, NCard, NGrid, NGridItem, NSpin, NStatistic } from 'naive-ui';

import { type AdminDashboardApi, getAdminOverviewApi } from '#/api/admin';

const userStore = useUserStore();

const loading = ref(false);
const overview = ref<AdminDashboardApi.Overview | null>(null);
const errorMsg = ref('');

const realName = computed(() => userStore.userInfo?.realName ?? '管理员');

async function loadOverview() {
  loading.value = true;
  errorMsg.value = '';
  try {
    overview.value = await getAdminOverviewApi();
  } catch (e: any) {
    errorMsg.value = e?.message ?? '概览数据暂不可用';
  } finally {
    loading.value = false;
  }
}

let timer: null | ReturnType<typeof setInterval> = null;
function startPolling() {
  stopPolling();
  timer = setInterval(loadOverview, 20_000);
}
function stopPolling() {
  if (timer) clearInterval(timer);
  timer = null;
}

onMounted(async () => {
  await loadOverview();
  startPolling();
});
onUnmounted(stopPolling);
</script>

<template>
  <div class="p-6">
    <div class="flex items-center justify-between mb-4">
      <div>
        <h1 class="text-2xl font-semibold">控制台 · {{ realName }}</h1>
        <p class="text-sm text-muted-foreground mt-1">
          平台运行概览（缓存 60s，每 20s 自动刷新）。金额单位：元。
        </p>
      </div>
      <NButton :loading="loading" @click="loadOverview">刷新</NButton>
    </div>

    <NAlert
      v-if="errorMsg"
      type="warning"
      :title="errorMsg"
      closable
      class="mb-4"
    />

    <NSpin :show="loading">
      <!-- 4 色统计条 -->
      <NGrid :cols="4" :x-gap="16" :y-gap="16" responsive="screen">
        <NGridItem>
          <NCard class="!bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-950 dark:to-blue-900">
            <NStatistic label="总用户数" :value="overview?.total_users ?? 0" />
            <div class="text-xs text-muted-foreground mt-2">
              今日新增：{{ overview?.today_users ?? 0 }}
            </div>
          </NCard>
        </NGridItem>
        <NGridItem>
          <NCard class="!bg-gradient-to-br from-green-50 to-green-100 dark:from-green-950 dark:to-green-900">
            <NStatistic
              label="总搜索次数"
              :value="overview?.total_searches ?? 0"
            />
            <div class="text-xs text-muted-foreground mt-2">
              今日：{{ overview?.today_searches ?? 0 }}
            </div>
          </NCard>
        </NGridItem>
        <NGridItem>
          <NCard class="!bg-gradient-to-br from-amber-50 to-amber-100 dark:from-amber-950 dark:to-amber-900">
            <NStatistic label="累计交易金额">
              <template #prefix>¥</template>
              {{ overview?.total_order_amount ?? '0.00' }}
            </NStatistic>
            <div class="text-xs text-muted-foreground mt-2">
              今日：¥{{ overview?.today_order_amount ?? '0.00' }}
            </div>
          </NCard>
        </NGridItem>
        <NGridItem>
          <NCard class="!bg-gradient-to-br from-purple-50 to-purple-100 dark:from-purple-950 dark:to-purple-900">
            <NStatistic
              label="题库总量"
              :value="overview?.total_questions ?? 0"
            />
            <div class="text-xs text-muted-foreground mt-2">
              来源：Mongo questions 集合
            </div>
          </NCard>
        </NGridItem>
      </NGrid>
    </NSpin>
  </div>
</template>
