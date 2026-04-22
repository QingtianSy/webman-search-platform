<script lang="ts" setup>
import type { DataTableColumns } from 'naive-ui';

import { h, onMounted, ref } from 'vue';

import {
  NAlert,
  NButton,
  NCard,
  NDataTable,
  NForm,
  NFormItem,
  NInput,
  NModal,
  NPopconfirm,
  NSpace,
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
    const data = await listApiKeysApi({ page: page.value, page_size: pageSize.value });
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
    const data = await createApiKeyApi({ app_name: createForm.value.app_name.trim() });
    createOpen.value = false;
    createForm.value.app_name = '';
    secretResult.value = data;
    secretModalOpen.value = true;
    load();
  } catch {
    // request 拦截器吐过 toast
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
          {
            size: 'small',
            onClick: () => onToggle(row),
          },
          () => (row.status === 1 ? '禁用' : '启用'),
        ),
        h(
          NPopconfirm,
          {
            onPositiveClick: () => onDelete(row),
          },
          {
            default: () => `确认删除 "${row.app_name}"？删除后该 Key 立即失效。`,
            trigger: () =>
              h(
                NButton,
                { size: 'small', type: 'error' },
                () => '删除',
              ),
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

onMounted(load);
</script>

<template>
  <div class="p-6">
    <NCard title="API 密钥">
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
