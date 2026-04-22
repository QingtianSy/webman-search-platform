<script lang="ts" setup>
import type { DataTableColumns } from 'naive-ui';

import { onMounted, ref } from 'vue';

import { NCard, NDataTable, useMessage } from 'naive-ui';

import { listOperateLogsApi, type UserLogApi } from '#/api/user/log';

const message = useMessage();

const loading = ref(false);
const rows = ref<UserLogApi.OperateLog[]>([]);
const total = ref(0);
const page = ref(1);
const pageSize = ref(20);

async function load() {
  loading.value = true;
  try {
    const data = await listOperateLogsApi({
      page: page.value,
      page_size: pageSize.value,
    });
    rows.value = data.list ?? [];
    total.value = data.total ?? 0;
  } catch {
    message.error('操作记录加载失败');
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

const columns: DataTableColumns<UserLogApi.OperateLog> = [
  { title: '模块', key: 'module', width: 120 },
  { title: '动作', key: 'action', width: 140 },
  {
    title: '内容',
    key: 'content',
    ellipsis: { tooltip: true },
  },
  { title: 'IP', key: 'ip', width: 140 },
  { title: '时间', key: 'created_at', width: 180 },
];

onMounted(load);
</script>

<template>
  <div class="p-6">
    <NCard title="操作记录">
      <NDataTable
        remote
        :loading="loading"
        :columns="columns"
        :data="rows"
        :row-key="(row: UserLogApi.OperateLog) => row.id"
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
  </div>
</template>
