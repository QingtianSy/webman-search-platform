<script lang="ts" setup>
// 管理端 · 控制台。docs/07 §3.2.1。
// 结构：2 色统计条 · 双折线趋势（近7/30天用户+搜索+交易） · 待办红点 · 实时动态两列 · 20s 轮询。
// trend/todo/activity 为 🆕 接口，后端 Phase 2 末尾补；未就绪时 try/catch 兜空，不阻断 overview。
import { computed, onMounted, onUnmounted, ref, shallowRef, watch } from 'vue';

import { useUserStore } from '@vben/stores';

import * as echarts from 'echarts';
import {
  NAlert,
  NBadge,
  NButton,
  NCard,
  NGrid,
  NGridItem,
  NRadioButton,
  NRadioGroup,
  NSpin,
  NStatistic,
  NTag,
} from 'naive-ui';

import {
  type AdminDashboardApi,
  getAdminDashboardActivityApi,
  getAdminDashboardTodoApi,
  getAdminDashboardTrendApi,
  getAdminOverviewApi,
} from '#/api/admin';

const userStore = useUserStore();

const loading = ref(false);
const overview = ref<AdminDashboardApi.Overview | null>(null);
const trend = ref<AdminDashboardApi.Trend | null>(null);
const todo = ref<AdminDashboardApi.Todo | null>(null);
const activity = ref<AdminDashboardApi.Activity | null>(null);
const errorMsg = ref('');
const days = ref<7 | 30>(7);

const realName = computed(() => userStore.userInfo?.realName ?? '管理员');

const chartEl = ref<HTMLDivElement | null>(null);
const chartRef = shallowRef<echarts.ECharts | null>(null);

function initChart() {
  if (!chartEl.value || chartRef.value) return;
  chartRef.value = echarts.init(chartEl.value);
  const onResize = () => chartRef.value?.resize();
  window.addEventListener('resize', onResize);
  onUnmounted(() => {
    window.removeEventListener('resize', onResize);
    chartRef.value?.dispose();
    chartRef.value = null;
  });
}

function renderChart() {
  if (!chartRef.value) return;
  const list = trend.value?.list ?? [];
  chartRef.value.setOption({
    tooltip: { trigger: 'axis' },
    legend: { data: ['新增用户', '搜索次数'], right: 0 },
    grid: { left: 40, right: 20, top: 40, bottom: 30 },
    xAxis: {
      type: 'category',
      data: list.map((p) => p.date),
      boundaryGap: false,
    },
    yAxis: [{ type: 'value' }],
    series: [
      {
        name: '新增用户',
        type: 'line',
        smooth: true,
        itemStyle: { color: '#18a058' },
        areaStyle: { opacity: 0.1 },
        data: list.map((p) => p.users),
      },
      {
        name: '搜索次数',
        type: 'line',
        smooth: true,
        itemStyle: { color: '#2080f0' },
        areaStyle: { opacity: 0.1 },
        data: list.map((p) => p.searches),
      },
    ],
  });
}

async function loadOverview() {
  try {
    overview.value = await getAdminOverviewApi();
  } catch (e: any) {
    errorMsg.value = e?.message ?? '概览数据暂不可用';
  }
}

async function loadTrend() {
  try {
    trend.value = await getAdminDashboardTrendApi(days.value);
  } catch {
    // 🆕 后端未就绪兜底空
    trend.value = { list: [] };
  }
  renderChart();
}

async function loadTodo() {
  try {
    todo.value = await getAdminDashboardTodoApi();
  } catch {
    todo.value = {
      pending_orders: 0,
      failed_payments: 0,
      new_announcements: 0,
      low_proxies: 0,
      es_out_of_sync: 0,
    };
  }
}

async function loadActivity() {
  try {
    activity.value = await getAdminDashboardActivityApi();
  } catch {
    activity.value = { searches: [], orders: [] };
  }
}

async function loadAll() {
  loading.value = true;
  errorMsg.value = '';
  try {
    await Promise.all([loadOverview(), loadTrend(), loadTodo(), loadActivity()]);
  } finally {
    loading.value = false;
  }
}

