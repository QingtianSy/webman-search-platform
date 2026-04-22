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
  type AdminUserApi,
  assignUserRolesApi,
  createUserApi,
  deleteUserApi,
  listRolesApi,
  listUsersApi,
  toggleUserStatusApi,
  updateUserApi,
} from '#/api/admin';

const message = useMessage();

const loading = ref(false);
const rows = ref<AdminUserApi.User[]>([]);
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

// 角色候选（用于新增/分配角色下拉）
const roleOptions = ref<{ label: string; value: number }[]>([]);
async function loadRoles() {
  try {
    const data = await listRolesApi({ page: 1, page_size: 100 });
    roleOptions.value = (data.list ?? [])
      .filter((r) => r.status === 1)
      .map((r) => ({ label: `${r.name}（${r.code}）`, value: r.id }));
  } catch {
    // 忽略，编辑时再提示
  }
}

async function load() {
  loading.value = true;
  try {
    const data = await listUsersApi({
      keyword: filter.keyword || undefined,
      status: filter.status === '' ? undefined : filter.status,
      page: page.value,
      page_size: pageSize.value,
    });
    rows.value = data.list ?? [];
    total.value = data.total ?? 0;
  } catch {
    message.error('用户列表加载失败');
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
const editing = ref<AdminUserApi.User | null>(null);
const form = reactive<{
  id?: number;
  username: string;
  password: string;
  nickname: string;
  mobile: string;
  email: string;
  status: number;
  role_ids: number[];
}>({
  username: '',
  password: '',
  nickname: '',
  mobile: '',
  email: '',
  status: 1,
  role_ids: [],
});
const saving = ref(false);

function openCreate() {
  editing.value = null;
  Object.assign(form, {
    id: undefined,
    username: '',
    password: '',
    nickname: '',
    mobile: '',
    email: '',
    status: 1,
    role_ids: [],
  });
  editorVisible.value = true;
}

function openEdit(row: AdminUserApi.User) {
  editing.value = row;
  Object.assign(form, {
    id: row.id,
    username: row.username ?? '',
    password: '',
    nickname: row.nickname ?? '',
    mobile: row.mobile ?? '',
    email: row.email ?? '',
    status: row.status ?? 1,
    role_ids: (row.roles ?? []).map((r) => r.id),
  });
  editorVisible.value = true;
}

async function onSave() {
  if (!form.username.trim()) {
    message.warning('用户名不能为空');
    return;
  }
  if (!editing.value && form.password.length < 6) {
    message.warning('新用户密码至少 6 位');
    return;
  }
  if (editing.value && form.password !== '' && form.password.length < 6) {
    message.warning('密码至少 6 位');
    return;
  }
  saving.value = true;
  try {
    if (editing.value && form.id) {
      const payload: AdminUserApi.UpdatePayload = {
        id: form.id,
        username: form.username,
        nickname: form.nickname,
        mobile: form.mobile,
        email: form.email,
        status: form.status,
        role_ids: form.role_ids,
      };
      if (form.password) payload.password = form.password;
      await updateUserApi(payload);
      message.success('更新成功');
    } else {
      await createUserApi({
        username: form.username,
        password: form.password,
        nickname: form.nickname,
        mobile: form.mobile,
        email: form.email,
        status: form.status,
        role_ids: form.role_ids,
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

async function onDelete(row: AdminUserApi.User) {
  try {
    await deleteUserApi(row.id);
    message.success('删除成功');
    load();
  } catch {
    // interceptor
  }
}

async function onToggleStatus(row: AdminUserApi.User) {
  try {
    await toggleUserStatusApi(row.id);
    message.success(row.status === 1 ? '已禁用' : '已启用');
    load();
  } catch {
    // interceptor
  }
}

// ========== 分配角色（快捷入口） ==========
const roleModal = ref(false);
const roleTarget = ref<AdminUserApi.User | null>(null);
const roleSelected = ref<number[]>([]);
const roleSaving = ref(false);

function openRoleModal(row: AdminUserApi.User) {
  roleTarget.value = row;
  roleSelected.value = (row.roles ?? []).map((r) => r.id);
  roleModal.value = true;
}

async function onRoleSave() {
  if (!roleTarget.value) return;
  roleSaving.value = true;
  try {
    await assignUserRolesApi(roleTarget.value.id, roleSelected.value);
    message.success('角色已更新（对应用户 token 会立即失效）');
    roleModal.value = false;
    load();
  } catch {
    // interceptor
  } finally {
    roleSaving.value = false;
  }
}

const columns: DataTableColumns<AdminUserApi.User> = [
  { title: 'ID', key: 'id', width: 70 },
  { title: '用户名', key: 'username', width: 140 },
  { title: '昵称', key: 'nickname', width: 140 },
  { title: '手机', key: 'mobile', width: 130 },
  { title: '邮箱', key: 'email', width: 180, ellipsis: { tooltip: true } },
  {
    title: '角色',
    key: 'roles',
    width: 200,
    render: (row) =>
      h(
        NSpace,
        { size: 'small' },
        () =>
          row.roles?.map((r) =>
            h(NTag, { size: 'small', type: 'info' }, () => r.name),
          ) ?? [h('span', { class: 'text-muted-foreground' }, '—')],
      ),
  },
  {
    title: '状态',
    key: 'status',
    width: 80,
    render: (row) =>
      row.status === 1
        ? h(NTag, { type: 'success', size: 'small' }, () => '启用')
        : h(NTag, { type: 'error', size: 'small' }, () => '禁用'),
  },
  { title: '创建时间', key: 'created_at', width: 170 },
  {
    title: '操作',
    key: 'actions',
    width: 280,
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
          { size: 'small', onClick: () => openRoleModal(row) },
          () => '角色',
        ),
        h(
          NPopconfirm,
          { onPositiveClick: () => onToggleStatus(row) },
          {
            default: () =>
              row.status === 1
                ? '禁用会立即吊销该用户 token'
                : '确定启用该用户？',
            trigger: () =>
              h(
                NButton,
                {
                  size: 'small',
                  type: row.status === 1 ? 'warning' : 'success',
                },
                () => (row.status === 1 ? '禁用' : '启用'),
              ),
          },
        ),
        h(
          NPopconfirm,
          { onPositiveClick: () => onDelete(row) },
          {
            default: () => '删除不可恢复，关联钱包/API Key/采集账号会一并清理',
            trigger: () =>
              h(NButton, { size: 'small', type: 'error' }, () => '删除'),
          },
        ),
      ]),
  },
];

onMounted(() => {
  loadRoles();
  load();
});
</script>

<template>
  <div class="p-6">
    <NCard title="用户管理">
      <template #header-extra>
        <NButton type="primary" @click="openCreate">新增用户</NButton>
      </template>

      <NSpace class="mb-4">
        <NInputGroup>
          <NInput
            v-model:value="filter.keyword"
            placeholder="用户名/昵称/手机/邮箱"
            clearable
            style="width: 260px"
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
        :row-key="(row: AdminUserApi.User) => row.id"
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

    <!-- 编辑/新增 -->
    <NModal
      v-model:show="editorVisible"
      preset="card"
      :title="editing ? '编辑用户' : '新增用户'"
      style="width: 560px"
      :mask-closable="false"
    >
      <NForm label-placement="left" label-width="auto">
        <NFormItem label="用户名" required>
          <NInput v-model:value="form.username" :disabled="!!editing" />
        </NFormItem>
        <NFormItem :label="editing ? '新密码' : '密码'">
          <NInput
            v-model:value="form.password"
            type="password"
            show-password-on="click"
            :placeholder="editing ? '留空则不修改' : '至少 6 位'"
          />
        </NFormItem>
        <NFormItem label="昵称">
          <NInput v-model:value="form.nickname" />
        </NFormItem>
        <NFormItem label="手机">
          <NInput v-model:value="form.mobile" />
        </NFormItem>
        <NFormItem label="邮箱">
          <NInput v-model:value="form.email" />
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
        <NFormItem label="角色">
          <NSelect
            v-model:value="form.role_ids"
            multiple
            :options="roleOptions"
            placeholder="不选则默认 user 角色（仅新建时生效）"
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

    <!-- 角色分配 -->
    <NModal
      v-model:show="roleModal"
      preset="card"
      title="分配角色"
      style="width: 460px"
      :mask-closable="false"
    >
      <div class="mb-3 text-sm text-muted-foreground">
        目标用户：{{ roleTarget?.username }} · 保存后该用户 token 立即失效
      </div>
      <NSelect v-model:value="roleSelected" multiple :options="roleOptions" />
      <template #footer>
        <NSpace justify="end">
          <NButton @click="roleModal = false">取消</NButton>
          <NButton type="primary" :loading="roleSaving" @click="onRoleSave">
            保存
          </NButton>
        </NSpace>
      </template>
    </NModal>
  </div>
</template>
