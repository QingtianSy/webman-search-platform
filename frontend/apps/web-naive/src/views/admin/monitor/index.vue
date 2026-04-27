<script lang="ts" setup>
// 管理端 · 系统监控。docs/07 §3.2.13。
// 四行 Dashboard:服务器 · PHP · Redis · DB · Business;10s 自动刷新;服务掉线 Banner;日志面板
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';

import {
  NAlert,
  NBadge,
  NButton,
  NCard,
  NDescriptions,
  NDescriptionsItem,
  NEmpty,
  NGrid,
  NGridItem,
  NSpace,
  NSpin,
  NSwitch,
  NTag,
  useMessage,
} from 'naive-ui';

import {
  type AdminMonitorApi,
  getMonitorLogsApi,
  getMonitorOverviewApi,
} from '#/api/admin';

const message = useMessage();

const loading = ref(false);
const overview = ref<AdminMonitorApi.Overview | null>(null);
const lastUpdated = ref<string>('');
const autoRefresh = ref(true);
let timer: null | ReturnType<typeof setInterval> = null;

async function load() {
  loading.value = true;
  try {
    overview.value = await getMonitorOverviewApi();
    lastUpdated.value = new Date().toLocaleTimeString();
  } catch {
    message.error('监控数据加载失败');
  } finally {
    loading.value = false;
  }
}

function startTimer() {
  stopTimer();
  if (!autoRefresh.value) return;
  timer = setInterval(() => {
    load();
  }, 10_000);
}
function stopTimer() {
  if (timer) {
    clearInterval(timer);
    timer = null;
  }
}
function onToggleAuto(v: boolean) {
  autoRefresh.value = v;
  if (v) startTimer();
  else stopTimer();
}

// ========= 依赖健康判断 =========
const redisDown = computed(() => {
  const r = overview.value?.redis;
  return !r || (r as any).error;
});
const dbDown = computed(() => {
  const d = overview.value?.database;
  return !d || (d as any).error;
});
// 后端 services 是 { mysql, redis, mongodb, elasticsearch } 的 map，值为 'ok' / 'error' / 'disconnected' / 'not_configured' / 'unknown'。
// 未配置的服务不算异常（避免本地开发无 mongodb 时误报），只有显式 error/disconnected/unknown 才列入 banner。
const SERVICE_LABEL: Record<string, string> = {
  mysql: 'MySQL',
  redis: 'Redis',
  mongodb: 'MongoDB',
  elasticsearch: 'Elasticsearch',
};
const serviceEntries = computed<[string, string][]>(() => {
  const map = overview.value?.services ?? {};
  return Object.entries(map) as [string, string][];
});
const unhealthyServices = computed<string[]>(() => {
  return serviceEntries.value
    .filter(([, v]) => v !== 'ok' && v !== 'not_configured')
    .map(([k]) => SERVICE_LABEL[k] ?? k);
});

const offlineBanner = computed(() => {
  const items: string[] = [];
  if (redisDown.value && !items.includes('Redis')) items.push('Redis');
  if (dbDown.value) items.push('数据库');
  for (const name of unhealthyServices.value) {
    if (!items.includes(name)) items.push(name);
  }
  return items;
});

// ========= 日志 =========
const logs = ref<AdminMonitorApi.LogEntry[]>([]);
const logsLoading = ref(false);
async function loadLogs() {
  logsLoading.value = true;
  try {
    const data = await getMonitorLogsApi({ limit: 100 });
    logs.value = Array.isArray(data) ? data : [];
  } catch {
    logs.value = [];
  } finally {
    logsLoading.value = false;
  }
}

function formatUptime(sec?: null | number) {
  if (!sec || sec < 0) return '-';
  const d = Math.floor(sec / 86_400);
  const h = Math.floor((sec % 86_400) / 3600);
  const m = Math.floor((sec % 3600) / 60);
  if (d > 0) return `${d}d ${h}h ${m}m`;
  if (h > 0) return `${h}h ${m}m`;
  return `${m}m`;
}

onMounted(() => {
  load();
  loadLogs();
  startTimer();
});
onBeforeUnmount(stopTimer);
</script>

