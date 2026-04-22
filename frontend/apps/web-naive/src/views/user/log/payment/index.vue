<script lang="ts" setup>
import type { DataTableColumns } from 'naive-ui';

import { h, onMounted, ref } from 'vue';

import { NCard, NDataTable, NTag, useMessage } from 'naive-ui';

import { listPaymentLogsApi, type UserLogApi } from '#/api/user/log';

const message = useMessage();

const loading = ref(false);
const rows = ref<UserLogApi.PaymentLog[]>([]);
const total = ref(0);
const page = ref(1);
const pageSize = ref(20);

async function load() {
  loading.value = true;
  try {
    const data = await listPaymentLogsApi({
      page: page.value,
      page_size: pageSize.value,
    });
    rows.value = data.list ?? [];
    total.value = data.total ?? 0;
  } catch {
    message.error('支付记录加载失败');
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

// status：0=未支付 1=已支付 2=失败 3=退款（后端口径；未命中显示原值便于排查）。
function statusTag(status: number) {
  switch (status) {
    case 0: {
      return h(NTag, { type: 'default', size: 'small' }, () => '未支付');
    }
    case 1: {
      return h(NTag, { type: 'success', size: 'small' }, () => '已支付');
    }
    case 2: {
      return h(NTag, { type: 'error', size: 'small' }, () => '失败');
    }
    case 3: {
      return h(NTag, { type: 'info', size: 'small' }, () => '已退款');
    }
    default: {
      return h(NTag, { size: 'small' }, () => `status=${status}`);
    }
  }
}

const columns: DataTableColumns<UserLogApi.PaymentLog> = [
  { title: '订单号', key: 'order_no', width: 200 },
  { title: '金额', key: 'amount', width: 100 },
  { title: '支付方式', key: 'pay_method', width: 120 },
  {
    title: '状态',
    key: 'status',
    width: 100,
    render: (row) => statusTag(row.status),
  },
  { title: '备注', key: 'remark' },
  { title: '时间', key: 'created_at', width: 180 },
];

onMounted(load);
</script>

<template>
  <div class="p-6">
    <NCard title="支付记录">
      <NDataTable
        remote
        :loading="loading"
        :columns="columns"
        :data="rows"
        :row-key="(row: UserLogApi.PaymentLog) => row.id"
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
