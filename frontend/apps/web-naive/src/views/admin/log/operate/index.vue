<script lang="ts" setup>
// 管理端 · 操作日志。docs/07 §3.2.17。
// 字段：user_id / username / module / action / 时间范围；ip + content 详情 drawer
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
  NTag,
  useMessage,
} from 'naive-ui';

import LogFilterCard from '#/components/admin/log-filter-card.vue';
import { usePagination } from '#/composables/usePagination';

import { type AdminLogApi, listAdminOperateLogsApi } from '#/api/admin';
import { rangeToParams } from '#/utils/datetime';

const message = useMessage();

const loading = ref(false);
const rows = ref<AdminLogApi.OperateLog[]>([]);
const { page, pageSize, total, apply } = usePagination(20);

const filter = reactive<{
  user_id: null | number;
  username: string;
  rangeTs: [number, number] | null;
  module: string;
  action: string;
}>({
  user_id: null,
  username: '',
  rangeTs: null,
  module: '',
  action: '',
});

const drawerRow = ref<AdminLogApi.OperateLog | null>(null);

function buildQuery() {
  const q: AdminLogApi.OperateListParams = {
    page: page.value,
    page_size: pageSize.value,
  };
  if (filter.user_id != null) q.user_id = filter.user_id;
  if (filter.username) q.username = filter.username;
  if (filter.module) q.module = filter.module;
  if (filter.action) q.action = filter.action;
  const dr = rangeToParams(filter.rangeTs);
  if (dr.start_time) q.start_time = dr.start_time;
  if (dr.end_time) q.end_time = dr.end_time;
  return q;
}

async function load() {
  loading.value = true;
  try {
    const data = await listAdminOperateLogsApi(buildQuery());
    rows.value = data.list ?? [];
    apply(data);
  } catch {
    message.error('操作日志加载失败');
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
  filter.module = '';
  filter.action = '';
  page.value = 1;
  load();
}

const columns: DataTableColumns<AdminLogApi.OperateLog> = [
  { title: 'ID', key: 'id', width: 70 },
  { title: '用户ID', key: 'user_id', width: 80 },
  { title: '用户名', key: 'username', width: 130, ellipsis: { tooltip: true } },
  {
    title: '模块',
    key: 'module',
    width: 120,
    render: (r) =>
      r.module
        ? h(NTag, { size: 'small', type: 'info' }, { default: () => r.module })
        : '-',
  },
  { title: '动作', key: 'action', width: 140 },
  { title: '内容', key: 'content', ellipsis: { tooltip: true } },
  { title: 'IP', key: 'ip', width: 130 },
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
    <NCard title="操作日志">
      <LogFilterCard
        v-model="filter"
        :loading="loading"
        :show-export="false"
        @search="onSearch"
        @reset="onReset"
      >
        <template #extra>
          <NInput
            v-model:value="filter.module"
            placeholder="模块"
            clearable
            style="width: 130px"
          />
          <NInput
            v-model:value="filter.action"
            placeholder="动作"
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
        :row-key="(r: AdminLogApi.OperateLog) => r.id"
        :scroll-x="1200"
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
      <NDrawerContent title="操作日志详情" :native-scrollbar="false" closable>
        <template v-if="drawerRow">
          <NDescriptions :column="1" label-placement="left" bordered>
            <NDescriptionsItem label="用户 ID">
              {{ drawerRow.user_id }}
            </NDescriptionsItem>
            <NDescriptionsItem label="用户名">
              {{ drawerRow.username ?? '-' }}
            </NDescriptionsItem>
            <NDescriptionsItem label="模块">
              {{ drawerRow.module }}
            </NDescriptionsItem>
            <NDescriptionsItem label="动作">
              {{ drawerRow.action }}
            </NDescriptionsItem>
            <NDescriptionsItem label="内容">
              <pre class="whitespace-pre-wrap break-all">{{
                drawerRow.content ?? '-'
              }}</pre>
            </NDescriptionsItem>
            <NDescriptionsItem label="IP">
              {{ drawerRow.ip ?? '-' }}
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
