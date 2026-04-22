<script lang="ts" setup>
import type { DataTableColumns } from 'naive-ui';

import { h, onBeforeUnmount, onMounted, ref } from 'vue';

import {
  NButton,
  NCard,
  NDataTable,
  NDescriptions,
  NDescriptionsItem,
  NModal,
  NSpace,
  NTag,
  useMessage,
} from 'naive-ui';

import {
  type AdminApiSourceApi,
  getAdminApiSourceDetailApi,
  getAdminApiSourceTestResultApi,
  listAdminApiSourcesApi,
  submitTestAdminApiSourceApi,
  testAdminApiSourceApi,
} from '#/api/admin';

const message = useMessage();

const loading = ref(false);
const rows = ref<AdminApiSourceApi.Source[]>([]);
const total = ref(0);
const page = ref(1);
const pageSize = ref(20);

async function load() {
  loading.value = true;
  try {
    const data = await listAdminApiSourcesApi({
      page: page.value,
      page_size: pageSize.value,
    });
    rows.value = data.list ?? [];
    total.value = data.total ?? 0;
  } catch {
    message.error('API 源列表加载失败');
  } finally {
    loading.value = false;
  }
}

function onPageChange(p: number) {
  page.value = p;
  load();
}
function onPageSizeChange(ps: number) {
  pageSize.value = ps;
  page.value = 1;
  load();
}

// ========= 详情 =========
const detailVisible = ref(false);
const detail = ref<AdminApiSourceApi.Source | null>(null);
const detailLoading = ref(false);
async function openDetail(row: AdminApiSourceApi.Source) {
  detail.value = null;
  detailVisible.value = true;
  detailLoading.value = true;
  try {
    detail.value = await getAdminApiSourceDetailApi(row.id);
  } catch {
    message.error('加载详情失败');
    detailVisible.value = false;
  } finally {
    detailLoading.value = false;
  }
}

// ========= 同步测试 =========
const syncTesting = ref<null | number>(null);
async function onSyncTest(row: AdminApiSourceApi.Source) {
  syncTesting.value = row.id;
  try {
    const res = await testAdminApiSourceApi(row.id);
    if (res?.success) {
      message.success(`[同步] 测试通过：${res?.data?.message ?? ''}`);
    } else {
      message.error(`[同步] 测试失败：${res?.data?.message ?? '未知错误'}`);
    }
    load();
  } catch {
    // interceptor
  } finally {
    syncTesting.value = null;
  }
}

// ========= 异步测试 + 轮询 =========
const asyncTestingId = ref<null | number>(null);
const asyncTaskId = ref<null | string>(null);
const asyncStatus = ref<string>('');
const asyncResult = ref<any>(null);
const resultVisible = ref(false);
let pollTimer: null | ReturnType<typeof setTimeout> = null;

function stopPoll() {
  if (pollTimer) {
    clearTimeout(pollTimer);
    pollTimer = null;
  }
}

async function pollOnce() {
  if (!asyncTaskId.value) return;
  try {
    const row = await getAdminApiSourceTestResultApi(asyncTaskId.value);
    asyncStatus.value = row?.status ?? 'unknown';
    asyncResult.value = row;
    if (['error', 'failed', 'success'].includes(asyncStatus.value)) {
      stopPoll();
      asyncTestingId.value = null;
      load();
      return;
    }
  } catch {
    stopPoll();
    asyncTestingId.value = null;
    return;
  }
  // 仍在 pending/running：1.5s 继续轮询
  pollTimer = setTimeout(pollOnce, 1500);
}

async function onAsyncTest(row: AdminApiSourceApi.Source) {
  stopPoll();
  asyncTestingId.value = row.id;
  asyncTaskId.value = null;
  asyncStatus.value = 'submitting';
  asyncResult.value = null;
  resultVisible.value = true;
  try {
    const res = await submitTestAdminApiSourceApi(row.id);
    asyncTaskId.value = res?.task_id ?? null;
    asyncStatus.value = res?.status ?? 'pending';
    if (asyncTaskId.value) {
      pollTimer = setTimeout(pollOnce, 800);
    } else {
      asyncTestingId.value = null;
      message.error('提交测试任务失败');
    }
  } catch {
    asyncTestingId.value = null;
  }
}

onBeforeUnmount(stopPoll);

