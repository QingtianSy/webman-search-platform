<script lang="ts" setup>
import type { DataTableColumns } from 'naive-ui';

import { computed, h, onMounted, ref } from 'vue';

import {
  NAlert,
  NButton,
  NCard,
  NDataTable,
  NForm,
  NFormItem,
  NInput,
  NInputNumber,
  NModal,
  NPopconfirm,
  NSpace,
  NSpin,
  NTabs,
  NTabPane,
  NTag,
  NText,
  useMessage,
} from 'naive-ui';

import {
  createApiKeyApi,
  deleteApiKeyApi,
  listApiKeysApi,
  toggleApiKeyApi,
  type ApiKeyApi,
} from '#/api/user/api-key';

const message = useMessage();

const loading = ref(false);
const rows = ref<ApiKeyApi.ApiKeyItem[]>([]);
const total = ref(0);
const page = ref(1);
const pageSize = ref(20);

// 创建表单
const createOpen = ref(false);
const createForm = ref<{ app_name: string }>({ app_name: '' });
const creating = ref(false);

// 新建成功后展示的一次性 secret
const secretModalOpen = ref(false);
const secretResult = ref<ApiKeyApi.CreateResult | null>(null);

async function load() {
  loading.value = true;
  try {
    const data = await listApiKeysApi({
      page: page.value,
      page_size: pageSize.value,
    });
    rows.value = data.list ?? [];
    total.value = data.total ?? 0;
  } catch {
    message.error('加载 API Key 列表失败');
  } finally {
    loading.value = false;
  }
}

async function onCreate() {
  if (!createForm.value.app_name.trim()) {
    message.warning('请输入应用名称');
    return;
  }
  creating.value = true;
  try {
    const data = await createApiKeyApi({
      app_name: createForm.value.app_name.trim(),
    });
    createOpen.value = false;
    createForm.value.app_name = '';
    secretResult.value = data;
    secretModalOpen.value = true;
    load();
  } catch {
    // interceptor
  } finally {
    creating.value = false;
  }
}

async function onToggle(row: ApiKeyApi.ApiKeyItem) {
  const next = row.status === 1 ? 0 : 1;
  try {
    await toggleApiKeyApi(row.id, next);
    message.success(next === 1 ? '已启用' : '已禁用');
    load();
  } catch {
    // ignored
  }
}

async function onDelete(row: ApiKeyApi.ApiKeyItem) {
  try {
    await deleteApiKeyApi(row.id);
    message.success('已删除');
    load();
  } catch {
    // ignored
  }
}

function copySecret() {
  const s = secretResult.value?.api_secret;
  if (!s) return;
  navigator.clipboard
    ?.writeText(s)
    .then(() => message.success('已复制到剪贴板'))
    .catch(() => message.error('复制失败，请手动选择文本'));
}

const columns: DataTableColumns<ApiKeyApi.ApiKeyItem> = [
  { title: '应用', key: 'app_name', width: 160 },
  {
    title: 'API Key',
    key: 'api_key',
    render: (row) => h('code', { class: 'text-xs' }, row.api_key),
  },
  {
    title: '状态',
    key: 'status',
    width: 90,
    render: (row) =>
      row.status === 1
        ? h(NTag, { type: 'success', size: 'small' }, () => '启用')
        : h(NTag, { type: 'default', size: 'small' }, () => '禁用'),
  },
  { title: '创建时间', key: 'created_at', width: 180 },
  {
    title: '操作',
    key: 'actions',
    width: 180,
    render: (row) =>
      h(NSpace, { size: 'small' }, () => [
        h(
          NButton,
          { size: 'small', onClick: () => onToggle(row) },
          () => (row.status === 1 ? '禁用' : '启用'),
        ),
        h(
          NPopconfirm,
          { onPositiveClick: () => onDelete(row) },
          {
            default: () =>
              `确认删除 "${row.app_name}"？删除后该 Key 立即失效。`,
            trigger: () =>
              h(NButton, { size: 'small', type: 'error' }, () => '删除'),
          },
        ),
      ]),
  },
];

function onPageChange(p: number) {
  page.value = p;
  load();
}

function onPageSizeChange(ps: number) {
  pageSize.value = ps;
  page.value = 1;
  load();
}

// ========= 接入示例 / 在线试用 =========
// Open API 实际后端路径为 /api/v1/open/v1/* ；前端 apiURL 已带 /api/v1 前缀，此处拼接展示用完整 URL
const apiBase = computed(() => {
  // 运行时 window.location 推断；SSR 情况下 fallback 到占位
  const origin =
    typeof window === 'undefined' ? 'https://your-domain' : window.location.origin;
  return `${origin}/api/v1/open/v1`;
});

