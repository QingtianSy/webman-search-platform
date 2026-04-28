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

import {
  cancelOrderApi,
  continueOrderApi,
  listOrdersApi,
  type OrderApi,
} from '#/api/user/order';
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

const payMethodOptions = [
  { label: '支付宝支付', value: 'alipay' },
  { label: '微信支付', value: 'wxpay' },
  { label: 'QQ支付', value: 'qqpay' },
];

function payMethodLabel(code: string) {
  return payMethodOptions.find((item) => item.value === code)?.label ?? code;
}

function hydrateFromQuery() {
  const query = route.query;
  if (query.status !== undefined && query.status !== '') {
    const value = Number(query.status);
    if (!Number.isNaN(value)) filter.status = value;
  }
  if (typeof query.pay_method === 'string') filter.pay_method = query.pay_method;
  if (typeof query.order_no === 'string') filter.order_no = query.order_no;
  const dateFrom = typeof query.date_from === 'string' ? query.date_from : '';
  const dateTo = typeof query.date_to === 'string' ? query.date_to : '';
  if (dateFrom && dateTo) {
    const start = new Date(dateFrom).getTime();
    const end = new Date(dateTo).getTime();
    if (!Number.isNaN(start) && !Number.isNaN(end)) {
      filter.dateRange = [start, end];
    }
  }
  const page = Number(query.page);
  if (!Number.isNaN(page) && page > 0) {
    pagination.page.value = page;
  }
}

function pushQuery() {
  const query: Record<string, string> = {};
  if (filter.status !== undefined) query.status = String(filter.status);
  if (filter.pay_method) query.pay_method = filter.pay_method;
  if (filter.order_no) query.order_no = filter.order_no;
  if (filter.dateRange) {
    query.date_from = new Date(filter.dateRange[0]).toISOString().slice(0, 10);
    query.date_to = new Date(filter.dateRange[1]).toISOString().slice(0, 10);
  }
  if (pagination.page.value > 1) query.page = String(pagination.page.value);
  router.replace({ query }).catch(() => {});
}

const pagination = usePagination(20);
const loading = ref(false);
const rows = ref<OrderApi.Order[]>([]);

async function load() {
  loading.value = true;
  try {
    const [from, to] = filter.dateRange ?? [];
    const data = await listOrdersApi({
      page: pagination.page.value,
      page_size: pagination.pageSize.value,
      status: filter.status,
      pay_method: filter.pay_method,
      order_no: filter.order_no || undefined,
      date_from: from ? new Date(from).toISOString().slice(0, 10) : undefined,
      date_to: to ? new Date(to).toISOString().slice(0, 10) : undefined,
    });
    rows.value = data.list ?? [];
    pagination.apply(data);
    startPollingIfNeeded();
  } catch {
    message.error('支付记录加载失败');
  } finally {
    loading.value = false;
  }
}

function onSearch() {
  pagination.page.value = 1;
  pushQuery();
  void load();
}

function onReset() {
  filter.status = undefined;
  filter.pay_method = undefined;
  filter.order_no = '';
  filter.dateRange = null;
  onSearch();
}

let pollTimer: null | ReturnType<typeof setInterval> = null;

function stopPolling() {
  if (pollTimer) {
    clearInterval(pollTimer);
    pollTimer = null;
  }
}

function startPollingIfNeeded() {
  stopPolling();
  if (rows.value.some((row) => Number(row.status) === 0)) {
    pollTimer = setInterval(() => {
      void load();
    }, 60_000);
  }
}

const drawerVisible = ref(false);
const detail = ref<null | OrderApi.Order>(null);

function openDetail(row: OrderApi.Order) {
  detail.value = row;
  drawerVisible.value = true;
}

async function continuePayment(row: OrderApi.Order) {
  try {
    const response = await continueOrderApi(row.order_no);
    if (response.pay_url) {
      window.location.href = response.pay_url;
    } else {
      message.info('请稍后刷新查看订单状态');
    }
  } catch {
    message.error('重新发起支付失败');
  }
}

async function cancelPayment(row: OrderApi.Order) {
  try {
    await cancelOrderApi(row.order_no);
    message.success('已取消');
    await load();
  } catch {
    message.error('取消失败');
  }
}

