<script lang="ts" setup>
import type { DataTableColumns } from 'naive-ui';

import { h, onMounted, reactive, ref } from 'vue';

import {
  NButton,
  NCard,
  NDataTable,
  NDescriptions,
  NDescriptionsItem,
  NInput,
  NInputGroup,
  NModal,
  NPopconfirm,
  NSelect,
  NSpace,
  NTag,
  useMessage,
} from 'naive-ui';

import {
  type AdminCollectApi,
  getAdminCollectTaskDetailApi,
  listAdminCollectTasksApi,
  retryAdminCollectTaskApi,
  stopAdminCollectTaskApi,
} from '#/api/admin';

const message = useMessage();

const loading = ref(false);
const rows = ref<AdminCollectApi.Task[]>([]);
const total = ref(0);
const page = ref(1);
const pageSize = ref(20);

const filter = reactive<{ keyword: string; status: '' | number }>({
  keyword: '',
  status: '',
});
const statusOptions = [
  { label: '全部', value: '' },
  { label: '待执行', value: 0 },
  { label: '执行中', value: 1 },
  { label: '成功', value: 2 },
  { label: '失败', value: 3 },
  { label: '已停止', value: 4 },
];

function statusTag(status: number) {
  const map: Record<number, { type: any; text: string }> = {
    0: { type: 'default', text: '待执行' },
    1: { type: 'info', text: '执行中' },
    2: { type: 'success', text: '成功' },
    3: { type: 'error', text: '失败' },
    4: { type: 'warning', text: '已停止' },
  };
  const m = map[status] ?? { type: 'default', text: `未知(${status})` };
  return h(NTag, { size: 'small', type: m.type }, () => m.text);
}

async function load() {
  loading.value = true;
  try {
    const data = await listAdminCollectTasksApi({
      keyword: filter.keyword || undefined,
      status: filter.status === '' ? undefined : filter.status,
      page: page.value,
      page_size: pageSize.value,
    });
    rows.value = data.list ?? [];
    total.value = data.total ?? 0;
  } catch {
    message.error('采集任务加载失败');
  } finally {
    loading.value = false;
  }
}

