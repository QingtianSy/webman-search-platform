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

import {
  searchHistoryApi,
  searchQueryApi,
  type SearchApi,
} from '#/api/user/search';
import { usePagination } from '#/composables/usePagination';

const message = useMessage();
const route = useRoute();
const router = useRouter();

const filter = reactive({
  keyword: '',
  question_type: undefined as number | string | undefined,
  status: undefined as number | undefined,
  dateRange: null as [number, number] | null,
  expanded: false,
});

function hydrateFromQuery() {
  const q = route.query;
  if (typeof q.keyword === 'string') filter.keyword = q.keyword;
  if (typeof q.question_type === 'string') filter.question_type = q.question_type;
  if (q.status !== undefined && q.status !== '') {
    const n = Number(q.status);
    if (!Number.isNaN(n)) filter.status = n;
  }
  const df = typeof q.date_from === 'string' ? q.date_from : '';
  const dt = typeof q.date_to === 'string' ? q.date_to : '';
  if (df && dt) {
    const a = new Date(df).getTime();
    const b = new Date(dt).getTime();
    if (!Number.isNaN(a) && !Number.isNaN(b)) {
      filter.dateRange = [a, b];
      filter.expanded = true;
    }
  }
  const p = Number(q.page);
  if (!Number.isNaN(p) && p > 0) pg.page.value = p;
}
function pushQuery() {
  const q: Record<string, string> = {};
  if (filter.keyword) q.keyword = filter.keyword;
  if (filter.question_type !== undefined) q.question_type = String(filter.question_type);
  if (filter.status !== undefined) q.status = String(filter.status);
  if (filter.dateRange) {
    q.date_from = new Date(filter.dateRange[0]).toISOString().slice(0, 10);
    q.date_to = new Date(filter.dateRange[1]).toISOString().slice(0, 10);
  }
  if (pg.page.value > 1) q.page = String(pg.page.value);
  router.replace({ query: q }).catch(() => {});
}

const pg = usePagination(20);
const loading = ref(false);
const rows = ref<SearchApi.HistoryItem[]>([]);

