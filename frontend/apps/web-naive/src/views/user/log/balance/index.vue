<script lang="ts" setup>
import type { DataTableColumns } from 'naive-ui';

import { computed, h, onMounted, reactive, ref } from 'vue';
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
  NSelect,
  NSpace,
  NTag,
  useMessage,
} from 'naive-ui';

import { listBalanceLogsApi, type UserLogApi } from '#/api/user/log';
import { usePagination } from '#/composables/usePagination';

const message = useMessage();
const route = useRoute();
const router = useRouter();

const filter = reactive({
  type: undefined as number | undefined,
  order_no: '',
  dateRange: null as [number, number] | null,
});

// 从 URL query 反序列化到 filter，刷新 / 分享链接保持筛选
function hydrateFromQuery() {
  const q = route.query;
  if (q.type !== undefined && q.type !== '') {
    const n = Number(q.type);
    if (!Number.isNaN(n)) filter.type = n;
  }
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

// 把当前 filter 写回 URL（replace，不污染浏览历史）
function pushQuery() {
  const q: Record<string, string> = {};
  if (filter.type !== undefined) q.type = String(filter.type);
  if (filter.order_no) q.order_no = filter.order_no;
  if (filter.dateRange) {
    q.date_from = new Date(filter.dateRange[0]).toISOString().slice(0, 10);
    q.date_to = new Date(filter.dateRange[1]).toISOString().slice(0, 10);
  }
  if (pg.page.value > 1) q.page = String(pg.page.value);
  router.replace({ query: q }).catch(() => {
    // 相同 query 重复 replace 会抛 NavigationDuplicated，忽略即可
  });
}

const pg = usePagination(20);
const loading = ref(false);
const rows = ref<UserLogApi.BalanceLog[]>([]);

async function load() {
  loading.value = true;
  try {
    const [from, to] = filter.dateRange ?? [];
    const data = await listBalanceLogsApi({
      page: pg.page.value,
      page_size: pg.pageSize.value,
      type: filter.type,
      order_no: filter.order_no || undefined,
      date_from: from ? new Date(from).toISOString().slice(0, 10) : undefined,
      date_to: to ? new Date(to).toISOString().slice(0, 10) : undefined,
    });
    rows.value = data.list ?? [];
    pg.apply(data);
  } catch {
    message.error('余额流水加载失败');
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
  filter.type = undefined;
  filter.order_no = '';
  filter.dateRange = null;
  onSearch();
}

// 详情 Drawer
const drawerVisible = ref(false);
const detail = ref<null | UserLogApi.BalanceLog>(null);
function openDetail(row: UserLogApi.BalanceLog) {
  detail.value = row;
  drawerVisible.value = true;
}

function typeTag(type: number) {
  switch (type) {
    case 1:
      return h(NTag, { type: 'success', size: 'small' }, () => '充值');
    case 2:
      return h(NTag, { type: 'warning', size: 'small' }, () => '消费');
    case 3:
      return h(NTag, { type: 'info', size: 'small' }, () => '退款');
    case 4:
      return h(NTag, { size: 'small' }, () => '调整');
    default:
      return h(NTag, { size: 'small' }, () => `type=${type}`);
  }
}

function amountSign(row: UserLogApi.BalanceLog) {
  // 1/充值、3/退款 为 +；2/消费 为 -；4/调整按符号
  const v = Number(row.amount);
  if (row.type === 2) return { sign: '-', color: '#d03050', abs: v };
  if (row.type === 1 || row.type === 3) return { sign: '+', color: '#18a058', abs: v };
  return { sign: v >= 0 ? '+' : '-', color: v >= 0 ? '#18a058' : '#d03050', abs: Math.abs(v) };
}

const columns = computed<DataTableColumns<UserLogApi.BalanceLog>>(() => [
  {
    title: '类型',
    key: 'type',
    width: 100,
    render: (row) => typeTag(row.type),
  },
  {
    title: '变动',
    key: 'amount',
    width: 130,
    render: (row) => {
      const s = amountSign(row);
      return h(
        'span',
        { style: `color:${s.color};font-weight:600;` },
        `${s.sign} ¥ ${Number(s.abs).toFixed(2)}`,
      );
    },
  },
  { title: '变动后余额', key: 'balance_after', width: 140 },
  { title: '备注', key: 'remark' },
  { title: '时间', key: 'created_at', width: 180 },
  {
    title: '操作',
    key: 'actions',
    width: 80,
    render: (row) =>
      h(
        NButton,
        { size: 'small', text: true, type: 'primary', onClick: () => openDetail(row) },
        { default: () => '详情' },
      ),
  },
]);

onMounted(() => {
  hydrateFromQuery();
  load();
});
</script>

<template>
  <div class="p-6">
    <NCard :bordered="false" size="small" class="mb-3">
      <NSpace>
        <NSelect
          v-model:value="filter.type"
          placeholder="类型"
          clearable
          style="width: 140px"
          :options="[
            { label: '充值', value: 1 },
            { label: '消费', value: 2 },
            { label: '退款', value: 3 },
            { label: '调整', value: 4 },
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

    <NCard :bordered="false" size="small" title="余额流水">
      <NDataTable
        remote
        :loading="loading"
        :columns="columns"
        :data="rows"
        :row-key="(row: UserLogApi.BalanceLog) => row.id"
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
      <NDrawerContent title="流水详情" closable>
        <NDescriptions v-if="detail" :column="1" bordered size="small">
          <NDescriptionsItem label="ID">{{ detail.id }}</NDescriptionsItem>
          <NDescriptionsItem label="类型">
            <component :is="typeTag(detail.type)" />
          </NDescriptionsItem>
          <NDescriptionsItem label="金额">¥ {{ detail.amount }}</NDescriptionsItem>
          <NDescriptionsItem label="变动后余额">
            ¥ {{ detail.balance_after }}
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
