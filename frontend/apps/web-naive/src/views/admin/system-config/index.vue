<script lang="ts" setup>
import type { DataTableColumns } from 'naive-ui';

import { computed, h, onMounted, reactive, ref } from 'vue';

import {
  NAlert,
  NButton,
  NCard,
  NDataTable,
  NForm,
  NFormItem,
  NInput,
  NModal,
  NSpace,
  NTag,
  useMessage,
} from 'naive-ui';

import {
  type AdminSystemConfigApi,
  listSystemConfigsApi,
  updateSystemConfigApi,
} from '#/api/admin';

const message = useMessage();

// 保留键：通用入口无权更新，前端主动拦截，与后端 RESERVED 规则一致
const RESERVED_KEYS = new Set([
  'payment_min_amount',
  'payment_max_amount',
  'doc_config',
]);
const RESERVED_PREFIXES = ['epay_', 'collect_'];

function isReserved(key: string): boolean {
  if (RESERVED_KEYS.has(key)) return true;
  return RESERVED_PREFIXES.some((p) => key.startsWith(p));
}

const loading = ref(false);
const rows = ref<AdminSystemConfigApi.ConfigItem[]>([]);

async function load() {
  loading.value = true;
  try {
    const data = await listSystemConfigsApi();
    rows.value = Array.isArray(data) ? data : [];
  } catch {
    message.error('系统配置加载失败');
  } finally {
    loading.value = false;
  }
}

// ========= 编辑 =========
const editorVisible = ref(false);
const editing = ref<AdminSystemConfigApi.ConfigItem | null>(null);
const form = reactive<{
  config_key: string;
  config_value: string;
  value_type: string;
  description: string;
}>({
  config_key: '',
  config_value: '',
  value_type: 'string',
  description: '',
});
const saving = ref(false);

const canEdit = computed(() => !isReserved(form.config_key));

function openEdit(row: AdminSystemConfigApi.ConfigItem) {
  editing.value = row;
  Object.assign(form, {
    config_key: row.config_key,
    config_value: row.config_value ?? '',
    value_type: row.value_type ?? 'string',
    description: row.description ?? '',
  });
  editorVisible.value = true;
}

async function onSave() {
  if (!canEdit.value) {
    message.warning('该键为保留配置，请使用专用入口（如收款/采集/文档配置）');
    return;
  }
  // value_type 为 json/number/boolean 时，前端先做一次宽容校验
  if (form.value_type === 'json') {
    try {
      JSON.parse(form.config_value || 'null');
    } catch {
      message.warning('值不是合法 JSON');
      return;
    }
  } else if (form.value_type === 'number' && form.config_value !== '') {
    if (Number.isNaN(Number(form.config_value))) {
      message.warning('值不是合法数字');
      return;
    }
  } else if (
    form.value_type === 'boolean' &&
    !['0', '1', 'false', 'true'].includes(form.config_value)
  ) {
    message.warning('布尔值请填 0/1 或 true/false');
    return;
  }
  saving.value = true;
  try {
    await updateSystemConfigApi(form.config_key, form.config_value);
    message.success('保存成功');
    editorVisible.value = false;
    load();
  } catch {
    // interceptor
  } finally {
    saving.value = false;
  }
}

const columns: DataTableColumns<AdminSystemConfigApi.ConfigItem> = [
  { title: 'ID', key: 'id', width: 70 },
  {
    title: '键',
    key: 'config_key',
    width: 240,
    render: (row) =>
      h(NSpace, { size: 4, align: 'center' }, () => [
        h('span', row.config_key),
        isReserved(row.config_key)
          ? h(NTag, { size: 'small', type: 'warning' }, () => '保留')
          : null,
        row.is_sensitive === 1
          ? h(NTag, { size: 'small', type: 'error' }, () => '敏感')
          : null,
      ]),
  },
  { title: '分组', key: 'group_name', width: 120 },
  {
    title: '类型',
    key: 'value_type',
    width: 100,
    render: (row) =>
      h(NTag, { size: 'small', type: 'info' }, () => row.value_type ?? '-'),
  },
  {
    title: '值',
    key: 'config_value',
    ellipsis: { tooltip: true },
    render: (row) => row.config_value ?? '',
  },
  { title: '描述', key: 'description', ellipsis: { tooltip: true } },
  { title: '更新时间', key: 'updated_at', width: 170 },
  {
    title: '操作',
    key: 'actions',
    width: 100,
    fixed: 'right',
    render: (row) =>
      h(
        NButton,
        {
          size: 'small',
          type: 'primary',
          disabled: isReserved(row.config_key),
          onClick: () => openEdit(row),
        },
        () => (isReserved(row.config_key) ? '保留' : '编辑'),
      ),
  },
];

onMounted(load);
</script>

<template>
  <div class="p-6">
    <NCard title="系统配置">
      <template #header-extra>
        <NButton @click="load">刷新</NButton>
      </template>

      <NAlert type="warning" class="mb-4">
        敏感键（epay_*、doc_config.api_key 等）在接口响应中已脱敏为 ****。
        保留配置（epay_*、collect_*、payment_min/max_amount、doc_config）需走专用入口修改，此处通用更新会被后端 40003 拦下。
      </NAlert>

      <NDataTable
        :loading="loading"
        :columns="columns"
        :data="rows"
        :row-key="(row: AdminSystemConfigApi.ConfigItem) => row.id"
        :scroll-x="1200"
      />
    </NCard>

    <NModal
      v-model:show="editorVisible"
      preset="card"
      title="编辑配置"
      style="width: 600px"
      :mask-closable="false"
    >
      <NForm label-placement="left" label-width="auto">
        <NFormItem label="键">
          <NInput v-model:value="form.config_key" disabled />
        </NFormItem>
        <NFormItem label="类型">
          <NInput v-model:value="form.value_type" disabled />
        </NFormItem>
        <NFormItem label="描述">
          <NInput v-model:value="form.description" disabled />
        </NFormItem>
        <NFormItem label="值">
          <NInput
            v-model:value="form.config_value"
            type="textarea"
            :autosize="{ minRows: 3, maxRows: 10 }"
            :placeholder="
              form.value_type === 'json'
                ? '合法 JSON'
                : form.value_type === 'number'
                  ? '数字'
                  : form.value_type === 'boolean'
                    ? '0/1 或 true/false'
                    : '纯文本'
            "
          />
        </NFormItem>
      </NForm>
      <template #footer>
        <NSpace justify="end">
          <NButton @click="editorVisible = false">取消</NButton>
          <NButton
            type="primary"
            :disabled="!canEdit"
            :loading="saving"
            @click="onSave"
          >
            保存
          </NButton>
        </NSpace>
      </template>
    </NModal>
  </div>
</template>