async function load() {
  loading.value = true;
  try {
    // HistoryParams 已包含 keyword/question_type/status，后端未实现时会忽略
    const data = await searchHistoryApi({
      page: pg.page.value,
      page_size: pg.pageSize.value,
      ...(filter.keyword && { keyword: filter.keyword }),
      ...(filter.question_type !== undefined && {
        question_type: filter.question_type,
      }),
      ...(filter.status !== undefined && { status: filter.status }),
    });
    rows.value = data.list ?? [];
    pg.apply(data);
  } catch {
    message.error('加载搜索历史失败');
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
  filter.keyword = '';
  filter.question_type = undefined;
  filter.status = undefined;
  filter.dateRange = null;
  onSearch();
}

// 详情 Drawer
const drawerVisible = ref(false);
const detail = ref<null | SearchApi.HistoryItem>(null);
const retrying = ref(false);
const retryResult = ref<null | SearchApi.QueryResult>(null);

function openDetail(row: SearchApi.HistoryItem) {
  detail.value = row;
  retryResult.value = null;
  drawerVisible.value = true;
}

async function retryQuery(row: SearchApi.HistoryItem) {
  retrying.value = true;
  try {
    retryResult.value = await searchQueryApi({ q: row.keyword });
    message.success(`命中 ${retryResult.value.hit_count} 条`);
  } catch {
    message.error('重试失败');
  } finally {
    retrying.value = false;
  }
}

const columns = computed<DataTableColumns<SearchApi.HistoryItem>>(() => [
  { title: '日志号', key: 'log_no', width: 220 },
  { title: '关键词', key: 'keyword', ellipsis: { tooltip: true } },
  {
    title: '状态',
    key: 'status',
    width: 90,
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
  {
    title: '操作',
    key: 'actions',
    width: 140,
    fixed: 'right',
    render: (row) =>
      h(NSpace, { size: 'small' }, {
        default: () => [
          h(
            NButton,
            { size: 'small', text: true, type: 'primary', onClick: () => openDetail(row) },
            { default: () => '详情' },
          ),
          row.status !== 1
            ? h(
                NButton,
                { size: 'small', ghost: true, type: 'warning', onClick: () => retryQuery(row) },
                { default: () => '重试' },
              )
            : null,
        ],
      }),
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
        <NInput
          v-model:value="filter.keyword"
          placeholder="关键词"
          clearable
          style="width: 220px"
        />
        <NSelect
          v-model:value="filter.question_type"
          placeholder="题型"
          clearable
          style="width: 140px"
          :options="[
            { label: '单选题', value: 'single' },
            { label: '多选题', value: 'multiple' },
            { label: '判断题', value: 'judgement' },
            { label: '填空题', value: 'completion' },
          ]"
        />
        <NSelect
          v-model:value="filter.status"
          placeholder="状态"
          clearable
          style="width: 120px"
          :options="[
            { label: '命中', value: 1 },
            { label: '未命中', value: 0 },
          ]"
        />
        <template v-if="filter.expanded">
          <NDatePicker
            v-model:value="filter.dateRange"
            type="daterange"
            clearable
            style="width: 260px"
          />
        </template>
        <NButton type="primary" @click="onSearch">查询</NButton>
        <NButton @click="onReset">重置</NButton>
        <NButton text type="primary" @click="filter.expanded = !filter.expanded">
          {{ filter.expanded ? '收起' : '更多' }}
        </NButton>
      </NSpace>
    </NCard>

    <NCard :bordered="false" size="small" title="搜索日志">
      <NDataTable
        remote
        :loading="loading"
        :columns="columns"
        :data="rows"
        :row-key="(row: SearchApi.HistoryItem) => row.id"
        :pagination="{
          page: pg.page.value,
          pageSize: pg.pageSize.value,
          itemCount: pg.total.value,
          pageSizes: [10, 20, 50, 100],
          showSizePicker: true,
          onUpdatePage: (p: number) => { pg.onPageChange(p); pushQuery(); load(); },
          onUpdatePageSize: (s: number) => { pg.onPageSizeChange(s); pushQuery(); load(); },
        }"
      />
    </NCard>

    <NDrawer v-model:show="drawerVisible" :width="720">
      <NDrawerContent title="搜索详情" closable>
        <NDescriptions v-if="detail" :column="1" bordered size="small">
          <NDescriptionsItem label="日志号">{{ detail.log_no }}</NDescriptionsItem>
          <NDescriptionsItem label="关键词">{{ detail.keyword }}</NDescriptionsItem>
          <NDescriptionsItem label="题型">
            {{ detail.question_type ?? '-' }}
          </NDescriptionsItem>
          <NDescriptionsItem label="状态">
            <NTag :type="detail.status === 1 ? 'success' : 'default'" size="small">
              {{ detail.status === 1 ? '命中' : '未命中' }}
            </NTag>
          </NDescriptionsItem>
          <NDescriptionsItem label="命中数">{{ detail.hit_count }}</NDescriptionsItem>
          <NDescriptionsItem label="来源">{{ detail.source_type }}</NDescriptionsItem>
          <NDescriptionsItem label="消耗额度">{{ detail.consume_quota }}</NDescriptionsItem>
          <NDescriptionsItem label="耗时">{{ detail.cost_ms }} ms</NDescriptionsItem>
          <NDescriptionsItem label="时间">{{ detail.created_at }}</NDescriptionsItem>
        </NDescriptions>

        <div v-if="detail && detail.status !== 1" class="mt-4">
          <NButton type="warning" :loading="retrying" @click="retryQuery(detail)">
            再次搜索
          </NButton>
        </div>

        <div v-if="retryResult" class="mt-4">
          <div class="sub-title">重试结果（{{ retryResult.hit_count }} 条）</div>
          <pre class="json-box">{{ JSON.stringify(retryResult.list, null, 2) }}</pre>
        </div>
      </NDrawerContent>
    </NDrawer>
  </div>
</template>

<style scoped>
.mb-3 {
  margin-bottom: 12px;
}
.mt-4 {
  margin-top: 16px;
}
.sub-title {
  margin: 0 0 6px;
  font-size: 13px;
  font-weight: 600;
  color: #2080f0;
}
.json-box {
  background: #f5f7fa;
  padding: 12px;
  border-radius: 4px;
  font-size: 12px;
  max-height: 360px;
  overflow: auto;
  white-space: pre-wrap;
  word-break: break-all;
}
</style>