function onSearch() {
  page.value = 1;
  load();
}
function onReset() {
  filter.keyword = '';
  filter.status = '';
  page.value = 1;
  load();
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

async function onStop(row: AdminCollectApi.Task) {
  try {
    await stopAdminCollectTaskApi(row.task_no);
    message.success('已下发停止');
    load();
  } catch {
    // interceptor
  }
}

async function onRetry(row: AdminCollectApi.Task) {
  try {
    await retryAdminCollectTaskApi(row.task_no);
    message.success('已下发重试');
    load();
  } catch {
    // interceptor
  }
}

// ========= 详情 =========
const detailVisible = ref(false);
const detail = ref<AdminCollectApi.Task | null>(null);
const detailLoading = ref(false);
async function openDetail(row: AdminCollectApi.Task) {
  detail.value = null;
  detailVisible.value = true;
  detailLoading.value = true;
  try {
    detail.value = await getAdminCollectTaskDetailApi(row.task_no);
  } catch {
    message.error('加载详情失败');
    detailVisible.value = false;
  } finally {
    detailLoading.value = false;
  }
}

const columns: DataTableColumns<AdminCollectApi.Task> = [
  { title: 'ID', key: 'id', width: 70 },
  {
    title: '任务号',
    key: 'task_no',
    width: 220,
    ellipsis: { tooltip: true },
  },
  { title: '用户ID', key: 'user_id', width: 80 },
  { title: '来源', key: 'source', width: 120 },
  { title: '关键词', key: 'keyword', width: 160, ellipsis: { tooltip: true } },
  {
    title: '状态',
    key: 'status',
    width: 90,
    render: (row) => statusTag(row.status),
  },
  { title: '进度', key: 'progress', width: 80 },
  { title: '命中', key: 'result_count', width: 80 },
  { title: '创建', key: 'created_at', width: 170 },
  { title: '完成', key: 'finished_at', width: 170 },
  {
    title: '操作',
    key: 'actions',
    width: 220,
    fixed: 'right',
    render: (row) =>
      h(NSpace, { size: 'small' }, () => {
        const canStop = row.status === 0 || row.status === 1;
        const canRetry = row.status === 3 || row.status === 4;
        return [
          h(
            NButton,
            { size: 'small', onClick: () => openDetail(row) },
            () => '详情',
          ),
          canStop
            ? h(
                NPopconfirm,
                { onPositiveClick: () => onStop(row) },
                {
                  default: () => '确认停止该任务？',
                  trigger: () =>
                    h(
                      NButton,
                      { size: 'small', type: 'warning' },
                      () => '停止',
                    ),
                },
              )
            : null,
          canRetry
            ? h(
                NButton,
                {
                  size: 'small',
                  type: 'primary',
                  onClick: () => onRetry(row),
                },
                () => '重试',
              )
            : null,
        ];
      }),
  },
];

onMounted(load);
</script>

<template>
  <div class="p-6">
    <NCard title="采集管理">
      <template #header-extra>
        <NButton @click="load">刷新</NButton>
      </template>

      <NSpace class="mb-4">
        <NInputGroup>
          <NInput
            v-model:value="filter.keyword"
            placeholder="任务号/关键词/来源"
            clearable
            style="width: 260px"
            @keydown.enter="onSearch"
          />
          <NButton type="primary" @click="onSearch">搜索</NButton>
        </NInputGroup>
        <NSelect
          v-model:value="filter.status"
          :options="statusOptions"
          style="width: 140px"
        />
        <NButton @click="onReset">重置</NButton>
      </NSpace>

      <NDataTable
        remote
        :loading="loading"
        :columns="columns"
        :data="rows"
        :row-key="(row: AdminCollectApi.Task) => row.id"
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
      title="任务详情"
      style="width: 720px"
    >
      <div v-if="detailLoading">加载中...</div>
      <NDescriptions v-else-if="detail" bordered :column="2" label-placement="left">
        <NDescriptionsItem label="ID">{{ detail.id }}</NDescriptionsItem>
        <NDescriptionsItem label="任务号">{{ detail.task_no }}</NDescriptionsItem>
        <NDescriptionsItem label="用户ID">{{ detail.user_id ?? '-' }}</NDescriptionsItem>
        <NDescriptionsItem label="来源">{{ detail.source ?? '-' }}</NDescriptionsItem>
        <NDescriptionsItem label="关键词">{{ detail.keyword ?? '-' }}</NDescriptionsItem>
        <NDescriptionsItem label="状态">
          <NTag
            size="small"
            :type="
              detail.status === 2
                ? 'success'
                : detail.status === 3
                  ? 'error'
                  : detail.status === 4
                    ? 'warning'
                    : detail.status === 1
                      ? 'info'
                      : 'default'
            "
          >
            {{
              {
                0: '待执行',
                1: '执行中',
                2: '成功',
                3: '失败',
                4: '已停止',
              }[detail.status] ?? `未知(${detail.status})`
            }}
          </NTag>
        </NDescriptionsItem>
        <NDescriptionsItem label="进度">{{ detail.progress ?? '-' }}</NDescriptionsItem>
        <NDescriptionsItem label="命中条数">{{ detail.result_count ?? '-' }}</NDescriptionsItem>
        <NDescriptionsItem label="创建时间">{{ detail.created_at }}</NDescriptionsItem>
        <NDescriptionsItem label="更新时间">{{ detail.updated_at ?? '-' }}</NDescriptionsItem>
        <NDescriptionsItem label="完成时间" :span="2">{{ detail.finished_at ?? '-' }}</NDescriptionsItem>
        <NDescriptionsItem label="消息" :span="2">
          <div style="white-space: pre-wrap">{{ detail.message || '-' }}</div>
        </NDescriptionsItem>
      </NDescriptions>
    </NModal>
  </div>
</template>
