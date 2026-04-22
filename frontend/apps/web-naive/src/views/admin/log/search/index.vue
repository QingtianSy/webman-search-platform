<script lang="ts" setup>
import type { DataTableColumns } from 'naive-ui';

import { onMounted, reactive, ref } from 'vue';

import {
  NButton,
  NCard,
  NDataTable,
  NDatePicker,
  NInput,
  NInputGroup,
  NSpace,
  useMessage,
} from 'naive-ui';

import {
  type AdminLogApi,
  exportAdminSearchLogsApi,
  listAdminSearchLogsApi,
} from '#/api/admin';

const message = useMessage();

const loading = ref(false);
const exporting = ref(false);
const rows = ref<AdminLogApi.SearchLog[]>([]);
const total = ref(0);
const page = ref(1);
const pageSize = ref(20);

// NDatePicker daterange 给的是 [startTs, endTs]
const rangeTs = ref<[number, number] | null>(null);
const filter = reactive<{ keyword: string }>({ keyword: '' });

function fmtDate(ts: null | number): null | string {
  if (!ts) return null;
  const d = new Date(ts);
  const pad = (n: number) => `${n}`.padStart(2, '0');
  return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())} ${pad(d.getHours())}:${pad(d.getMinutes())}:${pad(d.getSeconds())}`;
}

function buildParams() {
  return {
    keyword: filter.keyword || undefined,
    start_time: fmtDate(rangeTs.value?.[0] ?? null) ?? undefined,
    end_time: fmtDate(rangeTs.value?.[1] ?? null) ?? undefined,
  };
}

async function load() {
  loading.value = true;
  try {
    const data = await listAdminSearchLogsApi({
      ...buildParams(),
      page: page.value,
      page_size: pageSize.value,
    });
    rows.value = data.list ?? [];
    total.value = data.total ?? 0;
  } catch {
    message.error('搜索日志加载失败');
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
  rangeTs.value = null;
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

async function onExport() {
  exporting.value = true;
  try {
    const blob = (await exportAdminSearchLogsApi(buildParams())) as Blob;
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

const columns: DataTableColumns<AdminLogApi.SearchLog> = [
  { title: 'ID', key: 'id', width: 70 },
  { title: '日志号', key: 'log_no', width: 200, ellipsis: { tooltip: true } },
  { title: '用户ID', key: 'user_id', width: 90 },
  { title: '关键词', key: 'keyword', width: 220, ellipsis: { tooltip: true } },
  { title: '命中条数', key: 'result_count', width: 100 },
  { title: '命中源', key: 'hit_source', width: 140 },
  { title: 'IP', key: 'ip', width: 130 },
  { title: '时间', key: 'created_at', width: 170 },
];

onMounted(load);
</script>

<template>
  <div class="p-6">
    <NCard title="搜索日志">
      <template #header-extra>
        <NButton :loading="exporting" @click="onExport">导出 CSV</NButton>
      </template>

      <NSpace class="mb-4">
        <NInputGroup>
          <NInput
            v-model:value="filter.keyword"
            placeholder="关键词/日志号"
            clearable
            style="width: 240px"
            @keydown.enter="onSearch"
          />
          <NButton type="primary" @click="onSearch">搜索</NButton>
        </NInputGroup>
        <NDatePicker
          v-model:value="rangeTs"
          type="datetimerange"
          clearable
          style="width: 400px"
          placeholder="时间范围"
        />
        <NButton @click="onReset">重置</NButton>
      </NSpace>

      <NDataTable
        remote
        :loading="loading"
        :columns="columns"
        :data="rows"
        :row-key="(row: AdminLogApi.SearchLog) => row.id"
        :scroll-x="1200"
        :pagination="{
          page,
          pageSize,
          itemCount: total,
          pageSizes: [10, 20, 50, 100],
          showSizePicker: true,
          onChange: onPageChange,
          onUpdatePageSize: onPageSizeChange,
        }"
      />
    </NCard>
  </div>
</template>