const demoKey = ref('<YOUR_API_KEY>');
const demoSecret = ref('<YOUR_API_SECRET>');
const demoKeyword = ref('');
const demoSplit = ref('###');
const demoTimeout = ref(10);
const demoLoading = ref(false);
const demoResult = ref<null | string>(null);
const demoError = ref<null | string>(null);

async function runDemo() {
  if (!demoKeyword.value.trim() || demoKeyword.value.trim().length < 2) {
    message.warning('关键词至少 2 个字符');
    return;
  }
  if (
    demoKey.value.startsWith('<') ||
    demoSecret.value.startsWith('<') ||
    !demoKey.value ||
    !demoSecret.value
  ) {
    message.warning('请先填入 API Key / Secret');
    return;
  }
  demoLoading.value = true;
  demoResult.value = null;
  demoError.value = null;
  const ctrl = new AbortController();
  const timer = setTimeout(() => ctrl.abort(), demoTimeout.value * 1000);
  try {
    const resp = await fetch(`${apiBase.value}/search/query`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'x-api-key': demoKey.value,
        'x-api-secret': demoSecret.value,
      },
      body: JSON.stringify({
        q: demoKeyword.value.trim(),
        split: demoSplit.value || '###',
      }),
      signal: ctrl.signal,
    });
    const text = await resp.text();
    try {
      const json = JSON.parse(text);
      demoResult.value = JSON.stringify(json, null, 2);
    } catch {
      demoResult.value = text;
    }
  } catch (e: any) {
    demoError.value =
      e?.name === 'AbortError'
        ? `超时（> ${demoTimeout.value}s）`
        : e?.message || String(e);
  } finally {
    clearTimeout(timer);
    demoLoading.value = false;
  }
}

// 样例代码在模板内按 apiBase + demoKey 动态渲染
const curlSnippet = computed(
  () => `curl -X POST "${apiBase.value}/search/query" \\
  -H "Content-Type: application/json" \\
  -H "x-api-key: ${demoKey.value}" \\
  -H "x-api-secret: ${demoSecret.value}" \\
  -d '{"q":"${demoKeyword.value || '示例关键词'}","split":"${demoSplit.value || '###'}"}'`,
);

const jsSnippet = computed(
  () => `// Node / 浏览器环境均可，注意不要在前端代码里硬编码 secret
const resp = await fetch("${apiBase.value}/search/query", {
  method: "POST",
  headers: {
    "Content-Type": "application/json",
    "x-api-key": "${demoKey.value}",
    "x-api-secret": "${demoSecret.value}",
  },
  body: JSON.stringify({
    q: "${demoKeyword.value || '示例关键词'}",
    split: "${demoSplit.value || '###'}",
  }),
});
const data = await resp.json();
console.log(data);`,
);

const pySnippet = computed(
  () => `import requests

resp = requests.post(
    "${apiBase.value}/search/query",
    headers={
        "Content-Type": "application/json",
        "x-api-key": "${demoKey.value}",
        "x-api-secret": "${demoSecret.value}",
    },
    json={"q": "${demoKeyword.value || '示例关键词'}", "split": "${demoSplit.value || '###'}"},
    timeout=10,
)
print(resp.json())`,
);

function copyText(t: string) {
  navigator.clipboard
    ?.writeText(t)
    .then(() => message.success('已复制'))
    .catch(() => message.error('复制失败，请手动选择文本'));
}

onMounted(load);
</script>

