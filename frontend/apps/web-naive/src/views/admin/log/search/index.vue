<script lang="ts" setup>
// 搜索日志（admin 跨用户）。docs/07 §3.2.14：
//  - 两行筛选：用户 ID / 用户名 / 时间范围 / 关键词；批量选择 + 批量删除 + 导出
//  - URL 持久化：刷新/返回保留筛选条件
//  - 详情 Drawer：展示 request_payload / response_summary / ip / user_agent 完整字段
import type { DataTableColumns, DataTableRowKey } from 'naive-ui';

import { h, onMounted, reactive, ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';

import {
  NButton,
  NCard,
  NDataTable,
  NDescriptions,
  NDescriptionsItem,
  NDrawer,
  NDrawerContent,
  NInput,
  NPopconfirm,
  NSpace,
  NTag,
  useMessage,
} from 'naive-ui';

import LogFilterCard from '#/components/admin/log-filter-card.vue';
import { usePagination } from '#/composables/usePagination';

import {
  type AdminLogApi,
  deleteAdminSearchLogsApi,
  exportAdminSearchLogsApi,
  listAdminSearchLogsApi,
} from '#/api/admin';
import { rangeToParams } from '#/utils/datetime';

const message = useMessage();
const route = useRoute();
const router = useRouter();

const loading = ref(false);
const exporting = ref(false);
const deleting = ref(false);
const rows = ref<AdminLogApi.SearchLog[]>([]);
const { page, pageSize, total, apply } = usePagination(20);
const selected = ref<number[]>([]);

const filter = reactive<{
  user_id: null | number;
  username: string;
  rangeTs: [number, number] | null;
  keyword: string;
}>({
  user_id: null,
  username: '',
  rangeTs: null,
  keyword: '',
});

const drawerRow = ref<AdminLogApi.SearchLog | null>(null);

function buildQuery() {
  const q: Record<string, any> = {};
  if (filter.user_id != null) q.user_id = filter.user_id;
  if (filter.username) q.username = filter.username;
  if (filter.keyword) q.keyword = filter.keyword;
  const dr = rangeToParams(filter.rangeTs);
  if (dr.start_time) q.start_time = dr.start_time;
  if (dr.end_time) q.end_time = dr.end_time;
  if (page.value > 1) q.page = page.value;
  if (pageSize.value !== 20) q.page_size = pageSize.value;
  return q;
}

function pushUrl() {
  router.replace({ query: buildQuery() });
}

function hydrateFromUrl() {
  const q = route.query;
  if (q.user_id) filter.user_id = Number(q.user_id);
  if (q.username) filter.username = String(q.username);
  if (q.keyword) filter.keyword = String(q.keyword);
  if (q.page) page.value = Number(q.page);
  if (q.page_size) pageSize.value = Number(q.page_size);
  // 时间范围从 URL 反解不做（datetimerange 保存为 ts 对 URL 不友好）
}

async function load() {
  loading.value = true;
  try {
    const data = await listAdminSearchLogsApi({
      ...buildQuery(),
      page: page.value,
      page_size: pageSize.value,
    });
    rows.value = data.list ?? [];
    apply(data);
    pushUrl();
  } catch {
    message.error('搜索日志加载失败');
  } finally {
    loading.value = false;
  }
}

function onSearch() {
  page.value = 1;
  selected.value = [];
  load();
}
function onReset() {
  filter.user_id = null;
  filter.username = '';
  filter.rangeTs = null;
  filter.keyword = '';
  page.value = 1;
  selected.value = [];
  load();
}

async function onExport() {
  exporting.value = true;
  try {
    const blob = (await exportAdminSearchLogsApi(buildQuery())) as Blob;
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `search-logs-${Date.now()}.csv`;
    document.body.append(a);
    a.click();
    a.remove();
    URL.revokeObjectURL(url);
    message.success('导出已开始下载（最多 50000 行）');
  } catch {
    message.error('导出失败');
  } finally {
    exporting.value = false;
  }
}

async function onBatchDelete() {
  if (selected.value.length === 0) return;
  deleting.value = true;
  try {
    const r = await deleteAdminSearchLogsApi(selected.value);
    message.success(`已删除 ${r.deleted} 条`);
    selected.value = [];
    load();
  } catch {
    /* 拦截器已提示 */
  } finally {
    deleting.value = false;
  }
}

const columns: DataTableColumns<AdminLogApi.SearchLog> = [
  { type: 'selection' },
  { title: 'ID', key: 'id', width: 70 },
  { title: '日志号', key: 'log_no', width: 180, ellipsis: { tooltip: true } },
  { title: '用户ID', key: 'user_id', width: 80 },
  { title: '关键词', key: 'keyword', width: 200, ellipsis: { tooltip: true } },
  { title: '命中', key: 'result_count', width: 80 },
  {
    title: '命中源',
    key: 'hit_source',
    width: 110,
    render: (r) =>
      r.hit_source
        ? h(NTag, { size: 'small', type: 'info' }, { default: () => r.hit_source })
        : '-',
  },
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

const rowKey = (r: AdminLogApi.SearchLog) => r.id;

watch(page, load);
watch(pageSize, load);

onMounted(() => {
  hydrateFromUrl();
  load();
});
</script>

<template>
  <div class="p-6">
    <NCard title="搜索日志">
      <LogFilterCard
        v-model="filter"
        :loading="loading"
        :exporting="exporting"
        :show-delete="true"
        :delete-disabled="selected.length === 0"
        @search="onSearch"
        @reset="onReset"
        @export="onExport"
      >
        <template #extra>
          <NInput
            v-model:value="filter.keyword"
            placeholder="关键词/日志号"
            clearable
            style="width: 200px"
            @keydown.enter="onSearch"
          />
          <NPopconfirm @positive-click="onBatchDelete">
            <template #trigger>
              <span />
            </template>
            确认批量删除 {{ selected.length }} 条搜索日志？不可恢复。
          </NPopconfirm>
        </template>
      </LogFilterCard>

      <NSpace v-if="selected.length > 0" class="mb-2">
        <span class="text-sm text-muted-foreground">
          已选 {{ selected.length }} 条
        </span>
        <NPopconfirm @positive-click="onBatchDelete">
          <template #trigger>
            <NButton size="small" type="error" :loading="deleting">
              删除所选
            </NButton>
          </template>
          确认批量删除 {{ selected.length }} 条？不可恢复。
        </NPopconfirm>
      </NSpace>

      <NDataTable
        v-model:checked-row-keys="selected as DataTableRowKey[]"
        remote
        :loading="loading"
        :columns="columns"
        :data="rows"
        :row-key="rowKey"
        :scroll-x="1250"
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
      <NDrawerContent
        title="搜索日志详情"
        :native-scrollbar="false"
        closable
      >
        <template v-if="drawerRow">
          <NDescriptions :column="1" label-placement="left" bordered>
            <NDescriptionsItem label="日志号">
              {{ drawerRow.log_no }}
            </NDescriptionsItem>
            <NDescriptionsItem label="用户 ID">
              {{ drawerRow.user_id }}
            </NDescriptionsItem>
            <NDescriptionsItem label="关键词">
              {{ drawerRow.keyword }}
            </NDescriptionsItem>
            <NDescriptionsItem label="命中条数">
              {{ drawerRow.result_count ?? '-' }}
            </NDescriptionsItem>
            <NDescriptionsItem label="命中源">
              {{ drawerRow.hit_source ?? '-' }}
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