<template>
  <div class="p-6">
    <NCard title="系统监控">
      <template #header-extra>
        <NSpace :align="'center'">
          <span class="text-xs text-muted-foreground">
            上次刷新：{{ lastUpdated || '-' }}
          </span>
          <span class="text-xs">自动刷新(10s)</span>
          <NSwitch :value="autoRefresh" @update:value="onToggleAuto" />
          <NButton :loading="loading" @click="load">手动刷新</NButton>
        </NSpace>
      </template>

      <NAlert
        v-if="offlineBanner.length > 0"
        type="error"
        class="mb-4"
        :bordered="false"
      >
        <template #header>
          依赖服务异常：{{ offlineBanner.join('、') }}
        </template>
        请检查后端服务是否在线，部分业务功能可能不可用
      </NAlert>

      <NSpin :show="loading && !overview">
        <NGrid :cols="2" :x-gap="16" :y-gap="16">
          <!-- 行 1-左: 服务器 -->
          <NGridItem>
            <NCard title="服务器" size="small">
              <NDescriptions :column="2" size="small" label-placement="left">
                <NDescriptionsItem label="主机名">
                  {{ overview?.server?.hostname ?? '-' }}
                </NDescriptionsItem>
                <NDescriptionsItem label="OS">
                  {{ overview?.server?.os ?? '-' }}
                </NDescriptionsItem>
                <NDescriptionsItem label="PHP">
                  {{ overview?.server?.php_version ?? '-' }}
                </NDescriptionsItem>
                <NDescriptionsItem label="SAPI">
                  {{ overview?.server?.sapi ?? '-' }}
                </NDescriptionsItem>
                <NDescriptionsItem label="Workers">
                  {{ overview?.server?.worker_count ?? '-' }}
                </NDescriptionsItem>
                <NDescriptionsItem label="Swoole">
                  {{ overview?.server?.swoole_version ?? '-' }}
                </NDescriptionsItem>
                <NDescriptionsItem label="启动时间">
                  {{ overview?.server?.start_time ?? '-' }}
                </NDescriptionsItem>
                <NDescriptionsItem label="运行时长">
                  {{ formatUptime(overview?.server?.uptime_seconds) }}
                </NDescriptionsItem>
                <NDescriptionsItem label="负载" :span="2">
                  <span v-if="overview?.server?.load_average">
                    {{ overview.server.load_average.join(' / ') }}
                  </span>
                  <span v-else>-</span>
                </NDescriptionsItem>
              </NDescriptions>
            </NCard>
          </NGridItem>

          <!-- 行 1-右: PHP -->
          <NGridItem>
            <NCard title="PHP 运行时" size="small">
              <NDescriptions :column="2" size="small" label-placement="left">
                <NDescriptionsItem label="内存">
                  {{ overview?.php?.memory_usage ?? '-' }}
                </NDescriptionsItem>
                <NDescriptionsItem label="峰值">
                  {{ overview?.php?.memory_peak ?? '-' }}
                </NDescriptionsItem>
                <NDescriptionsItem label="memory_limit">
                  {{ overview?.php?.memory_limit ?? '-' }}
                </NDescriptionsItem>
                <NDescriptionsItem label="最大执行">
                  {{ overview?.php?.max_execution ?? '-' }}
                </NDescriptionsItem>
                <NDescriptionsItem label="扩展" :span="2">
                  <NSpace size="small">
                    <NTag
                      v-for="(v, k) in overview?.php?.extensions ?? {}"
                      :key="k"
                      size="tiny"
                      :type="v ? 'success' : 'default'"
                    >
                      {{ k }}{{ v ? '' : '✗' }}
                    </NTag>
                  </NSpace>
                </NDescriptionsItem>
              </NDescriptions>
            </NCard>
          </NGridItem>

          <!-- 行 2-左: Redis -->
          <NGridItem>
            <NCard size="small">
              <template #header>
                Redis
                <NBadge
                  :type="redisDown ? 'error' : 'success'"
                  :value="redisDown ? '离线' : '在线'"
                  class="ml-2"
                />
              </template>
              <NAlert
                v-if="redisDown"
                type="error"
                :bordered="false"
                size="small"
              >
                {{ (overview?.redis as any)?.error ?? 'Redis 连接失败' }}
              </NAlert>
              <NDescriptions v-else :column="2" size="small" label-placement="left">
                <NDescriptionsItem label="版本">
                  {{ overview?.redis?.redis_version ?? '-' }}
                </NDescriptionsItem>
                <NDescriptionsItem label="运行">
                  {{ formatUptime(overview?.redis?.uptime_in_seconds) }}
                </NDescriptionsItem>
                <NDescriptionsItem label="连接">
                  {{ overview?.redis?.connected_clients ?? '-' }}
                </NDescriptionsItem>
                <NDescriptionsItem label="内存">
                  {{ overview?.redis?.used_memory_human ?? '-' }}
                </NDescriptionsItem>
                <NDescriptionsItem label="命中">
                  {{ overview?.redis?.keyspace_hits ?? 0 }}
                </NDescriptionsItem>
                <NDescriptionsItem label="未命中">
                  {{ overview?.redis?.keyspace_misses ?? 0 }}
                </NDescriptionsItem>
                <NDescriptionsItem label="命令总数" :span="2">
                  {{ overview?.redis?.total_commands ?? 0 }}
                </NDescriptionsItem>
                <NDescriptionsItem label="DB keys" :span="2">
                  <NSpace size="small">
                    <NTag
                      v-for="(v, k) in overview?.redis?.db_keys ?? {}"
                      :key="k"
                      size="tiny"
                    >
                      {{ k }}:{{ v }}
                    </NTag>
                  </NSpace>
                </NDescriptionsItem>
              </NDescriptions>
            </NCard>
          </NGridItem>

          <!-- 行 2-右: 数据库 -->
          <NGridItem>
            <NCard size="small">
              <template #header>
                数据库
                <NBadge
                  :type="dbDown ? 'error' : 'success'"
                  :value="dbDown ? '离线' : '在线'"
                  class="ml-2"
                />
              </template>
              <NAlert
                v-if="dbDown"
                type="error"
                :bordered="false"
                size="small"
              >
                {{ (overview?.database as any)?.error ?? 'DB 连接失败' }}
              </NAlert>
              <NDescriptions v-else :column="2" size="small" label-placement="left">
                <NDescriptionsItem label="版本">
                  {{ overview?.database?.version ?? '-' }}
                </NDescriptionsItem>
                <NDescriptionsItem label="运行">
                  {{ formatUptime(overview?.database?.uptime_seconds) }}
                </NDescriptionsItem>
                <NDescriptionsItem label="连接数">
                  {{ overview?.database?.threads_connected ?? 0 }}
                  / {{ overview?.database?.max_connections ?? 0 }}
                </NDescriptionsItem>
                <NDescriptionsItem label="总连接">
                  {{ overview?.database?.total_connections ?? 0 }}
                </NDescriptionsItem>
                <NDescriptionsItem label="总查询">
                  {{ overview?.database?.total_queries ?? 0 }}
                </NDescriptionsItem>
                <NDescriptionsItem label="慢查询">
                  {{ overview?.database?.slow_queries ?? 0 }}
                </NDescriptionsItem>
                <NDescriptionsItem label="发送">
                  {{ overview?.database?.bytes_sent ?? '-' }}
                </NDescriptionsItem>
                <NDescriptionsItem label="接收">
                  {{ overview?.database?.bytes_received ?? '-' }}
                </NDescriptionsItem>
              </NDescriptions>
            </NCard>
          </NGridItem>

          <!-- 行 3: 业务指标 + 服务健康 -->
          <NGridItem>
            <NCard title="业务指标" size="small">
              <NDescriptions :column="2" size="small" label-placement="left">
                <NDescriptionsItem label="总用户">
                  {{ overview?.business?.total_users ?? 0 }}
                </NDescriptionsItem>
                <NDescriptionsItem label="今日新增">
                  {{ overview?.business?.today_users ?? 0 }}
                </NDescriptionsItem>
                <NDescriptionsItem label="总搜索">
                  {{ overview?.business?.total_searches ?? 0 }}
                </NDescriptionsItem>
                <NDescriptionsItem label="今日搜索">
                  {{ overview?.business?.today_searches ?? 0 }}
                </NDescriptionsItem>
                <NDescriptionsItem label="累计金额">
                  ¥ {{ overview?.business?.total_order_amount ?? '0.00' }}
                </NDescriptionsItem>
                <NDescriptionsItem label="今日金额">
                  ¥ {{ overview?.business?.today_order_amount ?? '0.00' }}
                </NDescriptionsItem>
                <NDescriptionsItem label="题库总数">
                  {{ overview?.business?.total_questions ?? 0 }}
                </NDescriptionsItem>
                <NDescriptionsItem label="在跑采集">
                  {{ overview?.business?.active_collect_tasks ?? 0 }}
                </NDescriptionsItem>
              </NDescriptions>
              <NAlert
                v-if="overview?.business?.error"
                type="warning"
                :bordered="false"
                size="small"
                class="mt-2"
              >
                {{ overview.business.error }}
              </NAlert>
            </NCard>
          </NGridItem>

          <NGridItem>
            <NCard title="依赖服务" size="small">
              <NEmpty
                v-if="serviceEntries.length === 0"
                description="无上报"
                size="small"
              />
              <div v-else class="flex flex-col gap-2">
                <div
                  v-for="[k, v] in serviceEntries"
                  :key="k"
                  class="flex items-center gap-2 text-sm"
                >
                  <NBadge
                    :type="
                      v === 'ok'
                        ? 'success'
                        : v === 'not_configured'
                          ? 'default'
                          : 'error'
                    "
                    dot
                  />
                  <span>{{ SERVICE_LABEL[k] ?? k }}</span>
                  <span class="text-xs text-muted-foreground ml-auto">
                    {{ v }}
                  </span>
                </div>
              </div>
            </NCard>
          </NGridItem>
        </NGrid>
      </NSpin>
    </NCard>

    <NCard title="运行日志（最近 100 条）" class="mt-4">
      <template #header-extra>
        <NButton :loading="logsLoading" size="small" @click="loadLogs">
          刷新日志
        </NButton>
      </template>

      <NEmpty v-if="logs.length === 0" description="暂无日志" />
      <div v-else class="flex flex-col gap-1" style="max-height: 360px; overflow: auto">
        <div
          v-for="(l, i) in logs"
          :key="i"
          class="text-xs font-mono flex gap-2"
        >
          <span class="text-muted-foreground">{{ l.timestamp }}</span>
          <NTag
            size="tiny"
            :type="
              l.level === 'error'
                ? 'error'
                : l.level === 'warning'
                  ? 'warning'
                  : 'default'
            "
          >
            {{ l.level }}
          </NTag>
          <span v-if="l.channel" class="text-muted-foreground">[{{ l.channel }}]</span>
          <span class="break-all">{{ l.message }}</span>
        </div>
      </div>
    </NCard>
  </div>
</template>