<template>
  <div class="p-6">
    <NTabs type="line" animated>
      <NTabPane name="keys" tab="密钥列表">
        <NCard>
          <template #header-extra>
            <NButton type="primary" @click="createOpen = true">
              新建 API Key
            </NButton>
          </template>

          <NAlert type="info" :show-icon="false" class="mb-3">
            api_secret 仅在创建时一次性展示，关闭后不再提供。请立即复制保存。
          </NAlert>

          <NDataTable
            remote
            :loading="loading"
            :columns="columns"
            :data="rows"
            :row-key="(row: ApiKeyApi.ApiKeyItem) => row.id"
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
      </NTabPane>

      <NTabPane name="demo" tab="接入示例 / 在线试用">
        <NCard title="开放平台接入">
          <NAlert type="warning" :show-icon="false" class="mb-3">
            线上调用请在服务端进行，不要把 api_secret 暴露到浏览器前端/APP 包体里。
            本页"在线试用"仅为联调辅助，浏览器发起的请求会经过 CORS，需后端允许 Origin。
          </NAlert>

          <NForm label-placement="top">
            <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
              <NFormItem label="API Key">
                <NInput v-model:value="demoKey" placeholder="ak_xxx" />
              </NFormItem>
              <NFormItem label="API Secret">
                <NInput
                  v-model:value="demoSecret"
                  type="password"
                  show-password-on="click"
                  placeholder="sk_xxx"
                />
              </NFormItem>
            </div>
            <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
              <NFormItem label="关键词 q">
                <NInput
                  v-model:value="demoKeyword"
                  placeholder="至少 2 个字符"
                />
              </NFormItem>
              <NFormItem label="split">
                <NInput v-model:value="demoSplit" placeholder="默认 ###" />
              </NFormItem>
              <NFormItem label="超时(秒)">
                <NInputNumber v-model:value="demoTimeout" :min="1" :max="60" />
              </NFormItem>
            </div>
            <NSpace>
              <NButton type="primary" :loading="demoLoading" @click="runDemo">
                发起请求
              </NButton>
            </NSpace>
          </NForm>

          <div v-if="demoLoading" class="mt-4 flex items-center gap-2">
            <NSpin size="small" /> 请求中...
          </div>

          <NAlert v-if="demoError" type="error" class="mt-4">
            请求失败：{{ demoError }}
          </NAlert>

          <div v-if="demoResult" class="mt-4">
            <div class="mb-2 flex items-center justify-between">
              <span class="text-sm font-medium">响应</span>
              <NButton size="small" @click="copyText(demoResult)">复制</NButton>
            </div>
            <pre
              class="m-0 max-h-80 overflow-auto rounded bg-gray-100 p-3 text-xs dark:bg-neutral-800"
            >{{ demoResult }}</pre>
          </div>
        </NCard>

        <NCard title="代码示例" class="mt-4">
          <NTabs type="segment" default-value="curl">
            <NTabPane name="curl" tab="cURL">
              <div class="flex justify-end">
                <NButton size="small" @click="copyText(curlSnippet)">复制</NButton>
              </div>
              <pre
                class="m-0 mt-2 max-h-96 overflow-auto rounded bg-gray-100 p-3 text-xs dark:bg-neutral-800"
              >{{ curlSnippet }}</pre>
            </NTabPane>
            <NTabPane name="js" tab="JavaScript / Node">
              <div class="flex justify-end">
                <NButton size="small" @click="copyText(jsSnippet)">复制</NButton>
              </div>
              <pre
                class="m-0 mt-2 max-h-96 overflow-auto rounded bg-gray-100 p-3 text-xs dark:bg-neutral-800"
              >{{ jsSnippet }}</pre>
            </NTabPane>
            <NTabPane name="py" tab="Python">
              <div class="flex justify-end">
                <NButton size="small" @click="copyText(pySnippet)">复制</NButton>
              </div>
              <pre
                class="m-0 mt-2 max-h-96 overflow-auto rounded bg-gray-100 p-3 text-xs dark:bg-neutral-800"
              >{{ pySnippet }}</pre>
            </NTabPane>
          </NTabs>

          <NAlert type="info" :show-icon="false" class="mt-4">
            接口清单：<br />
            - POST <code>{{ apiBase }}/search/query</code> body <code>{q, info?, split?}</code><br />
            - GET <code>{{ apiBase }}/quota/detail</code> 返回剩余配额<br />
            错误码：40008 Key 无效 / 40006 配额不足 / 40004 未命中 / 50001 基础设施故障
          </NAlert>
        </NCard>
      </NTabPane>
    </NTabs>

    <!-- 创建表单 -->
    <NModal
      v-model:show="createOpen"
      preset="dialog"
      title="新建 API Key"
      :mask-closable="false"
      :closable="!creating"
      positive-text="创建"
      negative-text="取消"
      :loading="creating"
      @positive-click="onCreate"
    >
      <NForm :model="createForm" label-placement="top">
        <NFormItem label="应用名称" required>
          <NInput
            v-model:value="createForm.app_name"
            placeholder="例如：在线考试脚本"
            maxlength="100"
          />
        </NFormItem>
      </NForm>
    </NModal>

    <!-- 一次性 secret 展示 -->
    <NModal
      v-model:show="secretModalOpen"
      preset="card"
      title="API Key 创建成功"
      style="width: 560px"
      :mask-closable="false"
      :closable="true"
    >
      <NAlert type="warning" :show-icon="false" class="mb-3">
        api_secret 仅此一次显示。关闭本弹窗后将无法再次查看，请立即复制保存。
      </NAlert>
      <div class="space-y-3">
        <div>
          <div class="text-muted-foreground mb-1 text-sm">API Key</div>
          <NText code>{{ secretResult?.api_key }}</NText>
        </div>
        <div>
          <div class="text-muted-foreground mb-1 text-sm">API Secret</div>
          <NText code>{{ secretResult?.api_secret }}</NText>
        </div>
      </div>
      <template #footer>
        <NSpace justify="end">
          <NButton @click="secretModalOpen = false">关闭</NButton>
          <NButton type="primary" @click="copySecret">复制 Secret</NButton>
        </NSpace>
      </template>
    </NModal>
  </div>
</template>
