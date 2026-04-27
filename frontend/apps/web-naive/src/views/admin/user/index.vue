<script lang="ts" setup>
// 管理端 · 用户管理。docs/07 §3.2.2。
// 列表：+ 状态 switch / 脱敏显示 / 余额 & 套餐列
// 详情 Drawer 4 Tab：基本 · 角色 · 套餐/额度 · 安全
// 危险操作：重置密码 · 强制下线 · 调整余额 · 赠送套餐
import type { DataTableColumns } from 'naive-ui';

import { h, onMounted, reactive, ref } from 'vue';

import {
  NButton,
  NCard,
  NDataTable,
  NDescriptions,
  NDescriptionsItem,
  NDrawer,
  NDrawerContent,
  NForm,
  NFormItem,
  NInput,
  NInputGroup,
  NInputNumber,
  NModal,
  NPopconfirm,
  NSelect,
  NSpace,
  NSwitch,
  NTabs,
  NTabPane,
  NTag,
  useMessage,
} from 'naive-ui';

import {
  adjustUserBalanceApi,
  type AdminUserApi,
  assignUserRolesApi,
  createUserApi,
  deleteUserApi,
  forceOfflineUserApi,
  giftUserPlanApi,
  listRolesApi,
  listUsersApi,
  resetUserPasswordApi,
  toggleUserStatusApi,
  updateUserApi,
} from '#/api/admin';
import { listAdminPlansApi } from '#/api/admin';
import { maskEmail, maskMobile } from '#/utils/mask';

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

const roleOptions = ref<{ label: string; value: number }[]>([]);
const planOptions = ref<{ label: string; value: number }[]>([]);

