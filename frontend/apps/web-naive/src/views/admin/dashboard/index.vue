<script lang="ts" setup>
import { computed, onMounted, ref } from 'vue';

import { useUserStore } from '@vben/stores';

import {
  NAlert,
  NButton,
  NCard,
  NGrid,
  NGridItem,
  NSpin,
  NStatistic,
} from 'naive-ui';

import { type AdminDashboardApi, getAdminOverviewApi } from '#/api/admin';

const userStore = useUserStore();

const loading = ref(false);
const overview = ref<AdminDashboardApi.Overview | null>(null);
const errorMsg = ref('');

const realName = computed(() => userStore.userInfo?.realName ?? '管理员');

async function load() {
  loading.value = true;
  errorMsg.value = '';
  try {
    overview.value = await getAdminOverviewApi();
  } catch (e: any) {
    // 50001 级错误请求拦截器已经弹过 banner，这里只留一个轻提示按钮，避免双重打扰
    errorMsg.value = e?.message ?? '数据暂不可用';
  } finally {
    loading.value = false;
  }
}

onMounted(load);
</script>

<template>
  <div class="p-6">
    <div class="flex items-center justify-between mb-4">
      <div>
        <h1 class="text-2xl font-semibold">控制台 · {{ realName }}</h1>
        <p class="text-sm text-muted-foreground mt-1">
          平台运行概览（缓存 60s）。金额单位：元。
        </p>
      </div>
      <NButton :loading="loading" @click="load">刷新</NButton>
    </div>

    <NAlert
      v-if="errorMsg"
      type="warning"
      :title="errorMsg"
      closable
      class="mb-4"
    />

    <NSpin :show="loading">
      <NGrid :cols="4" :x-gap="16" :y-gap="16" responsive="screen">
        <NGridItem>
          <NCard>
            <NStatistic label="总用户数" :value="overview?.total_users ?? 0" />
            <div class="text-xs text-muted-foreground mt-2">
              今日新增：{{ overview?.today_users ?? 0 }}
            </div>
          </NCard>
        </NGridItem>
        <NGridItem>
          <NCard>
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
          <NCard>
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
          <NCard>
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
