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
  type AdminRoleApi,
  assignRolePermissionsApi,
  createRoleApi,
  deleteRoleApi,
  listPermissionsApi,
  listRolesApi,
  updateRoleApi,
} from '#/api/admin';

const message = useMessage();

const loading = ref(false);
const rows = ref<AdminRoleApi.Role[]>([]);
const total = ref(0);
const page = ref(1);
const pageSize = ref(20);

const filter = reactive<{ keyword: string; status: '' | number }>({
  keyword: '',
  status: '',
});
const statusOptions = [
  { label: '全部', value: '' },
  { label: '启用', value: 1 },
  { label: '禁用', value: 0 },
];

// 权限候选（用于权限分配）
const permissionOptions = ref<{ label: string; value: number }[]>([]);
async function loadPermissions() {
  try {
    const data = await listPermissionsApi({ page: 1, page_size: 200 });
    permissionOptions.value = (data.list ?? []).map((p) => ({
      label: `${p.name}（${p.code}）`,
      value: p.id,
    }));
  } catch {
    // 忽略，后续打开模态框时再提示
  }
}

async function load() {
  loading.value = true;
  try {
    const data = await listRolesApi({
      keyword: filter.keyword || undefined,
      status: filter.status === '' ? undefined : filter.status,
      page: page.value,
      page_size: pageSize.value,
    });
    rows.value = data.list ?? [];
    total.value = data.total ?? 0;
  } catch {
    message.error('角色列表加载失败');
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

// ========== 新增/编辑 ==========
const editorVisible = ref(false);
const editing = ref<AdminRoleApi.Role | null>(null);
const form = reactive<{
  id?: number;
  name: string;
  code: string;
  sort: number;
  status: number;
}>({ name: '', code: '', sort: 0, status: 1 });
const saving = ref(false);

function openCreate() {
  editing.value = null;
  Object.assign(form, {
    id: undefined,
    name: '',
    code: '',
    sort: 0,
    status: 1,
  });
  editorVisible.value = true;
}

function openEdit(row: AdminRoleApi.Role) {
  editing.value = row;
  Object.assign(form, {
    id: row.id,
    name: row.name ?? '',
    code: row.code ?? '',
    sort: row.sort ?? 0,
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
    if (editing.value && form.id) {
      await updateRoleApi({
        id: form.id,
        name: form.name,
        code: form.code,
        sort: form.sort,
        status: form.status,
      });
      message.success('更新成功');
    } else {
      await createRoleApi({
        name: form.name,
        code: form.code,
        sort: form.sort,
        status: form.status,
      });
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

async function onDelete(row: AdminRoleApi.Role) {
  try {
    await deleteRoleApi(row.id);
    message.success('删除成功（关联用户已自动解绑）');
    load();
  } catch {
    // interceptor
  }
}

// ========== 权限分配 ==========
const permModal = ref(false);
const permTarget = ref<AdminRoleApi.Role | null>(null);
const permSelected = ref<number[]>([]);
const permSaving = ref(false);

function openPermModal(row: AdminRoleApi.Role) {
  permTarget.value = row;
  permSelected.value = (row.permissions ?? []).map((p) => p.id);
  permModal.value = true;
}

async function onPermSave() {
  if (!permTarget.value) return;
  permSaving.value = true;
  try {
    await assignRolePermissionsApi(permTarget.value.id, permSelected.value);
    message.success('权限已更新（关联用户 token 立即失效）');
    permModal.value = false;
    load();
  } catch {
    // interceptor
  } finally {
    permSaving.value = false;
  }
}

const columns: DataTableColumns<AdminRoleApi.Role> = [
  { title: 'ID', key: 'id', width: 70 },
  { title: '名称', key: 'name', width: 140 },
  { title: '编码', key: 'code', width: 160 },
  { title: '排序', key: 'sort', width: 70 },
  {
    title: '状态',
    key: 'status',
    width: 80,
    render: (row) =>
      row.status === 1
        ? h(NTag, { type: 'success', size: 'small' }, () => '启用')
        : h(NTag, { type: 'error', size: 'small' }, () => '禁用'),
  },
  {
    title: '权限',
    key: 'permissions',
    render: (row) =>
      h(
        NSpace,
        { size: [4, 4] },
        () =>
          row.permissions?.map((p) =>
            h(NTag, { size: 'small', type: 'info' }, () => p.name),
          ) ?? [h('span', { class: 'text-muted-foreground' }, '—')],
      ),
  },
  { title: '更新时间', key: 'updated_at', width: 170 },
  {
    title: '操作',
    key: 'actions',
    width: 240,
    fixed: 'right',
    render: (row) =>
      h(NSpace, { size: 'small' }, () => [
        h(
          NButton,
          { size: 'small', type: 'primary', onClick: () => openEdit(row) },
          () => '编辑',
        ),
        h(
          NButton,
          { size: 'small', onClick: () => openPermModal(row) },
          () => '权限',
        ),
        h(
          NPopconfirm,
          { onPositiveClick: () => onDelete(row) },
          {
            default: () =>
              '删除会清空 role_permission/user_role 关联，请谨慎',
            trigger: () =>
              h(NButton, { size: 'small', type: 'error' }, () => '删除'),
          },
        ),
      ]),
  },
];

onMounted(() => {
  loadPermissions();
  load();
});
</script>

<template>
  <div class="p-6">
    <NCard title="角色管理">
      <template #header-extra>
        <NButton type="primary" @click="openCreate">新增角色</NButton>
      </template>

      <NSpace class="mb-4">
        <NInputGroup>
          <NInput
            v-model:value="filter.keyword"
            placeholder="名称/编码"
            clearable
            style="width: 240px"
            @keydown.enter="onSearch"
          />
          <NButton type="primary" @click="onSearch">搜索</NButton>
        </NInputGroup>
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
        :row-key="(row: AdminRoleApi.Role) => row.id"
        :scroll-x="1200"
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

    <!-- 编辑/新增 -->
    <NModal
      v-model:show="editorVisible"
      preset="card"
      :title="editing ? '编辑角色' : '新增角色'"
      style="width: 460px"
      :mask-closable="false"
    >
      <NForm label-placement="left" label-width="auto">
        <NFormItem label="名称" required>
          <NInput v-model:value="form.name" />
        </NFormItem>
        <NFormItem label="编码" required>
          <NInput v-model:value="form.code" :disabled="!!editing" />
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

    <!-- 权限分配 -->
    <NModal
      v-model:show="permModal"
      preset="card"
      title="分配权限"
      style="width: 560px"
      :mask-closable="false"
    >
      <div class="mb-3 text-sm text-muted-foreground">
        目标角色：{{ permTarget?.name }}（{{ permTarget?.code }}）·
        保存后该角色下所有用户 token 立即失效
      </div>
      <NSelect
        v-model:value="permSelected"
        multiple
        :options="permissionOptions"
        filterable
      />
      <template #footer>
        <NSpace justify="end">
          <NButton @click="permModal = false">取消</NButton>
          <NButton type="primary" :loading="permSaving" @click="onPermSave">
            保存
          </NButton>
        </NSpace>
      </template>
    </NModal>
  </div>
</template>
