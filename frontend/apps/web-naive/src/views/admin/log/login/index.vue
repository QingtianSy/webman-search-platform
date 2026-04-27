<script lang="ts" setup>
// 管理端 · 登录日志。docs/07 §3.2.18。
// 字段：user_id / username / status(0失败/1成功) / ip / 时间范围；user_agent 详情 drawer
// 失败登录 user_id=0，username 展示 '-'。
import type { DataTableColumns } from 'naive-ui';

import { h, onMounted, reactive, ref, watch } from 'vue';

import {
  NButton,
  NCard,
  NDataTable,
  NDescriptions,
  NDescriptionsItem,
  NDrawer,
  NDrawerContent,
  NInput,
  NSelect,
  NTag,
  useMessage,
} from 'naive-ui';

import LogFilterCard from '#/components/admin/log-filter-card.vue';
import { usePagination } from '#/composables/usePagination';

import { type AdminLogApi, listAdminLoginLogsApi } from '#/api/admin';
import { rangeToParams } from '#/utils/datetime';

const message = useMessage();

const loading = ref(false);
const rows = ref<AdminLogApi.LoginLog[]>([]);
const { page, pageSize, total, apply } = usePagination(20);

const filter = reactive<{
  user_id: null | number;
  username: string;
  rangeTs: [number, number] | null;
  status: null | number;
  ip: string;
}>({
  user_id: null,
  username: '',
  rangeTs: null,
  status: null,
  ip: '',
});

const statusOptions = [
  { label: '全部状态', value: undefined },
  { label: '成功', value: 1 },
  { label: '失败', value: 0 },
];

const drawerRow = ref<AdminLogApi.LoginLog | null>(null);

function buildQuery() {
  const q: AdminLogApi.LoginListParams = {
    page: page.value,
    page_size: pageSize.value,
  };
  if (filter.user_id != null) q.user_id = filter.user_id;
  if (filter.username) q.username = filter.username;
  if (filter.status != null) q.status = filter.status;
  if (filter.ip) q.ip = filter.ip;
  const dr = rangeToParams(filter.rangeTs);
  if (dr.start_time) q.start_time = dr.start_time;
  if (dr.end_time) q.end_time = dr.end_time;
  return q;
}

async function load() {
  loading.value = true;
  try {
    const data = await listAdminLoginLogsApi(buildQuery());
    rows.value = data.list ?? [];
    apply(data);
  } catch {
    message.error('登录日志加载失败');
  } finally {
    loading.value = false;
  }
}

function onSearch() {
  page.value = 1;
  load();
}
function onReset() {
  filter.user_id = null;
  filter.username = '';
  filter.rangeTs = null;
  filter.status = null;
  filter.ip = '';
  page.value = 1;
  load();
}

const columns: DataTableColumns<AdminLogApi.LoginLog> = [
  { title: 'ID', key: 'id', width: 70 },
  { title: '用户ID', key: 'user_id', width: 80 },
  {
    title: '用户名',
    key: 'username',
    width: 140,
    ellipsis: { tooltip: true },
    render: (r) => r.username || '-',
  },
  { title: 'IP', key: 'ip', width: 140 },
  { title: 'UA', key: 'user_agent', ellipsis: { tooltip: true } },
  {
    title: '状态',
    key: 'status',
    width: 90,
    render: (r) =>
      h(
        NTag,
        {
          size: 'small',
          type: r.status === 1 ? 'success' : 'error',
        },
        { default: () => (r.status === 1 ? '成功' : '失败') },
      ),
  },
  { title: '时间', key: 'created_at', width: 170 },
  {
    title: '操作',
    key: 'ops',
    width: 80,
    fixed: 'right',
    render: (r) =>
      h(
        NButton,
        {
          size: 'small',
          quaternary: true,
          type: 'primary',
          onClick: () => (drawerRow.value = r),
        },
        { default: () => '详情' },
      ),
  },
];

watch(page, load);
watch(pageSize, load);

onMounted(load);
</script>

<template>
  <div class="p-6">
    <NCard title="登录日志">
      <LogFilterCard
        v-model="filter"
        :loading="loading"
        :show-export="false"
        @search="onSearch"
        @reset="onReset"
      >
        <template #extra>
          <NSelect
            v-model:value="filter.status"
            :options="statusOptions"
            style="width: 130px"
            placeholder="状态"
          />
          <NInput
            v-model:value="filter.ip"
            placeholder="IP 精确匹配"
            clearable
            style="width: 160px"
          />
        </template>
      </LogFilterCard>

      <NDataTable
        remote
        :loading="loading"
        :columns="columns"
        :data="rows"
        :row-key="(r: AdminLogApi.LoginLog) => r.id"
        :scroll-x="1100"
        :pagination="{
          page,
          pageSize,
          itemCount: total,
          pageSizes: [10, 20, 50, 100],
          showSizePicker: true,
          onChange: (p: number) => (page = p),
          onUpdatePageSize: (ps: number) => {
            pageSize = ps;
            page = 1;
          },
        }"
      />
    </NCard>

    <NDrawer
      :show="!!drawerRow"
      :width="520"
      placement="right"
      @update:show="(v: boolean) => { if (!v) drawerRow = null; }"
    >
      <NDrawerContent title="登录日志详情" :native-scrollbar="false" closable>
        <template v-if="drawerRow">
          <NDescriptions :column="1" label-placement="left" bordered>
            <NDescriptionsItem label="用户 ID">
              {{ drawerRow.user_id }}
            </NDescriptionsItem>
            <NDescriptionsItem label="用户名">
              {{ drawerRow.username || '-' }}
            </NDescriptionsItem>
            <NDescriptionsItem label="IP">
              {{ drawerRow.ip ?? '-' }}
            </NDescriptionsItem>
            <NDescriptionsItem label="User Agent">
              <pre class="whitespace-pre-wrap break-all text-xs">{{
                drawerRow.user_agent ?? '-'
              }}</pre>
            </NDescriptionsItem>
            <NDescriptionsItem label="状态">
              {{ drawerRow.status === 1 ? '成功' : '失败' }}
            </NDescriptionsItem>
            <NDescriptionsItem label="时间">
              {{ drawerRow.created_at }}
            </NDescriptionsItem>
          </NDescriptions>
        </template>
      </NDrawerContent>
    </NDrawer>
  </div>
</template>
