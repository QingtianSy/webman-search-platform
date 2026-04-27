<script lang="ts" setup>
import type { DataTableColumns } from 'naive-ui';

import { computed, h, onBeforeUnmount, onMounted, reactive, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';

import {
  NButton,
  NCard,
  NDataTable,
  NDatePicker,
  NDescriptions,
  NDescriptionsItem,
  NDrawer,
  NDrawerContent,
  NInput,
  NPopconfirm,
  NSelect,
  NSpace,
  NTag,
  useMessage,
} from 'naive-ui';

import { listPaymentLogsApi, type UserLogApi } from '#/api/user/log';
import { cancelOrderApi, continueOrderApi } from '#/api/user/order';
import { usePagination } from '#/composables/usePagination';

const message = useMessage();
const route = useRoute();
const router = useRouter();

const filter = reactive({
  status: undefined as number | undefined,
  pay_method: undefined as string | undefined,
  order_no: '',
  dateRange: null as [number, number] | null,
});

function hydrateFromQuery() {
  const q = route.query;
  if (q.status !== undefined && q.status !== '') {
    const n = Number(q.status);
    if (!Number.isNaN(n)) filter.status = n;
  }
  if (typeof q.pay_method === 'string') filter.pay_method = q.pay_method;
  if (typeof q.order_no === 'string') filter.order_no = q.order_no;
  const df = typeof q.date_from === 'string' ? q.date_from : '';
  const dt = typeof q.date_to === 'string' ? q.date_to : '';
  if (df && dt) {
    const a = new Date(df).getTime();
    const b = new Date(dt).getTime();
    if (!Number.isNaN(a) && !Number.isNaN(b)) filter.dateRange = [a, b];
  }
  const p = Number(q.page);
  if (!Number.isNaN(p) && p > 0) pg.page.value = p;
}
function pushQuery() {
  const q: Record<string, string> = {};
  if (filter.status !== undefined) q.status = String(filter.status);
  if (filter.pay_method) q.pay_method = filter.pay_method;
  if (filter.order_no) q.order_no = filter.order_no;
  if (filter.dateRange) {
    q.date_from = new Date(filter.dateRange[0]).toISOString().slice(0, 10);
    q.date_to = new Date(filter.dateRange[1]).toISOString().slice(0, 10);
  }
  if (pg.page.value > 1) q.page = String(pg.page.value);
  router.replace({ query: q }).catch(() => {});
}

const pg = usePagination(20);
const loading = ref(false);
const rows = ref<UserLogApi.PaymentLog[]>([]);

async function load() {
  loading.value = true;
  try {
    const [from, to] = filter.dateRange ?? [];
    const data = await listPaymentLogsApi({
      page: pg.page.value,
      page_size: pg.pageSize.value,
      status: filter.status,
      pay_method: filter.pay_method,
      order_no: filter.order_no || undefined,
      date_from: from ? new Date(from).toISOString().slice(0, 10) : undefined,
      date_to: to ? new Date(to).toISOString().slice(0, 10) : undefined,
    });
    rows.value = data.list ?? [];
    pg.apply(data);
  } catch {
    message.error('支付记录加载失败');
  } finally {
    loading.value = false;
  }
}

function onSearch() {
  pg.page.value = 1;
  pushQuery();
  load();
}
function onReset() {
  filter.status = undefined;
  filter.pay_method = undefined;
  filter.order_no = '';
  filter.dateRange = null;
  onSearch();
}

// 轮询 pending 订单
let pollTimer: null | ReturnType<typeof setInterval> = null;
function startPollingIfNeeded() {
  stopPolling();
  const hasPending = rows.value.some((r) => Number(r.status) === 0);
  if (hasPending) {
    pollTimer = setInterval(load, 60_000);
  }
}
function stopPolling() {
  if (pollTimer) {
    clearInterval(pollTimer);
    pollTimer = null;
  }
}

// 详情 Drawer
const drawerVisible = ref(false);
const detail = ref<null | UserLogApi.PaymentLog>(null);
function openDetail(row: UserLogApi.PaymentLog) {
  detail.value = row;
  drawerVisible.value = true;
}

async function continuePayment(row: UserLogApi.PaymentLog) {
  try {
    const r = await continueOrderApi(row.order_no);
    if (r?.pay_url) window.location.href = r.pay_url;
    else message.info('请稍后刷新查看状态');
  } catch {
    message.error('重新发起支付失败');
  }
}

async function cancelPayment(row: UserLogApi.PaymentLog) {
  try {
    await cancelOrderApi(row.order_no);
    message.success('已取消');
    load();
  } catch {
    message.error('取消失败');
  }
}

function statusTag(status: number) {
  switch (status) {
    case 0:
      return h(NTag, { type: 'default', size: 'small' }, () => '未支付');
    case 1:
      return h(NTag, { type: 'success', size: 'small' }, () => '已支付');
    case 2:
      return h(NTag, { type: 'error', size: 'small' }, () => '失败');
    case 3:
      return h(NTag, { type: 'info', size: 'small' }, () => '已退款');
    default:
      return h(NTag, { size: 'small' }, () => `status=${status}`);
  }
}

const columns = computed<DataTableColumns<UserLogApi.PaymentLog>>(() => [
  { title: '订单号', key: 'order_no', width: 200 },
  {
    title: '金额',
    key: 'amount',
    width: 120,
    render: (row) => `¥ ${Number(row.amount).toFixed(2)}`,
  },
  { title: '支付方式', key: 'pay_method', width: 120 },
  {
    title: '状态',
    key: 'status',
    width: 100,
    render: (row) => statusTag(row.status),
  },
  { title: '备注', key: 'remark' },
  { title: '时间', key: 'created_at', width: 180 },
  {
    title: '操作',
    key: 'actions',
    width: 200,
    fixed: 'right',
    render: (row) =>
      h(NSpace, { size: 'small' }, {
        default: () => [
          h(
            NButton,
            { size: 'small', text: true, type: 'primary', onClick: () => openDetail(row) },
            { default: () => '详情' },
          ),
          Number(row.status) === 0
            ? h(
                NButton,
                { size: 'small', type: 'primary', ghost: true, onClick: () => continuePayment(row) },
                { default: () => '继续支付' },
              )
            : null,
          Number(row.status) === 0
            ? h(
                NPopconfirm,
                { onPositiveClick: () => cancelPayment(row) },
                {
                  trigger: () =>
                    h(
                      NButton,
                      { size: 'small', type: 'error', ghost: true },
                      { default: () => '取消' },
                    ),
                  default: () => '确认取消订单？',
                },
              )
            : null,
        ],
      }),
  },
]);

onMounted(async () => {
  hydrateFromQuery();
  await load();
  startPollingIfNeeded();
});

onBeforeUnmount(stopPolling);
</script>

<template>
  <div class="p-6">
    <NCard :bordered="false" size="small" class="mb-3">
      <NSpace>
        <NSelect
          v-model:value="filter.status"
          placeholder="状态"
          clearable
          style="width: 140px"
          :options="[
            { label: '未支付', value: 0 },
            { label: '已支付', value: 1 },
            { label: '失败', value: 2 },
            { label: '已退款', value: 3 },
          ]"
        />
        <NSelect
          v-model:value="filter.pay_method"
          placeholder="支付方式"
          clearable
          style="width: 140px"
          :options="[
            { label: '支付宝', value: 'alipay' },
            { label: '微信', value: 'wechat' },
          ]"
        />
        <NInput
          v-model:value="filter.order_no"
          placeholder="订单号"
          clearable
          style="width: 200px"
        />
        <NDatePicker
          v-model:value="filter.dateRange"
          type="daterange"
          clearable
          style="width: 260px"
        />
        <NButton type="primary" @click="onSearch">查询</NButton>
        <NButton @click="onReset">重置</NButton>
      </NSpace>
    </NCard>

    <NCard :bordered="false" size="small" title="支付记录">
      <NDataTable
        remote
        :loading="loading"
        :columns="columns"
        :data="rows"
        :row-key="(row: UserLogApi.PaymentLog) => row.id"
        :pagination="{
          page: pg.page.value,
          pageSize: pg.pageSize.value,
          itemCount: pg.total.value,
          pageSizes: [10, 20, 50],
          showSizePicker: true,
          onUpdatePage: (p: number) => { pg.onPageChange(p); pushQuery(); load(); },
          onUpdatePageSize: (s: number) => { pg.onPageSizeChange(s); pushQuery(); load(); },
        }"
      />
    </NCard>

    <NDrawer v-model:show="drawerVisible" :width="520">
      <NDrawerContent title="支付详情" closable>
        <NDescriptions v-if="detail" :column="1" bordered size="small">
          <NDescriptionsItem label="订单号">{{ detail.order_no }}</NDescriptionsItem>
          <NDescriptionsItem label="金额">¥ {{ detail.amount }}</NDescriptionsItem>
          <NDescriptionsItem label="支付方式">{{ detail.pay_method }}</NDescriptionsItem>
          <NDescriptionsItem label="状态">
            <component :is="statusTag(detail.status)" />
          </NDescriptionsItem>
          <NDescriptionsItem label="备注">{{ detail.remark ?? '-' }}</NDescriptionsItem>
          <NDescriptionsItem label="时间">{{ detail.created_at }}</NDescriptionsItem>
        </NDescriptions>
      </NDrawerContent>
    </NDrawer>
  </div>
</template>

<style scoped>
.mb-3 {
  margin-bottom: 12px;
}
</style>
