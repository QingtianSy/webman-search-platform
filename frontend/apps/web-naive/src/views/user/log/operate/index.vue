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
  NSpace,
  useMessage,
} from 'naive-ui';

import { listOperateLogsApi, type UserLogApi } from '#/api/user/log';
import { usePagination } from '#/composables/usePagination';

const message = useMessage();
const route = useRoute();
const router = useRouter();

const filter = reactive({
  module: '',
  action: '',
  ip: '',
  dateRange: null as [number, number] | null,
  expanded: false,
});

function hydrateFromQuery() {
  const q = route.query;
  if (typeof q.module === 'string') filter.module = q.module;
  if (typeof q.action === 'string') filter.action = q.action;
  if (typeof q.ip === 'string') {
    filter.ip = q.ip;
    filter.expanded = true;
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
  if (filter.module) q.module = filter.module;
  if (filter.action) q.action = filter.action;
  if (filter.ip) q.ip = filter.ip;
  if (filter.dateRange) {
    q.date_from = new Date(filter.dateRange[0]).toISOString().slice(0, 10);
    q.date_to = new Date(filter.dateRange[1]).toISOString().slice(0, 10);
  }
  if (pg.page.value > 1) q.page = String(pg.page.value);
  router.replace({ query: q }).catch(() => {});
}

const pg = usePagination(20);
const loading = ref(false);
const rows = ref<UserLogApi.OperateLog[]>([]);

const MODULE_LABEL: Record<string, string> = {
  payment: '支付',
  api_key: 'API密钥',
  collect: '采集',
  auth: '认证',
  search: '搜索',
  upload: '上传',
};

const ACTION_LABEL: Record<string, string> = {
  create: '创建',
  cancel: '取消',
  continue: '继续',
  toggle: '切换',
  delete: '删除',
  set_default: '设为默认',
  regenerate: '重置',
  submit: '提交',
  login: '登录',
  register: '注册',
  change_password: '修改密码',
  export: '导出',
};

async function load() {
  loading.value = true;
  try {
    const [from, to] = filter.dateRange ?? [];
    const data = await listOperateLogsApi({
      page: pg.page.value,
      page_size: pg.pageSize.value,
      module: filter.module || undefined,
      action: filter.action || undefined,
      ip: filter.ip || undefined,
      date_from: from ? new Date(from).toISOString().slice(0, 10) : undefined,
      date_to: to ? new Date(to).toISOString().slice(0, 10) : undefined,
    });
    rows.value = data.list ?? [];
    pg.apply(data);
  } catch {
    message.error('操作记录加载失败');
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
  filter.module = '';
  filter.action = '';
  filter.ip = '';
  filter.dateRange = null;
  onSearch();
}

// 脱敏：把 password / secret / token 字段值替换
function maskSensitive(obj: unknown): unknown {
  if (!obj || typeof obj !== 'object') return obj;
  if (Array.isArray(obj)) return obj.map(maskSensitive);
  const out: Record<string, unknown> = {};
  const re = /password|secret|token|api_key|auth/i;
  for (const [k, v] of Object.entries(obj as Record<string, unknown>)) {
    if (re.test(k) && typeof v === 'string') {
      out[k] = '******';
    } else {
      out[k] = maskSensitive(v);
    }
  }
  return out;
}

function formatContent(src: null | string | undefined): string {
  if (!src) return '';
  try {
    const j = JSON.parse(src);
    return JSON.stringify(maskSensitive(j), null, 2);
  } catch {
    return src;
  }
}

// 详情 Drawer
const drawerVisible = ref(false);
const detail = ref<null | UserLogApi.OperateLog>(null);
function openDetail(row: UserLogApi.OperateLog) {
  detail.value = row;
  drawerVisible.value = true;
}

const columns = computed<DataTableColumns<UserLogApi.OperateLog>>(() => [
  { title: '模块', key: 'module', width: 120, render: (r) => MODULE_LABEL[r.module] ?? r.module },
  { title: '动作', key: 'action', width: 140, render: (r) => ACTION_LABEL[r.action] ?? r.action },
  { title: '内容', key: 'content', ellipsis: { tooltip: true } },
  { title: 'IP', key: 'ip', width: 140 },
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
        <NInput
          v-model:value="filter.module"
          placeholder="模块"
          clearable
          style="width: 140px"
        />
        <NInput
          v-model:value="filter.action"
          placeholder="动作"
          clearable
          style="width: 140px"
        />
        <template v-if="filter.expanded">
          <NInput
            v-model:value="filter.ip"
            placeholder="IP"
            clearable
            style="width: 160px"
          />
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

    <NCard :bordered="false" size="small" title="操作记录">
      <NDataTable
        remote
        :loading="loading"
        :columns="columns"
        :data="rows"
        :row-key="(row: UserLogApi.OperateLog) => row.id"
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

    <NDrawer v-model:show="drawerVisible" :width="640">
      <NDrawerContent title="操作详情" closable>
        <NDescriptions v-if="detail" :column="1" bordered size="small">
          <NDescriptionsItem label="模块">{{ MODULE_LABEL[detail.module] ?? detail.module }}</NDescriptionsItem>
          <NDescriptionsItem label="动作">{{ ACTION_LABEL[detail.action] ?? detail.action }}</NDescriptionsItem>
          <NDescriptionsItem label="IP">{{ detail.ip ?? '-' }}</NDescriptionsItem>
          <NDescriptionsItem label="时间">{{ detail.created_at }}</NDescriptionsItem>
        </NDescriptions>
        <div class="sub-title">内容（敏感字段已脱敏）</div>
        <pre v-if="detail" class="json-box">{{ formatContent(detail.content) }}</pre>
      </NDrawerContent>
    </NDrawer>
  </div>
</template>

<style scoped>
.mb-3 {
  margin-bottom: 12px;
}
.sub-title {
  margin: 12px 0 6px;
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
