<script lang="ts" setup>
import type { DataTableColumns } from 'naive-ui';

import { h, onMounted, ref } from 'vue';

import { NCard, NDataTable, NTag, useMessage } from 'naive-ui';

import { searchHistoryApi, type SearchApi } from '#/api/user/search';

const message = useMessage();

const loading = ref(false);
const page = ref(1);
const pageSize = ref(20);
const total = ref(0);
const rows = ref<SearchApi.HistoryItem[]>([]);

const columns: DataTableColumns<SearchApi.HistoryItem> = [
  { title: '日志号', key: 'log_no', width: 220 },
  { title: '关键词', key: 'keyword' },
  {
    title: '状态',
    key: 'status',
    width: 80,
    render: (row) =>
      row.status === 1
        ? h(NTag, { type: 'success', size: 'small' }, () => '命中')
        : h(NTag, { type: 'default', size: 'small' }, () => '未命中'),
  },
  { title: '命中数', key: 'hit_count', width: 90 },
  { title: '来源', key: 'source_type', width: 120 },
  { title: '消耗', key: 'consume_quota', width: 80 },
  { title: '耗时(ms)', key: 'cost_ms', width: 100 },
  { title: '时间', key: 'created_at', width: 180 },
];

async function load() {
  loading.value = true;
  try {
    const data = await searchHistoryApi({ page: page.value, page_size: pageSize.value });
    rows.value = data.list;
    total.value = data.total;
  } catch {
    message.error('加载搜索历史失败');
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

onMounted(load);
</script>

<template>
  <div class="p-6">
    <NCard title="搜索日志">
      <NDataTable
        remote
        :loading="loading"
        :columns="columns"
        :data="rows"
        :row-key="(row: SearchApi.HistoryItem) => row.id"
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
