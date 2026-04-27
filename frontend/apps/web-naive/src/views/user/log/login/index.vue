<script lang="ts" setup>
import type { DataTableColumns } from 'naive-ui';

import { computed, h, onMounted, reactive, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';

import {
  NAlert,
  NButton,
  NCard,
  NDataTable,
  NDatePicker,
  NDescriptions,
  NDescriptionsItem,
  NDrawer,
  NDrawerContent,
  NInput,
  NSelect,
  NSpace,
  NTag,
  useMessage,
} from 'naive-ui';

import { listLoginLogsApi, type UserLogApi } from '#/api/user/log';
import { usePagination } from '#/composables/usePagination';

const message = useMessage();
const route = useRoute();
const router = useRouter();

const filter = reactive({
  status: undefined as number | undefined,
  ip: '',
  dateRange: null as [number, number] | null,
});

function hydrateFromQuery() {
  const q = route.query;
  if (q.status !== undefined && q.status !== '') {
    const n = Number(q.status);
    if (!Number.isNaN(n)) filter.status = n;
  }
  if (typeof q.ip === 'string') filter.ip = q.ip;
  const df = typeof q.date_from === 'string' ? q.date_from : '';
  const dt = typeof q.date_to === 'string' ? q.date_to : '';
  if (df && dt) {
    const a = new Date(df).getTime();
    const b = new Date(dt).getTime();
    if (!Number.isNaN(a) && !Number.isNaN(b)) filter.dateRange = [a, b];
  }
  const p = Number(q.page);
  if (!Number.isNaN(p) && p > 0) pg.page.value = p;
}
function pushQuery() {
  const q: Record<string, string> = {};
  if (filter.status !== undefined) q.status = String(filter.status);
  if (filter.ip) q.ip = filter.ip;
  if (filter.dateRange) {
    q.date_from = new Date(filter.dateRange[0]).toISOString().slice(0, 10);
    q.date_to = new Date(filter.dateRange[1]).toISOString().slice(0, 10);
  }
  if (pg.page.value > 1) q.page = String(pg.page.value);
  router.replace({ query: q }).catch(() => {});
}

const pg = usePagination(20);
const loading = ref(false);
const rows = ref<UserLogApi.LoginLog[]>([]);

async function load() {
  loading.value = true;
  try {
    const [from, to] = filter.dateRange ?? [];
    const data = await listLoginLogsApi({
      page: pg.page.value,
      page_size: pg.pageSize.value,
      status: filter.status,
      ip: filter.ip || undefined,
      date_from: from ? new Date(from).toISOString().slice(0, 10) : undefined,
      date_to: to ? new Date(to).toISOString().slice(0, 10) : undefined,
    });
    rows.value = data.list ?? [];
    pg.apply(data);
  } catch {
    message.error('登录记录加载失败');
  } finally {
    loading.value = false;
  }
}

function onSearch() {
  pg.page.value = 1;
  pushQuery();
  load();
}
function onReset() {
  filter.status = undefined;
  filter.ip = '';
  filter.dateRange = null;
  onSearch();
}

// 异常告警：最近 7 天出现多于 3 个不同 IP
const abnormalWarn = computed(() => {
  const now = Date.now();
  const week = 7 * 24 * 60 * 60 * 1000;
  const ips = new Set<string>();
  for (const r of rows.value) {
    if (!r.created_at) continue;
    const t = new Date(r.created_at).getTime();
    if (now - t <= week && r.status === 1 && r.ip) {
      ips.add(r.ip);
    }
  }
  return ips.size > 3
    ? `近 7 天检测到 ${ips.size} 个不同登录 IP，请关注账号安全`
    : '';
});

// 详情 Drawer
const drawerVisible = ref(false);
const detail = ref<null | UserLogApi.LoginLog>(null);
function openDetail(row: UserLogApi.LoginLog) {
  detail.value = row;
  drawerVisible.value = true;
}

const columns = computed<DataTableColumns<UserLogApi.LoginLog>>(() => [
  {
    title: '结果',
    key: 'status',
    width: 90,
    render: (row) =>
      row.status === 1
        ? h(NTag, { type: 'success', size: 'small' }, () => '成功')
        : h(NTag, { type: 'error', size: 'small' }, () => '失败'),
  },
  { title: 'IP', key: 'ip', width: 140 },
  { title: 'User Agent', key: 'user_agent', ellipsis: { tooltip: true } },
  { title: '时间', key: 'created_at', width: 180 },
  {
    title: '操作',
    key: 'actions',
    width: 80,
    render: (row) =>
      h(
        NButton,
        { size: 'small', text: true, type: 'primary', onClick: () => openDetail(row) },
        { default: () => '详情' },
      ),
  },
]);

onMounted(() => {
  hydrateFromQuery();
  load();
});
</script>

<template>
  <div class="p-6">
    <NAlert
      v-if="abnormalWarn"
      type="warning"
      :show-icon="false"
      class="mb-3"
    >
      {{ abnormalWarn }}
    </NAlert>

    <NCard :bordered="false" size="small" class="mb-3">
      <NSpace>
        <NSelect
          v-model:value="filter.status"
          placeholder="结果"
          clearable
          style="width: 120px"
          :options="[
            { label: '成功', value: 1 },
            { label: '失败', value: 0 },
          ]"
        />
        <NInput
          v-model:value="filter.ip"
          placeholder="IP"
          clearable
          style="width: 160px"
        />
        <NDatePicker
          v-model:value="filter.dateRange"
          type="daterange"
          clearable
          style="width: 260px"
        />
        <NButton type="primary" @click="onSearch">查询</NButton>
        <NButton @click="onReset">重置</NButton>
      </NSpace>
    </NCard>

    <NCard :bordered="false" size="small" title="登录记录">
      <NDataTable
        remote
        :loading="loading"
        :columns="columns"
        :data="rows"
        :row-key="(row: UserLogApi.LoginLog) => row.id"
        :pagination="{
          page: pg.page.value,
          pageSize: pg.pageSize.value,
          itemCount: pg.total.value,
          pageSizes: [10, 20, 50],
          showSizePicker: true,
          onUpdatePage: (p: number) => { pg.onPageChange(p); pushQuery(); load(); },
          onUpdatePageSize: (s: number) => { pg.onPageSizeChange(s); pushQuery(); load(); },
        }"
      />
    </NCard>

    <NDrawer v-model:show="drawerVisible" :width="520">
      <NDrawerContent title="登录详情" closable>
        <NDescriptions v-if="detail" :column="1" bordered size="small">
          <NDescriptionsItem label="结果">
            <NTag
              size="small"
              :type="detail.status === 1 ? 'success' : 'error'"
            >
              {{ detail.status === 1 ? '成功' : '失败' }}
            </NTag>
          </NDescriptionsItem>
          <NDescriptionsItem label="IP">{{ detail.ip }}</NDescriptionsItem>
          <NDescriptionsItem label="User Agent">
            {{ detail.user_agent ?? '-' }}
          </NDescriptionsItem>
          <NDescriptionsItem label="时间">{{ detail.created_at }}</NDescriptionsItem>
        </NDescriptions>
      </NDrawerContent>
    </NDrawer>
  </div>
</template>

<style scoped>
.mb-3 {
  margin-bottom: 12px;
}
</style>
