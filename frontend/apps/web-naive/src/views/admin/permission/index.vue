<script lang="ts" setup>
import type { DataTableColumns } from 'naive-ui';

import { h, onMounted, reactive, ref } from 'vue';

import {
  NButton,
  NCard,
  NDataTable,
  NForm,
  NFormItem,
  NInput,
  NInputGroup,
  NModal,
  NPopconfirm,
  NSelect,
  NSpace,
  NTag,
  useMessage,
} from 'naive-ui';

import {
  type AdminPermissionApi,
  createPermissionApi,
  deletePermissionApi,
  listAdminPermissionsApi,
  updatePermissionApi,
} from '#/api/admin';

const message = useMessage();

const loading = ref(false);
const rows = ref<AdminPermissionApi.Permission[]>([]);
const total = ref(0);
const page = ref(1);
const pageSize = ref(20);

const filter = reactive<{ keyword: string }>({ keyword: '' });

const typeOptions = [
  { label: 'action', value: 'action' },
  { label: 'menu', value: 'menu' },
  { label: 'data', value: 'data' },
];

async function load() {
  loading.value = true;
  try {
    const data = await listAdminPermissionsApi({
      keyword: filter.keyword || undefined,
      page: page.value,
      page_size: pageSize.value,
    });
    rows.value = data.list ?? [];
    total.value = data.total ?? 0;
  } catch {
    message.error('权限列表加载失败');
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

// ========= 编辑 =========
const editorVisible = ref(false);
const editing = ref<AdminPermissionApi.Permission | null>(null);
const form = reactive<{
  id?: number;
  name: string;
  code: string;
  type: string;
  description: string;
  status: number;
}>({
  name: '',
  code: '',
  type: 'action',
  description: '',
  status: 1,
});
const saving = ref(false);

function openCreate() {
  editing.value = null;
  Object.assign(form, {
    id: undefined,
    name: '',
    code: '',
    type: 'action',
    description: '',
    status: 1,
  });
  editorVisible.value = true;
}

function openEdit(row: AdminPermissionApi.Permission) {
  editing.value = row;
  Object.assign(form, {
    id: row.id,
    name: row.name ?? '',
    code: row.code ?? '',
    type: row.type ?? 'action',
    description: row.description ?? '',
    status: row.status ?? 1,
  });
  editorVisible.value = true;
}

async function onSave() {
  if (!form.name.trim() || !form.code.trim()) {
    message.warning('名称和编码不能为空');
    return;
  }
  saving.value = true;
  try {
    const payload = {
      name: form.name,
      code: form.code,
      type: form.type,
      description: form.description,
      status: form.status,
    };
    if (editing.value && form.id) {
      await updatePermissionApi({ id: form.id, ...payload });
      message.success('更新成功');
    } else {
      await createPermissionApi(payload);
      message.success('创建成功');
    }
    editorVisible.value = false;
    load();
  } catch {
    // interceptor
  } finally {
    saving.value = false;
  }
}

async function onDelete(row: AdminPermissionApi.Permission) {
  try {
    await deletePermissionApi(row.id);
    message.success('删除成功');
    load();
  } catch {
    // interceptor
  }
}

const columns: DataTableColumns<AdminPermissionApi.Permission> = [
  { title: 'ID', key: 'id', width: 70 },
  { title: '名称', key: 'name', width: 180 },
  { title: '编码', key: 'code', width: 200 },
  {
    title: '类型',
    key: 'type',
    width: 90,
    render: (row) =>
      h(NTag, { size: 'small', type: 'info' }, () => row.type ?? '-'),
  },
  { title: '描述', key: 'description', ellipsis: { tooltip: true } },
  {
    title: '状态',
    key: 'status',
    width: 80,
    render: (row) =>
      row.status === 1
        ? h(NTag, { type: 'success', size: 'small' }, () => '启用')
        : h(NTag, { size: 'small' }, () => '禁用'),
  },
  {
    title: '操作',
    key: 'actions',
    width: 160,
    fixed: 'right',
    render: (row) =>
      h(NSpace, { size: 'small' }, () => [
        h(
          NButton,
          { size: 'small', type: 'primary', onClick: () => openEdit(row) },
          () => '编辑',
        ),
        h(
          NPopconfirm,
          { onPositiveClick: () => onDelete(row) },
          {
            default: () => '删除后引用该权限码的角色将失去对应能力',
            trigger: () =>
              h(NButton, { size: 'small', type: 'error' }, () => '删除'),
          },
        ),
      ]),
  },
];

onMounted(load);
</script>

<template>
  <div class="p-6">
    <NCard title="权限管理">
      <template #header-extra>
        <NButton type="primary" @click="openCreate">新增权限</NButton>
      </template>

      <NSpace class="mb-4">
        <NInputGroup>
          <NInput
            v-model:value="filter.keyword"
            placeholder="名称/编码关键词"
            clearable
            style="width: 260px"
            @keydown.enter="onSearch"
          />
          <NButton type="primary" @click="onSearch">搜索</NButton>
        </NInputGroup>
        <NButton @click="onReset">重置</NButton>
      </NSpace>

      <NDataTable
        remote
        :loading="loading"
        :columns="columns"
        :data="rows"
        :row-key="(row: AdminPermissionApi.Permission) => row.id"
        :scroll-x="1100"
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

    <NModal
      v-model:show="editorVisible"
      preset="card"
      :title="editing ? '编辑权限' : '新增权限'"
      style="width: 560px"
      :mask-closable="false"
    >
      <NForm label-placement="left" label-width="auto">
        <NFormItem label="名称" required>
          <NInput v-model:value="form.name" />
        </NFormItem>
        <NFormItem label="编码" required>
          <NInput
            v-model:value="form.code"
            :disabled="!!editing"
            placeholder="如 admin.question.manage"
          />
        </NFormItem>
        <NFormItem label="类型">
          <NSelect
            v-model:value="form.type"
            :options="typeOptions"
            style="width: 160px"
          />
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
        <NFormItem label="描述">
          <NInput
            v-model:value="form.description"
            type="textarea"
            :autosize="{ minRows: 2, maxRows: 5 }"
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
