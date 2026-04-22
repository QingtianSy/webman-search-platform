<script lang="ts" setup>
import type { DataTableColumns } from 'naive-ui';

import { h, onMounted, ref } from 'vue';

import { NCard, NDataTable, NTag, useMessage } from 'naive-ui';

import { listBalanceLogsApi, type UserLogApi } from '#/api/user/log';

const message = useMessage();

const loading = ref(false);
const rows = ref<UserLogApi.BalanceLog[]>([]);
const total = ref(0);
const page = ref(1);
const pageSize = ref(20);

async function load() {
  loading.value = true;
  try {
    const data = await listBalanceLogsApi({
      page: page.value,
      page_size: pageSize.value,
    });
    rows.value = data.list ?? [];
    total.value = data.total ?? 0;
  } catch {
    message.error('余额流水加载失败');
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

// type 语义（后端口径）：1=充值 2=消费 3=退款 4=调整。
// 没命中枚举的兜底显示数字，避免掩盖异常数据。
function typeTag(type: number) {
  switch (type) {
    case 1: {
      return h(NTag, { type: 'success', size: 'small' }, () => '充值');
    }
    case 2: {
      return h(NTag, { type: 'warning', size: 'small' }, () => '消费');
    }
    case 3: {
      return h(NTag, { type: 'info', size: 'small' }, () => '退款');
    }
    case 4: {
      return h(NTag, { size: 'small' }, () => '调整');
    }
    default: {
      return h(NTag, { size: 'small' }, () => `type=${type}`);
    }
  }
}

const columns: DataTableColumns<UserLogApi.BalanceLog> = [
  {
    title: '类型',
    key: 'type',
    width: 100,
    render: (row) => typeTag(row.type),
  },
  { title: '金额', key: 'amount', width: 120 },
  { title: '变动后余额', key: 'balance_after', width: 140 },
  { title: '备注', key: 'remark' },
  { title: '时间', key: 'created_at', width: 180 },
];

onMounted(load);
</script>

<template>
  <div class="p-6">
    <NCard title="余额流水">
      <NDataTable
        remote
        :loading="loading"
        :columns="columns"
        :data="rows"
        :row-key="(row: UserLogApi.BalanceLog) => row.id"
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
