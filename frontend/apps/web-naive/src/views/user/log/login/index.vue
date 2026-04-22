<script lang="ts" setup>
import type { DataTableColumns } from 'naive-ui';

import { h, onMounted, ref } from 'vue';

import { NCard, NDataTable, NTag, useMessage } from 'naive-ui';

import { listLoginLogsApi, type UserLogApi } from '#/api/user/log';

const message = useMessage();

const loading = ref(false);
const rows = ref<UserLogApi.LoginLog[]>([]);
const total = ref(0);
const page = ref(1);
const pageSize = ref(20);

async function load() {
  loading.value = true;
  try {
    const data = await listLoginLogsApi({
      page: page.value,
      page_size: pageSize.value,
    });
    rows.value = data.list ?? [];
    total.value = data.total ?? 0;
  } catch {
    message.error('登录记录加载失败');
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

const columns: DataTableColumns<UserLogApi.LoginLog> = [
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
  {
    title: 'User Agent',
    key: 'user_agent',
    ellipsis: { tooltip: true },
  },
  { title: '时间', key: 'created_at', width: 180 },
];

onMounted(load);
</script>

<template>
  <div class="p-6">
    <NCard title="登录记录">
      <NDataTable
        remote
        :loading="loading"
        :columns="columns"
        :data="rows"
        :row-key="(row: UserLogApi.LoginLog) => row.id"
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
