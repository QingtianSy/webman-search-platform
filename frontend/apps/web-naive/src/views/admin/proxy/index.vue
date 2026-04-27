<script lang="ts" setup>
// 管理端 · 代理 IP 池。docs/07 §3.2.19。
// 快速添加单条字符串 / 批量导入多行 / 全量探测进度条 / 筛选分页 / 单行探测与启禁
import type { DataTableColumns } from 'naive-ui';

import { h, onMounted, reactive, ref } from 'vue';

import {
  NAlert,
  NButton,
  NCard,
  NDataTable,
  NForm,
  NFormItem,
  NInput,
  NInputGroup,
  NInputNumber,
  NModal,
  NPopconfirm,
  NProgress,
  NSelect,
  NSpace,
  NSwitch,
  NTag,
  useMessage,
} from 'naive-ui';

import {
  type AdminProxyApi,
  batchImportAdminProxyApi,
  createAdminProxyApi,
  deleteAdminProxyApi,
  listAdminProxiesApi,
  probeAdminProxyApi,
  probeAllAdminProxyApi,
  quickAddAdminProxyApi,
  updateAdminProxyApi,
} from '#/api/admin';

const message = useMessage();

const loading = ref(false);
const rows = ref<AdminProxyApi.Proxy[]>([]);
const total = ref(0);
const page = ref(1);
const pageSize = ref(20);

const filter = reactive<{
  keyword: string;
  protocol: string;
  status: '' | number;
}>({
  keyword: '',
  protocol: '',
  status: '',
});

const protocolOptions = [
  { label: '全部', value: '' },
  { label: 'HTTP', value: 'http' },
  { label: 'HTTPS', value: 'https' },
  { label: 'SOCKS5', value: 'socks5' },
];
const statusOptions = [
  { label: '全部', value: '' },
  { label: '启用', value: 1 },
  { label: '禁用', value: 0 },
];

async function load() {
  loading.value = true;
  try {
    const data = await listAdminProxiesApi({
      keyword: filter.keyword || undefined,
      protocol: filter.protocol || undefined,
      status: filter.status === '' ? undefined : filter.status,
      page: page.value,
      page_size: pageSize.value,
    });
    rows.value = data.list ?? [];
    total.value = data.total ?? 0;
  } catch {
    message.error('代理列表加载失败');
  } finally {
    loading.value = false;
  }
}

