<script lang="ts" setup>
// 管理端 · 支付日志（订单）。docs/07 §3.2.16。
// 字段：user_id / username / status(0/1/2) / pay_type / 时间范围
// status: 0 待支付 / 1 已支付 / 2 已取消或过期
// type: 1 充值 / 2 套餐
import type { DataTableColumns } from 'naive-ui';

import { h, onMounted, reactive, ref, watch } from 'vue';

import {
  NCard,
  NDataTable,
  NSelect,
  NTag,
  useMessage,
} from 'naive-ui';

import LogFilterCard from '#/components/admin/log-filter-card.vue';
import { usePagination } from '#/composables/usePagination';

import { type AdminLogApi, listAdminPaymentLogsApi } from '#/api/admin';
import { rangeToParams } from '#/utils/datetime';

const message = useMessage();

const loading = ref(false);
const rows = ref<AdminLogApi.PaymentLog[]>([]);
const { page, pageSize, total, apply } = usePagination(20);

const filter = reactive<{
  user_id: null | number;
  username: string;
  rangeTs: [number, number] | null;
  status: null | number;
  pay_type: string;
}>({
  user_id: null,
  username: '',
  rangeTs: null,
  status: null,
  pay_type: '',
});

const statusOptions = [
  { label: '全部状态', value: undefined },
  { label: '待支付', value: 0 },
  { label: '已支付', value: 1 },
  { label: '已取消/过期', value: 2 },
];

const payTypeOptions = [
  { label: '全部渠道', value: '' },
  { label: '支付宝', value: 'alipay' },
  { label: '微信', value: 'wxpay' },
  { label: 'QQ 钱包', value: 'qqpay' },
  { label: '网银', value: 'bank' },
];

function buildQuery() {
  const q: AdminLogApi.PaymentListParams = {
    page: page.value,
    page_size: pageSize.value,
  };
  if (filter.user_id != null) q.user_id = filter.user_id;
  if (filter.username) q.username = filter.username;
  if (filter.status != null) q.status = filter.status;
  if (filter.pay_type) q.pay_type = filter.pay_type;
  const dr = rangeToParams(filter.rangeTs);
  if (dr.start_time) q.start_time = dr.start_time;
  if (dr.end_time) q.end_time = dr.end_time;
  return q;
}

async function load() {
  loading.value = true;
  try {
    const data = await listAdminPaymentLogsApi(buildQuery());
    rows.value = data.list ?? [];
    apply(data);
  } catch {
    message.error('支付日志加载失败');
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
  filter.pay_type = '';
  page.value = 1;
  load();
}

const statusTag: Record<number, { type: 'default' | 'success' | 'warning'; text: string }> = {
  0: { type: 'warning', text: '待支付' },
  1: { type: 'success', text: '已支付' },
  2: { type: 'default', text: '已取消' },
};

const columns: DataTableColumns<AdminLogApi.PaymentLog> = [
  { title: 'ID', key: 'id', width: 70 },
  { title: '订单号', key: 'order_no', width: 180, ellipsis: { tooltip: true } },
  { title: '交易号', key: 'trade_no', width: 180, ellipsis: { tooltip: true } },
  { title: '用户ID', key: 'user_id', width: 80 },
  { title: '用户名', key: 'username', width: 130, ellipsis: { tooltip: true } },
  {
    title: '类型',
    key: 'type',
    width: 80,
    render: (r) => (r.type === 2 ? '套餐' : '充值'),
  },
  { title: '金额', key: 'amount', width: 100, render: (r) => `¥${r.amount}` },
  { title: '渠道', key: 'pay_type', width: 100 },
  {
    title: '状态',
    key: 'status',
    width: 100,
    render: (r) => {
      const tag = statusTag[r.status];
      return tag
        ? h(NTag, { size: 'small', type: tag.type }, { default: () => tag.text })
        : r.status;
    },
  },
  { title: '创建', key: 'created_at', width: 170 },
  { title: '支付', key: 'paid_at', width: 170 },
];

watch(page, load);
watch(pageSize, load);

onMounted(load);
</script>

<template>
  <div class="p-6">
    <NCard title="支付日志">
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
          <NSelect
            v-model:value="filter.pay_type"
            :options="payTypeOptions"
            style="width: 130px"
            placeholder="渠道"
          />
        </template>
      </LogFilterCard>

      <NDataTable
        remote
        :loading="loading"
        :columns="columns"
        :data="rows"
        :row-key="(r: AdminLogApi.PaymentLog) => r.id"
        :scroll-x="1500"
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
  </div>
</template>