const columns: DataTableColumns<AdminApiSourceApi.Source> = [
  { title: 'ID', key: 'id', width: 70 },
  { title: '名称', key: 'name', width: 180 },
  { title: '编码', key: 'code', width: 140 },
  { title: 'endpoint', key: 'endpoint', ellipsis: { tooltip: true } },
  { title: '优先级', key: 'priority', width: 80 },
  {
    title: '状态',
    key: 'status',
    width: 80,
    render: (row) =>
      row.status === 1
        ? h(NTag, { type: 'success', size: 'small' }, () => '启用')
        : h(NTag, { size: 'small' }, () => '禁用'),
  },
  { title: '成功', key: 'success_count', width: 80 },
  { title: '失败', key: 'fail_count', width: 80 },
  {
    title: '上次测试',
    key: 'last_tested_at',
    width: 170,
  },
  {
    title: '上次结果',
    key: 'last_test_status',
    width: 100,
    render: (row) => {
      const s = row.last_test_status;
      if (!s) return '-';
      const type = s === 'success' ? 'success' : 'error';
      return h(NTag, { size: 'small', type }, () => s);
    },
  },
  {
    title: '操作',
    key: 'actions',
    width: 260,
    fixed: 'right',
    render: (row) =>
      h(NSpace, { size: 'small' }, () => [
        h(NButton, { size: 'small', onClick: () => openDetail(row) }, () => '详情'),
        h(
          NButton,
          {
            size: 'small',
            type: 'primary',
            loading: syncTesting.value === row.id,
            onClick: () => onSyncTest(row),
          },
          () => '同步测试',
        ),
        h(
          NButton,
          {
            size: 'small',
            loading: asyncTestingId.value === row.id,
            onClick: () => onAsyncTest(row),
          },
          () => '异步测试',
        ),
      ]),
  },
];

onMounted(load);
</script>

<template>
  <div class="p-6">
    <NCard title="API 源管理">
      <template #header-extra>
        <NButton @click="load">刷新</NButton>
      </template>

      <NDataTable
        remote
        :loading="loading"
        :columns="columns"
        :data="rows"
        :row-key="(row: AdminApiSourceApi.Source) => row.id"
        :scroll-x="1500"
        :pagination="{
          page,
          pageSize,
          itemCount: total,
          pageSizes: [10, 20, 50],
          showSizePicker: true,
          onChange: onPageChange,
          onUpdatePageSize: onPageSizeChange,
        }"
      />
    </NCard>

    <NModal
      v-model:show="detailVisible"
      preset="card"
      title="API 源详情"
      style="width: 760px"
    >
      <div v-if="detailLoading">加载中...</div>
      <NDescriptions
        v-else-if="detail"
        bordered
        :column="2"
        label-placement="left"
      >
        <NDescriptionsItem label="ID">{{ detail.id }}</NDescriptionsItem>
        <NDescriptionsItem label="名称">{{ detail.name }}</NDescriptionsItem>
        <NDescriptionsItem label="编码">{{ detail.code ?? '-' }}</NDescriptionsItem>
        <NDescriptionsItem label="优先级">{{ detail.priority ?? '-' }}</NDescriptionsItem>
        <NDescriptionsItem label="状态">
          {{ detail.status === 1 ? '启用' : '禁用' }}
        </NDescriptionsItem>
        <NDescriptionsItem label="上次测试">{{ detail.last_tested_at ?? '-' }}</NDescriptionsItem>
        <NDescriptionsItem label="上次结果">{{ detail.last_test_status ?? '-' }}</NDescriptionsItem>
        <NDescriptionsItem label="成功/失败">
          {{ detail.success_count ?? 0 }} / {{ detail.fail_count ?? 0 }}
        </NDescriptionsItem>
        <NDescriptionsItem label="endpoint" :span="2">
          <code>{{ detail.endpoint ?? '-' }}</code>
        </NDescriptionsItem>
        <NDescriptionsItem label="config" :span="2">
          <pre style="white-space: pre-wrap; margin: 0">{{
            typeof detail.config === 'string'
              ? detail.config
              : JSON.stringify(detail.config ?? {}, null, 2)
          }}</pre>
        </NDescriptionsItem>
      </NDescriptions>
    </NModal>

    <NModal
      v-model:show="resultVisible"
      preset="card"
      title="异步测试进度"
      style="width: 640px"
      @close="stopPoll"
    >
      <div class="mb-2">
        task_id:
        <code>{{ asyncTaskId ?? '-' }}</code>
      </div>
      <div class="mb-2">
        状态:
        <NTag
          :type="
            asyncStatus === 'success'
              ? 'success'
              : asyncStatus === 'error' || asyncStatus === 'failed'
                ? 'error'
                : 'info'
          "
        >
          {{ asyncStatus }}
        </NTag>
      </div>
      <pre
        v-if="asyncResult"
        style="
          white-space: pre-wrap;
          background: var(--n-color-modal, #f6f6f6);
          padding: 8px;
          border-radius: 4px;
          max-height: 320px;
          overflow: auto;
        "
      >{{ JSON.stringify(asyncResult, null, 2) }}</pre>
    </NModal>
  </div>
</template>