async function loadRoles() {
  try {
    const data = await listRolesApi({ page: 1, page_size: 100 });
    roleOptions.value = (data.list ?? [])
      .filter((r) => r.status === 1)
      .map((r) => ({ label: `${r.name}（${r.code}）`, value: r.id }));
  } catch {
    // ignore
  }
}
async function loadPlans() {
  try {
    const data = await listAdminPlansApi({ page: 1, page_size: 100 });
    planOptions.value = (data.list ?? []).map((p: any) => ({
      label: `${p.name} · ¥${p.price}`,
      value: p.id,
    }));
  } catch {
    // ignore
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

// ========== 详情 Drawer（4 Tab） ==========
const drawerVisible = ref(false);
const drawerRow = ref<AdminUserApi.User | null>(null);
const drawerTab = ref<'basic' | 'role' | 'plan' | 'security'>('basic');
const drawerRoles = ref<number[]>([]);
const drawerPlanId = ref<number | null>(null);
const drawerPlanDuration = ref<number>(30);
const drawerBalance = ref<number>(0);
const drawerRemark = ref<string>('');
const drawerNewPassword = ref<string>('');

function openDrawer(row: AdminUserApi.User) {
  drawerRow.value = row;
  drawerRoles.value = (row.roles ?? []).map((r) => r.id);
  drawerPlanId.value = row.plan_id ?? null;
  drawerPlanDuration.value = 30;
  drawerBalance.value = 0;
  drawerRemark.value = '';
  drawerNewPassword.value = '';
  drawerTab.value = 'basic';
  drawerVisible.value = true;
}

async function onDrawerAssignRoles() {
  if (!drawerRow.value) return;
  try {
    await assignUserRolesApi(drawerRow.value.id, drawerRoles.value);
    message.success('角色已更新（用户 token 立即失效）');
    drawerVisible.value = false;
    load();
  } catch {
    // interceptor
  }
}

async function onDrawerAdjustBalance() {
  if (!drawerRow.value || drawerBalance.value === 0) {
    message.warning('金额不能为 0');
    return;
  }
  if (!drawerRemark.value.trim()) {
    message.warning('请填写备注');
    return;
  }
  try {
    await adjustUserBalanceApi(
      drawerRow.value.id,
      drawerBalance.value,
      drawerRemark.value,
    );
    message.success('余额已调整');
    load();
  } catch {
    // interceptor
  }
}

async function onDrawerGiftPlan() {
  if (!drawerRow.value || !drawerPlanId.value) {
    message.warning('请选择套餐');
    return;
  }
  try {
    await giftUserPlanApi(
      drawerRow.value.id,
      drawerPlanId.value,
      drawerPlanDuration.value,
    );
    message.success('套餐赠送成功');
    load();
  } catch {
    // interceptor
  }
}

async function onDrawerResetPassword() {
  if (!drawerRow.value) return;
  if (drawerNewPassword.value.length < 6) {
    message.warning('新密码至少 6 位');
    return;
  }
  try {
    await resetUserPasswordApi(drawerRow.value.id, drawerNewPassword.value);
    message.success('密码已重置，用户下次登录需使用新密码');
    drawerNewPassword.value = '';
  } catch {
    // interceptor
  }
}

async function onDrawerForceOffline() {
  if (!drawerRow.value) return;
  try {
    await forceOfflineUserApi(drawerRow.value.id);
    message.success('用户 token 已全部失效');
  } catch {
    // interceptor
  }
}

const columns: DataTableColumns<AdminUserApi.User> = [
  { title: 'ID', key: 'id', width: 70 },
  { title: '用户名', key: 'username', width: 140, ellipsis: { tooltip: true } },
  { title: '昵称', key: 'nickname', width: 120, ellipsis: { tooltip: true } },
  {
    title: '手机',
    key: 'mobile',
    width: 130,
    render: (r) => maskMobile(r.mobile) || '-',
  },
  {
    title: '邮箱',
    key: 'email',
    width: 180,
    render: (r) => maskEmail(r.email) || '-',
  },
  {
    title: '余额',
    key: 'balance',
    width: 100,
    render: (r) => `¥${r.balance ?? '0.00'}`,
  },
  {
    title: '角色',
    key: 'roles',
    width: 180,
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
    width: 90,
    render: (row) =>
      h(NSwitch, {
        size: 'small',
        value: row.status === 1,
        onUpdateValue: () => onToggleStatus(row),
      }),
  },
  { title: '创建时间', key: 'created_at', width: 170 },
  {
    title: '操作',
    key: 'actions',
    width: 220,
    fixed: 'right',
    render: (row) =>
      h(NSpace, { size: 'small' }, () => [
        h(
          NButton,
          {
            size: 'small',
            quaternary: true,
            type: 'primary',
            onClick: () => openDrawer(row),
          },
          () => '详情',
        ),
        h(
          NButton,
          {
            size: 'small',
            quaternary: true,
            onClick: () => openEdit(row),
          },
          () => '编辑',
        ),
        h(
          NPopconfirm,
          { onPositiveClick: () => onDelete(row) },
          {
            default: () => '删除不可恢复，关联钱包/API Key/采集账号会一并清理',
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

onMounted(() => {
  loadRoles();
  loadPlans();
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
        :scroll-x="1500"
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

    <!-- 编辑/新增 Modal -->
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

    <!-- 详情 Drawer 4 Tab -->
    <NDrawer v-model:show="drawerVisible" :width="640" placement="right">
      <NDrawerContent
        :title="`用户详情 · ${drawerRow?.username ?? ''}`"
        :native-scrollbar="false"
        closable
      >
        <NTabs v-model:value="drawerTab" type="line">
          <!-- Tab 1 · 基本 -->
          <NTabPane name="basic" tab="基本">
            <NDescriptions
              v-if="drawerRow"
              :column="1"
              label-placement="left"
              bordered
            >
              <NDescriptionsItem label="用户 ID">
                {{ drawerRow.id }}
              </NDescriptionsItem>
              <NDescriptionsItem label="用户名">
                {{ drawerRow.username }}
              </NDescriptionsItem>
              <NDescriptionsItem label="昵称">
                {{ drawerRow.nickname ?? '-' }}
              </NDescriptionsItem>
              <NDescriptionsItem label="手机">
                {{ maskMobile(drawerRow.mobile) || '-' }}
              </NDescriptionsItem>
              <NDescriptionsItem label="邮箱">
                {{ maskEmail(drawerRow.email) || '-' }}
              </NDescriptionsItem>
              <NDescriptionsItem label="状态">
                <NTag :type="drawerRow.status === 1 ? 'success' : 'error'" size="small">
                  {{ drawerRow.status === 1 ? '启用' : '禁用' }}
                </NTag>
              </NDescriptionsItem>
              <NDescriptionsItem label="创建时间">
                {{ drawerRow.created_at }}
              </NDescriptionsItem>
            </NDescriptions>
          </NTabPane>

          <!-- Tab 2 · 角色 -->
          <NTabPane name="role" tab="角色">
            <p class="text-xs text-muted-foreground mb-3">
              修改后用户 token 立即失效，下次请求需重登。
            </p>
            <NSelect
              v-model:value="drawerRoles"
              multiple
              :options="roleOptions"
            />
            <div class="mt-4">
              <NButton type="primary" @click="onDrawerAssignRoles">
                保存角色
              </NButton>
            </div>
          </NTabPane>

          <!-- Tab 3 · 套餐 & 额度 -->
          <NTabPane name="plan" tab="套餐 & 额度">
            <NForm label-placement="left" label-width="auto">
              <NFormItem label="当前套餐">
                <span class="text-sm">
                  {{ drawerRow?.plan_id ? `套餐 #${drawerRow.plan_id}` : '无' }}
                  <span v-if="drawerRow?.plan_expire_at" class="ml-2 text-xs text-muted-foreground">
                    到期：{{ drawerRow.plan_expire_at }}
                  </span>
                </span>
              </NFormItem>
              <NFormItem label="余额">
                <span class="text-sm">¥{{ drawerRow?.balance ?? '0.00' }}</span>
              </NFormItem>
            </NForm>

            <NCard title="赠送套餐" size="small" class="mt-3">
              <NForm label-placement="left" label-width="auto">
                <NFormItem label="套餐">
                  <NSelect v-model:value="drawerPlanId" :options="planOptions" />
                </NFormItem>
                <NFormItem label="时长 (天)">
                  <NInputNumber v-model:value="drawerPlanDuration" :min="1" />
                </NFormItem>
              </NForm>
              <NButton type="primary" @click="onDrawerGiftPlan">
                赠送
              </NButton>
            </NCard>

            <NCard title="调整余额" size="small" class="mt-3">
              <NForm label-placement="left" label-width="auto">
                <NFormItem label="金额">
                  <NInputNumber
                    v-model:value="drawerBalance"
                    :precision="2"
                    :step="1"
                    placeholder="正数增加、负数扣减"
                  />
                </NFormItem>
                <NFormItem label="备注">
                  <NInput v-model:value="drawerRemark" placeholder="必填" />
                </NFormItem>
              </NForm>
              <NPopconfirm @positive-click="onDrawerAdjustBalance">
                <template #trigger>
                  <NButton type="warning">提交调整</NButton>
                </template>
                确认对用户 {{ drawerRow?.username }} 调整 ¥{{ drawerBalance }}？
              </NPopconfirm>
            </NCard>
          </NTabPane>

          <!-- Tab 4 · 安全 -->
          <NTabPane name="security" tab="安全">
            <NCard title="重置密码" size="small" class="mb-3">
              <NForm label-placement="left" label-width="auto">
                <NFormItem label="新密码">
                  <NInput
                    v-model:value="drawerNewPassword"
                    type="password"
                    show-password-on="click"
                    placeholder="至少 6 位"
                  />
                </NFormItem>
              </NForm>
              <NPopconfirm @positive-click="onDrawerResetPassword">
                <template #trigger>
                  <NButton type="warning">重置密码</NButton>
                </template>
                确认重置 {{ drawerRow?.username }} 的密码？原密码立即失效。
              </NPopconfirm>
            </NCard>

            <NCard title="强制下线" size="small">
              <p class="text-sm text-muted-foreground mb-3">
                吊销该用户所有 token。正在使用的设备将在下次请求时被踢回登录页。
              </p>
              <NPopconfirm @positive-click="onDrawerForceOffline">
                <template #trigger>
                  <NButton type="error">强制下线</NButton>
                </template>
                确认强制下线 {{ drawerRow?.username }}？
              </NPopconfirm>
            </NCard>
          </NTabPane>
        </NTabs>
      </NDrawerContent>
    </NDrawer>
  </div>
</template>
