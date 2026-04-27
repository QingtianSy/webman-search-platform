<script lang="ts" setup>
// 管理端 · 余额变动日志（跨用户）。docs/07 §3.2.15。
// 字段：user_id / username / type(recharge|consume|refund) / 时间范围
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

import { type AdminLogApi, listAdminBalanceLogsApi } from '#/api/admin';
import { rangeToParams } from '#/utils/datetime';

const message = useMessage();

const loading = ref(false);
const rows = ref<AdminLogApi.BalanceLog[]>([]);
const { page, pageSize, total, apply } = usePagination(20);

const filter = reactive<{
  user_id: null | number;
  username: string;
  rangeTs: [number, number] | null;
  type: string;
}>({
  user_id: null,
  username: '',
  rangeTs: null,
  type: '',
});

const typeOptions = [
  { label: '全部类型', value: '' },
  { label: '充值', value: 'recharge' },
  { label: '消费', value: 'consume' },
  { label: '退款', value: 'refund' },
];

function buildQuery() {
  const q: AdminLogApi.BalanceListParams = {
    page: page.value,
    page_size: pageSize.value,
  };
  if (filter.user_id != null) q.user_id = filter.user_id;
  if (filter.username) q.username = filter.username;
  if (filter.type) q.type = filter.type;
  const dr = rangeToParams(filter.rangeTs);
  if (dr.start_time) q.start_time = dr.start_time;
  if (dr.end_time) q.end_time = dr.end_time;
  return q;
}

async function load() {
  loading.value = true;
  try {
    const data = await listAdminBalanceLogsApi(buildQuery());
    rows.value = data.list ?? [];
    apply(data);
  } catch {
    message.error('余额日志加载失败');
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
  filter.type = '';
  page.value = 1;
  load();
}

const typeTag: Record<string, { type: 'success' | 'warning' | 'error'; text: string }> = {
  recharge: { type: 'success', text: '充值' },
  consume: { type: 'warning', text: '消费' },
  refund: { type: 'error', text: '退款' },
};

const columns: DataTableColumns<AdminLogApi.BalanceLog> = [
  { title: 'ID', key: 'id', width: 70 },
  { title: '用户ID', key: 'user_id', width: 80 },
  { title: '用户名', key: 'username', width: 140, ellipsis: { tooltip: true } },
  {
    title: '类型',
    key: 'type',
    width: 90,
    render: (r) => {
      const tag = typeTag[r.type];
      return tag
        ? h(NTag, { size: 'small', type: tag.type }, { default: () => tag.text })
        : r.type;
    },
  },
  {
    title: '金额',
    key: 'amount',
    width: 120,
    render: (r) => {
      const isIn = r.type === 'recharge' || r.type === 'refund';
      return h(
        'span',
        { class: isIn ? 'text-green-500' : 'text-red-500' },
        `${isIn ? '+' : '-'}¥${r.amount}`,
      );
    },
  },
  { title: '余额', key: 'balance_after', width: 110, render: (r) => `¥${r.balance_after}` },
  { title: '备注', key: 'remark', ellipsis: { tooltip: true } },
  { title: '时间', key: 'created_at', width: 170 },
];

watch(page, load);
watch(pageSize, load);

onMounted(load);
</script>

<template>
  <div class="p-6">
    <NCard title="余额变动日志">
      <LogFilterCard
        v-model="filter"
        :loading="loading"
        :show-export="false"
        @search="onSearch"
        @reset="onReset"
      >
        <template #extra>
          <NSelect
            v-model:value="filter.type"
            :options="typeOptions"
            style="width: 130px"
            placeholder="类型"
          />
        </template>
      </LogFilterCard>

      <NDataTable
        remote
        :loading="loading"
        :columns="columns"
        :data="rows"
        :row-key="(r: AdminLogApi.BalanceLog) => r.id"
        :scroll-x="1100"
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
