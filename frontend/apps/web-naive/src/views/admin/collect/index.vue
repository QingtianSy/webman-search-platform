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

const TYPE_LABEL: Record<string, string> = {
  chapter: '章节测试',
  course: '单课程',
  courses: '整号',
  exam: '考试',
  homework: '作业',
};

function statusTag(status: number) {
  const map: Record<number, { text: string; type: any }> = {
    0: { text: '待执行', type: 'default' },
    1: { text: '执行中', type: 'info' },
    2: { text: '成功', type: 'success' },
    3: { text: '失败', type: 'error' },
    4: { text: '已停止', type: 'warning' },
  };
  const m = map[status] ?? { text: `未知(${status})`, type: 'default' };
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
  { title: '账号', key: 'account_phone', width: 140 },
  {
    title: '采集类型',
    key: 'collect_type',
    width: 100,
    render: (row) => TYPE_LABEL[row.collect_type ?? ''] ?? row.collect_type ?? '-',
  },
  { title: '课程数', key: 'course_count', width: 80, align: 'center' },
  { title: '题目数', key: 'question_count', width: 80, align: 'center' },
  {
    title: '状态',
    key: 'status',
    width: 100,
    align: 'center',
    render: (row) => statusTag(row.status),
  },
  {
    title: '错误信息',
    key: 'error_message',
    minWidth: 180,
    ellipsis: { tooltip: true },
    render: (row) => row.error_message || '-',
  },
  { title: '创建时间', key: 'created_at', width: 170 },
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
                  onClick: () => onRetry(row),
                  size: 'small',
                  type: 'primary',
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
            placeholder="任务号/账号"
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
      <NDescriptions
        v-else-if="detail"
        bordered
        :column="2"
        label-placement="left"
      >
        <NDescriptionsItem label="ID">{{ detail.id }}</NDescriptionsItem>
        <NDescriptionsItem label="任务号">{{ detail.task_no }}</NDescriptionsItem>
        <NDescriptionsItem label="用户ID">{{ detail.user_id ?? '-' }}</NDescriptionsItem>
        <NDescriptionsItem label="账号">{{ detail.account_phone ?? '-' }}</NDescriptionsItem>
        <NDescriptionsItem label="采集类型">
          {{ TYPE_LABEL[detail.collect_type ?? ''] ?? detail.collect_type ?? '-' }}
        </NDescriptionsItem>
        <NDescriptionsItem label="状态">
          <component :is="statusTag(detail.status)" />
        </NDescriptionsItem>
        <NDescriptionsItem label="课程数">{{ detail.course_count ?? '-' }}</NDescriptionsItem>
        <NDescriptionsItem label="题目数">{{ detail.question_count ?? '-' }}</NDescriptionsItem>
        <NDescriptionsItem label="成功/失败">
          {{ detail.success_count ?? 0 }} / {{ detail.fail_count ?? 0 }}
        </NDescriptionsItem>
        <NDescriptionsItem label="Runner PID">{{ detail.runner_script || '-' }}</NDescriptionsItem>
        <NDescriptionsItem label="创建时间">{{ detail.created_at }}</NDescriptionsItem>
        <NDescriptionsItem label="更新时间">{{ detail.updated_at ?? '-' }}</NDescriptionsItem>
        <NDescriptionsItem v-if="detail.course_ids" label="课程 IDs" :span="2">
          <span class="break-all text-xs">{{ detail.course_ids }}</span>
        </NDescriptionsItem>
        <NDescriptionsItem v-if="detail.error_message" label="错误信息" :span="2">
          <div class="text-error" style="white-space: pre-wrap">{{ detail.error_message }}</div>
        </NDescriptionsItem>
      </NDescriptions>
    </NModal>
  </div>
</template>