function onSearch() {
  page.value = 1;
  load();
}
function onReset() {
  filter.keyword = '';
  filter.protocol = '';
  filter.status = '';
  page.value = 1;
  load();
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

// ========= 快速添加（单条） =========
const quickVisible = ref(false);
const quickRaw = ref('');
async function onQuickAdd() {
  if (!quickRaw.value.trim()) {
    message.warning('请输入代理字符串');
    return;
  }
  try {
    await quickAddAdminProxyApi(quickRaw.value.trim());
    message.success('已添加');
    quickRaw.value = '';
    quickVisible.value = false;
    load();
  } catch {
    // interceptor
  }
}

// ========= 批量导入 =========
const batchVisible = ref(false);
const batchText = ref('');
const batchResult = ref<null | {
  success: number;
  failed: number;
  errors?: string[];
}>(null);
async function onBatchImport() {
  const items = batchText.value
    .split('\n')
    .map((l) => l.trim())
    .filter(Boolean);
  if (items.length === 0) {
    message.warning('请粘贴至少一行代理');
    return;
  }
  try {
    batchResult.value = await batchImportAdminProxyApi(items);
    message.success(
      `导入完成:成功 ${batchResult.value?.success ?? 0} · 失败 ${
        batchResult.value?.failed ?? 0
      }`,
    );
    load();
  } catch {
    // interceptor
  }
}

// ========= 新建 / 编辑 =========
const editorVisible = ref(false);
const editing = ref<AdminProxyApi.Proxy | null>(null);
const form = reactive<{
  id?: number;
  protocol: string;
  host: string;
  port: number;
  username: string;
  password: string;
  tags: string;
  weight: number;
  status: number;
}>({
  protocol: 'http',
  host: '',
  port: 80,
  username: '',
  password: '',
  tags: '',
  weight: 10,
  status: 1,
});
const saving = ref(false);

function openCreate() {
  editing.value = null;
  Object.assign(form, {
    id: undefined,
    protocol: 'http',
    host: '',
    port: 80,
    username: '',
    password: '',
    tags: '',
    weight: 10,
    status: 1,
  });
  editorVisible.value = true;
}

function openEdit(row: AdminProxyApi.Proxy) {
  editing.value = row;
  Object.assign(form, {
    id: row.id,
    protocol: row.protocol ?? 'http',
    host: row.host ?? '',
    port: row.port ?? 80,
    username: row.username ?? '',
    password: row.password ?? '',
    tags: row.tags ?? '',
    weight: row.weight ?? 10,
    status: row.status ?? 1,
  });
  editorVisible.value = true;
}

async function onSave() {
  if (!form.host.trim() || !form.port) {
    message.warning('host / port 必填');
    return;
  }
  saving.value = true;
  try {
    if (editing.value && form.id) {
      await updateAdminProxyApi(form.id, {
        protocol: form.protocol,
        host: form.host,
        port: form.port,
        username: form.username,
        password: form.password,
        tags: form.tags,
        weight: form.weight,
        status: form.status,
      });
      message.success('已更新');
    } else {
      await createAdminProxyApi({
        protocol: form.protocol,
        host: form.host,
        port: form.port,
        username: form.username,
        password: form.password,
        tags: form.tags,
        weight: form.weight,
        status: form.status,
      });
      message.success('已创建');
    }
    editorVisible.value = false;
    load();
  } catch {
    // interceptor
  } finally {
    saving.value = false;
  }
}

async function onDelete(row: AdminProxyApi.Proxy) {
  try {
    await deleteAdminProxyApi(row.id);
    message.success('已删除');
    load();
  } catch {
    // interceptor
  }
}

// ========= 单行探测 =========
const probingId = ref<null | number>(null);
async function onProbe(row: AdminProxyApi.Proxy) {
  probingId.value = row.id;
  try {
    const r = await probeAdminProxyApi(row.id);
    if (r?.success) {
      message.success(`通:${r.latency_ms ?? '-'}ms`);
    } else {
      message.error(`失败:${r?.message ?? '未知'}`);
    }
    load();
  } catch {
    // interceptor
  } finally {
    probingId.value = null;
  }
}

// ========= 全量探测（后端排队，前端本地进度条伪渲染） =========
const probeAllRunning = ref(false);
const probeAllTotal = ref(0);
const probeAllDone = ref(0);
let probeAllTimer: null | ReturnType<typeof setInterval> = null;

async function onProbeAll() {
  if (probeAllRunning.value) return;
  try {
    const res = await probeAllAdminProxyApi();
    probeAllTotal.value = res?.queued ?? total.value;
    probeAllDone.value = 0;
    probeAllRunning.value = true;
    message.info(`已排队 ${probeAllTotal.value} 个代理，后台探测中`);
    // 5s 刷一次列表，基于 last_tested_at 新变化估算进度（简化：每刷一次 +15%）
    probeAllTimer = setInterval(async () => {
      probeAllDone.value = Math.min(
        probeAllTotal.value,
        probeAllDone.value + Math.ceil(probeAllTotal.value * 0.15),
      );
      await load();
      if (probeAllDone.value >= probeAllTotal.value) {
        stopProbeAll();
      }
    }, 5000);
  } catch {
    // interceptor
  }
}
function stopProbeAll() {
  probeAllRunning.value = false;
  if (probeAllTimer) clearInterval(probeAllTimer);
  probeAllTimer = null;
}

async function onToggle(row: AdminProxyApi.Proxy) {
  try {
    await updateAdminProxyApi(row.id, { status: row.status === 1 ? 0 : 1 });
    load();
  } catch {
    // interceptor
  }
}

const columns: DataTableColumns<AdminProxyApi.Proxy> = [
  { title: 'ID', key: 'id', width: 70 },
  {
    title: '协议',
    key: 'protocol',
    width: 90,
    render: (r) => h(NTag, { size: 'small' }, () => r.protocol),
  },
  { title: '主机', key: 'host', width: 180, ellipsis: { tooltip: true } },
  { title: '端口', key: 'port', width: 80 },
  {
    title: '认证',
    key: 'username',
    width: 100,
    render: (r) => (r.username ? '是' : '-'),
  },
  { title: '权重', key: 'weight', width: 70 },
  {
    title: '成/败',
    key: 'sr',
    width: 100,
    render: (r) => `${r.success_count ?? 0} / ${r.fail_count ?? 0}`,
  },
  {
    title: '延迟',
    key: 'latency_ms',
    width: 90,
    render: (r) => (r.latency_ms ? `${r.latency_ms}ms` : '-'),
  },
  {
    title: '上次',
    key: 'last_test_status',
    width: 90,
    render: (r) => {
      if (!r.last_test_status) return '-';
      const type = r.last_test_status === 'success' ? 'success' : 'error';
      return h(NTag, { size: 'small', type }, () => r.last_test_status);
    },
  },
  { title: '探测时间', key: 'last_tested_at', width: 170 },
  {
    title: '状态',
    key: 'status',
    width: 90,
    render: (row) =>
      h(NSwitch, {
        size: 'small',
        value: row.status === 1,
        onUpdateValue: () => onToggle(row),
      }),
  },
  {
    title: '操作',
    key: 'actions',
    width: 240,
    fixed: 'right',
    render: (row) =>
      h(NSpace, { size: 'small' }, () => [
        h(
          NButton,
          {
            size: 'small',
            quaternary: true,
            loading: probingId.value === row.id,
            onClick: () => onProbe(row),
          },
          () => '探测',
        ),
        h(
          NButton,
          {
            size: 'small',
            quaternary: true,
            type: 'primary',
            onClick: () => openEdit(row),
          },
          () => '编辑',
        ),
        h(
          NPopconfirm,
          { onPositiveClick: () => onDelete(row) },
          {
            default: () => '确认删除？',
            trigger: () =>
              h(
                NButton,
                { size: 'small', quaternary: true, type: 'error' },
                () => '删除',
              ),
          },
        ),
      ]),
  },
];

onMounted(load);
</script>

<template>
  <div class="p-6">
    <NCard title="代理 IP 池">
      <template #header-extra>
        <NSpace>
          <NButton @click="quickVisible = true">快速添加</NButton>
          <NButton @click="batchVisible = true">批量导入</NButton>
          <NButton type="primary" @click="openCreate">新建</NButton>
          <NPopconfirm @positive-click="onProbeAll">
            <template #trigger>
              <NButton type="warning" :disabled="probeAllRunning">
                全量探测
              </NButton>
            </template>
            将所有代理投递后端探测队列，可能耗时较长
          </NPopconfirm>
        </NSpace>
      </template>

      <NAlert
        v-if="probeAllRunning"
        type="info"
        class="mb-3"
        :bordered="false"
        closable
        @close="stopProbeAll"
      >
        正在全量探测：{{ probeAllDone }} / {{ probeAllTotal }}
        <NProgress
          :percentage="
            probeAllTotal > 0 ? (probeAllDone / probeAllTotal) * 100 : 0
          "
          :show-indicator="false"
          class="mt-1"
        />
      </NAlert>

      <NSpace class="mb-4">
        <NInputGroup>
          <NInput
            v-model:value="filter.keyword"
            placeholder="host/tags"
            clearable
            style="width: 220px"
            @keydown.enter="onSearch"
          />
          <NButton type="primary" @click="onSearch">搜索</NButton>
        </NInputGroup>
        <NSelect
          v-model:value="filter.protocol"
          :options="protocolOptions"
          style="width: 140px"
        />
        <NSelect
          v-model:value="filter.status"
          :options="statusOptions"
          style="width: 120px"
        />
        <NButton @click="onReset">重置</NButton>
      </NSpace>

      <NDataTable
        remote
        :loading="loading"
        :columns="columns"
        :data="rows"
        :row-key="(r: AdminProxyApi.Proxy) => r.id"
        :scroll-x="1600"
        :pagination="{
          page,
          pageSize,
          itemCount: total,
          pageSizes: [10, 20, 50, 100],
          showSizePicker: true,
          onChange: onPageChange,
          onUpdatePageSize: onPageSizeChange,
        }"
      />
    </NCard>

    <!-- 快速添加 -->
    <NModal
      v-model:show="quickVisible"
      preset="card"
      title="快速添加"
      style="width: 520px"
    >
      <NInput
        v-model:value="quickRaw"
        placeholder="格式:http://user:pass@host:port 或 socks5://host:port"
      />
      <div class="text-xs text-muted-foreground mt-2">
        支持 http / https / socks5；user:pass 可选。
      </div>
      <template #footer>
        <NSpace justify="end">
          <NButton @click="quickVisible = false">取消</NButton>
          <NButton type="primary" @click="onQuickAdd">添加</NButton>
        </NSpace>
      </template>
    </NModal>

    <!-- 批量导入 -->
    <NModal
      v-model:show="batchVisible"
      preset="card"
      title="批量导入"
      style="width: 640px"
    >
      <NInput
        v-model:value="batchText"
        type="textarea"
        :autosize="{ minRows: 10, maxRows: 20 }"
        placeholder="每行一条，格式同快速添加"
      />
      <div
        v-if="batchResult"
        class="mt-2 text-sm"
      >
        成功:{{ batchResult.success }}，失败:{{ batchResult.failed }}
        <ul v-if="batchResult.errors?.length" class="text-xs text-red-500 mt-1">
          <li v-for="(e, i) in batchResult.errors" :key="i">{{ e }}</li>
        </ul>
      </div>
      <template #footer>
        <NSpace justify="end">
          <NButton @click="batchVisible = false">关闭</NButton>
          <NButton type="primary" @click="onBatchImport">导入</NButton>
        </NSpace>
      </template>
    </NModal>

    <!-- 编辑 / 新建 -->
    <NModal
      v-model:show="editorVisible"
      preset="card"
      :title="editing ? '编辑代理' : '新建代理'"
      style="width: 560px"
    >
      <NForm label-placement="left" label-width="auto">
        <NFormItem label="协议" required>
          <NSelect
            v-model:value="form.protocol"
            :options="protocolOptions.filter((o) => o.value !== '')"
            style="width: 160px"
          />
        </NFormItem>
        <NFormItem label="主机" required>
          <NInput v-model:value="form.host" />
        </NFormItem>
        <NFormItem label="端口" required>
          <NInputNumber v-model:value="form.port" :min="1" :max="65535" />
        </NFormItem>
        <NFormItem label="用户名">
          <NInput v-model:value="form.username" />
        </NFormItem>
        <NFormItem label="密码">
          <NInput
            v-model:value="form.password"
            type="password"
            show-password-on="click"
          />
        </NFormItem>
        <NFormItem label="权重">
          <NInputNumber v-model:value="form.weight" :min="0" />
        </NFormItem>
        <NFormItem label="标签">
          <NInput v-model:value="form.tags" placeholder="逗号分隔" />
        </NFormItem>
        <NFormItem label="状态">
          <NSelect
            v-model:value="form.status"
            :options="[
              { label: '启用', value: 1 },
              { label: '禁用', value: 0 },
            ]"
            style="width: 140px"
          />
        </NFormItem>
      </NForm>
      <template #footer>
        <NSpace justify="end">
          <NButton @click="editorVisible = false">取消</NButton>
          <NButton type="primary" :loading="saving" @click="onSave">
            保存
          </NButton>
        </NSpace>
      </template>
    </NModal>
  </div>
</template>
