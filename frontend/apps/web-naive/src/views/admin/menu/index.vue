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
  NInputNumber,
  NModal,
  NPopconfirm,
  NSelect,
  NSpace,
  NTag,
  useMessage,
} from 'naive-ui';

import {
  type AdminMenuApi,
  createMenuApi,
  deleteMenuApi,
  listAdminMenusApi,
  updateMenuApi,
} from '#/api/admin';

const message = useMessage();

const loading = ref(false);
const rows = ref<AdminMenuApi.Menu[]>([]);
const total = ref(0);
const page = ref(1);
const pageSize = ref(20);

const filter = reactive<{ keyword: string }>({ keyword: '' });

async function load() {
  loading.value = true;
  try {
    const data = await listAdminMenusApi({
      keyword: filter.keyword || undefined,
      page: page.value,
      page_size: pageSize.value,
    });
    rows.value = data.list ?? [];
    total.value = data.total ?? 0;
  } catch {
    message.error('菜单列表加载失败');
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
const editing = ref<AdminMenuApi.Menu | null>(null);
const form = reactive<{
  id?: number;
  parent_id: number;
  name: string;
  path: string;
  permission_code: string;
  icon: string;
  component: string;
  sort: number;
  status: number;
}>({
  parent_id: 0,
  name: '',
  path: '',
  permission_code: '',
  icon: '',
  component: '',
  sort: 0,
  status: 1,
});
const saving = ref(false);

function openCreate() {
  editing.value = null;
  Object.assign(form, {
    id: undefined,
    parent_id: 0,
    name: '',
    path: '',
    permission_code: '',
    icon: '',
    component: '',
    sort: 0,
    status: 1,
  });
  editorVisible.value = true;
}

function openEdit(row: AdminMenuApi.Menu) {
  editing.value = row;
  Object.assign(form, {
    id: row.id,
    parent_id: row.parent_id ?? 0,
    name: row.name ?? '',
    path: row.path ?? '',
    permission_code: row.permission_code ?? '',
    icon: row.icon ?? '',
    component: row.component ?? '',
    sort: row.sort ?? 0,
    status: row.status ?? 1,
  });
  editorVisible.value = true;
}

async function onSave() {
  if (!form.name.trim() || !form.path.trim() || !form.permission_code.trim()) {
    message.warning('名称、路径、权限码不能为空');
    return;
  }
  saving.value = true;
  try {
    const payload = {
      parent_id: form.parent_id,
      name: form.name,
      path: form.path,
      permission_code: form.permission_code,
      icon: form.icon,
      component: form.component,
      sort: form.sort,
      status: form.status,
    };
    if (editing.value && form.id) {
      await updateMenuApi({ id: form.id, ...payload });
      message.success('更新成功');
    } else {
      await createMenuApi(payload);
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

async function onDelete(row: AdminMenuApi.Menu) {
  try {
    await deleteMenuApi(row.id);
    message.success('删除成功');
    load();
  } catch {
    // interceptor
  }
}

const columns: DataTableColumns<AdminMenuApi.Menu> = [
  { title: 'ID', key: 'id', width: 70 },
  { title: '父ID', key: 'parent_id', width: 70 },
  { title: '名称', key: 'name', width: 140 },
  { title: '路径', key: 'path', width: 200, ellipsis: { tooltip: true } },
  {
    title: '权限码',
    key: 'permission_code',
    width: 180,
    ellipsis: { tooltip: true },
  },
  { title: '组件', key: 'component', ellipsis: { tooltip: true } },
  { title: '排序', key: 'sort', width: 70 },
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
            default: () => '删除后依赖该菜单的用户导航会断链，请确认无引用',
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
    <NCard title="菜单管理">
      <template #header-extra>
        <NButton type="primary" @click="openCreate">新增菜单</NButton>
      </template>

      <NSpace class="mb-4">
        <NInputGroup>
          <NInput
            v-model:value="filter.keyword"
            placeholder="名称/路径/权限码关键词"
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
        :row-key="(row: AdminMenuApi.Menu) => row.id"
        :scroll-x="1400"
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
      :title="editing ? '编辑菜单' : '新增菜单'"
      style="width: 640px"
      :mask-closable="false"
    >
      <NForm label-placement="left" label-width="auto">
        <NFormItem label="父菜单ID">
          <NInputNumber v-model:value="form.parent_id" :min="0" />
        </NFormItem>
        <NFormItem label="名称" required>
          <NInput v-model:value="form.name" />
        </NFormItem>
        <NFormItem label="路径" required>
          <NInput v-model:value="form.path" placeholder="如 /admin/question" />
        </NFormItem>
        <NFormItem label="权限码" required>
          <NInput
            v-model:value="form.permission_code"
            placeholder="如 admin.question.manage"
          />
        </NFormItem>
        <NFormItem label="组件">
          <NInput
            v-model:value="form.component"
            placeholder="如 /admin/question/index（相对 views 目录）"
          />
        </NFormItem>
        <NFormItem label="图标">
          <NInput v-model:value="form.icon" placeholder="icon 名或空" />
        </NFormItem>
        <NFormItem label="排序">
          <NInputNumber v-model:value="form.sort" :min="0" />
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