function statusTag(status: number) {
  switch (Number(status)) {
    case 0:
      return h(NTag, { type: 'default', size: 'small' }, () => '待支付');
    case 1:
      return h(NTag, { type: 'success', size: 'small' }, () => '已支付');
    case 2:
      return h(NTag, { type: 'warning', size: 'small' }, () => '已关闭');
    default:
      return h(NTag, { size: 'small' }, () => `status=${status}`);
  }
}

const columns = computed<DataTableColumns<OrderApi.Order>>(() => [
  { title: '订单号', key: 'order_no', width: 220 },
  {
    title: '类型',
    key: 'order_type',
    width: 100,
    render: (row) => (row.order_type === 'plan' ? '套餐购买' : '余额充值'),
  },
  {
    title: '金额',
    key: 'amount',
    width: 120,
    render: (row) => `¥ ${Number(row.amount).toFixed(2)}`,
  },
  {
    title: '支付方式',
    key: 'pay_method',
    width: 120,
    render: (row) => payMethodLabel(row.pay_method),
  },
  {
    title: '状态',
    key: 'status',
    width: 100,
    render: (row) => statusTag(Number(row.status)),
  },
  { title: '备注', key: 'remark' },
  { title: '创建时间', key: 'created_at', width: 180 },
  {
    title: '操作',
    key: 'actions',
    width: 220,
    fixed: 'right',
    render: (row) =>
      h(NSpace, { size: 'small' }, {
        default: () => [
          h(
            NButton,
            {
              size: 'small',
              text: true,
              type: 'primary',
              onClick: () => openDetail(row),
            },
            { default: () => '详情' },
          ),
          Number(row.status) === 0
            ? h(
                NButton,
                {
                  size: 'small',
                  type: 'primary',
                  ghost: true,
                  onClick: () => continuePayment(row),
                },
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
            { label: '待支付', value: 0 },
            { label: '已支付', value: 1 },
            { label: '已关闭', value: 2 },
          ]"
        />
        <NSelect
          v-model:value="filter.pay_method"
          placeholder="支付方式"
          clearable
          style="width: 140px"
          :options="payMethodOptions"
        />
        <NInput
          v-model:value="filter.order_no"
          placeholder="订单号"
          clearable
          style="width: 220px"
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
        :row-key="(row: OrderApi.Order) => row.order_no"
        :pagination="{
          page: pagination.page.value,
          pageSize: pagination.pageSize.value,
          itemCount: pagination.total.value,
          pageSizes: [10, 20, 50],
          showSizePicker: true,
          onUpdatePage: (page: number) => {
            pagination.onPageChange(page);
            pushQuery();
            void load();
          },
          onUpdatePageSize: (size: number) => {
            pagination.onPageSizeChange(size);
            pushQuery();
            void load();
          },
        }"
      />
    </NCard>

    <NDrawer v-model:show="drawerVisible" :width="520">
      <NDrawerContent title="支付详情" closable>
        <NDescriptions v-if="detail" :column="1" bordered size="small">
          <NDescriptionsItem label="订单号">
            {{ detail.order_no }}
          </NDescriptionsItem>
          <NDescriptionsItem label="订单类型">
            {{ detail.order_type === 'plan' ? '套餐购买' : '余额充值' }}
          </NDescriptionsItem>
          <NDescriptionsItem label="金额">
            ¥ {{ Number(detail.amount).toFixed(2) }}
          </NDescriptionsItem>
          <NDescriptionsItem label="支付方式">
            {{ payMethodLabel(detail.pay_method) }}
          </NDescriptionsItem>
          <NDescriptionsItem label="状态">
            <component :is="statusTag(Number(detail.status))" />
          </NDescriptionsItem>
          <NDescriptionsItem label="备注">
            {{ detail.remark ?? '-' }}
          </NDescriptionsItem>
          <NDescriptionsItem label="支付时间">
            {{ detail.paid_at ?? '-' }}
          </NDescriptionsItem>
          <NDescriptionsItem label="创建时间">
            {{ detail.created_at }}
          </NDescriptionsItem>
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