let timer: null | ReturnType<typeof setInterval> = null;
function startPolling() {
  stopPolling();
  timer = setInterval(() => {
    // 轮询只刷新动态 + 待办 + overview，trend 频率低
    loadOverview();
    loadTodo();
    loadActivity();
  }, 20_000);
}
function stopPolling() {
  if (timer) clearInterval(timer);
  timer = null;
}

watch(days, loadTrend);

onMounted(async () => {
  await loadAll();
  initChart();
  renderChart();
  startPolling();
});
onUnmounted(stopPolling);

const todoItems = computed(() => [
  { key: 'pending_orders', label: '待支付订单', value: todo.value?.pending_orders ?? 0 },
  { key: 'failed_payments', label: '支付失败', value: todo.value?.failed_payments ?? 0 },
  { key: 'new_announcements', label: '未发公告', value: todo.value?.new_announcements ?? 0 },
  { key: 'low_proxies', label: '可用代理不足', value: todo.value?.low_proxies ?? 0 },
  { key: 'es_out_of_sync', label: 'ES 未同步', value: todo.value?.es_out_of_sync ?? 0 },
]);
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
      <NButton :loading="loading" @click="loadAll">刷新</NButton>
    </div>

    <NAlert
      v-if="errorMsg"
      type="warning"
      :title="errorMsg"
      closable
      class="mb-4"
    />

    <NSpin :show="loading">
      <!-- 2 色统计条 -->
      <NGrid :cols="4" :x-gap="16" :y-gap="16" responsive="screen" class="mb-4">
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

      <!-- 待办红点条 -->
      <NCard title="待办" size="small" class="mb-4">
        <div class="flex flex-wrap gap-6 items-center">
          <div
            v-for="item in todoItems"
            :key="item.key"
            class="flex items-center gap-2"
          >
            <NBadge :value="item.value" :max="99" :show="item.value > 0">
              <NTag :type="item.value > 0 ? 'warning' : 'default'" size="small">
                {{ item.label }}
              </NTag>
            </NBadge>
          </div>
        </div>
      </NCard>

      <!-- 趋势图 -->
      <NCard title="趋势" size="small" class="mb-4">
        <template #header-extra>
          <NRadioGroup v-model:value="days" size="small">
            <NRadioButton :value="7">近 7 天</NRadioButton>
            <NRadioButton :value="30">近 30 天</NRadioButton>
          </NRadioGroup>
        </template>
        <div ref="chartEl" style="width: 100%; height: 300px" />
        <div
          v-if="(trend?.list?.length ?? 0) === 0"
          class="text-center text-xs text-muted-foreground pt-4"
        >
          暂无数据（接口未就绪或后端未上线）
        </div>
      </NCard>

      <!-- 实时动态两列 -->
      <NGrid :cols="2" :x-gap="16" responsive="screen">
        <NGridItem>
          <NCard title="最近搜索" size="small">
            <div
              v-if="activity?.searches?.length"
              class="flex flex-col gap-2"
            >
              <div
                v-for="(a, i) in activity.searches"
                :key="i"
                class="flex justify-between items-center text-sm"
              >
                <span class="truncate max-w-[260px]">
                  <NTag size="tiny" type="info">{{ a.username ?? a.user_id }}</NTag>
                  {{ a.summary }}
                </span>
                <span class="text-xs text-muted-foreground">
                  {{ a.created_at }}
                </span>
              </div>
            </div>
            <div v-else class="text-xs text-muted-foreground">暂无数据</div>
          </NCard>
        </NGridItem>
        <NGridItem>
          <NCard title="最近交易" size="small">
            <div
              v-if="activity?.orders?.length"
              class="flex flex-col gap-2"
            >
              <div
                v-for="(a, i) in activity.orders"
                :key="i"
                class="flex justify-between items-center text-sm"
              >
                <span class="truncate max-w-[260px]">
                  <NTag size="tiny" type="success">{{ a.username ?? a.user_id }}</NTag>
                  {{ a.summary }}
                </span>
                <span class="text-xs text-muted-foreground">
                  {{ a.created_at }}
                </span>
              </div>
            </div>
            <div v-else class="text-xs text-muted-foreground">暂无数据</div>
          </NCard>
        </NGridItem>
      </NGrid>
    </NSpin>
  </div>
</template>
