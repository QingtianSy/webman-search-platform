<script lang="ts" setup>
// 管理端 · 用户管理。docs/07 §3.2.2。
// 列表：+ 状态 switch / 脱敏显示 / 余额 & 套餐列
// 详情 Drawer 4 Tab：基本 · 角色 · 套餐/额度 · 安全
// 危险操作：重置密码 · 强制下线 · 调整余额 · 编辑套餐
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
  listRolesApi,
  listUsersApi,
  resetUserPasswordApi,
  setUserSubscriptionApi,
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
  // 编辑模式下的余额/套餐变更（新增模式不可用）
  balance_delta: number;
  balance_remark: string;
  plan_id: null | number;
}>({
  username: '',
  password: '',
  nickname: '',
  mobile: '',
  email: '',
  status: 1,
  role_ids: [],
  balance_delta: 0,
  balance_remark: '',
  plan_id: null,
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
    balance_delta: 0,
    balance_remark: '',
    plan_id: null,
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
    balance_delta: 0,
    balance_remark: '',
    plan_id: null,
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
  // 编辑模式的余额校验
  if (editing.value && form.balance_delta !== 0 && !form.balance_remark.trim()) {
    message.warning('调整余额必须填写备注');
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
      // 余额 / 套餐走独立接口（各自有业务流水）
      if (form.balance_delta !== 0) {
        await adjustUserBalanceApi(
          form.id,
          form.balance_delta,
          form.balance_remark,
        );
      }
      if (form.plan_id !== null) {
        await setUserSubscriptionApi(form.id, form.plan_id || null);
      }
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
const drawerBalance = ref<number>(0);
const drawerRemark = ref<string>('');
const drawerNewPassword = ref<string>('');

function openDrawer(row: AdminUserApi.User) {
  drawerRow.value = row;
  drawerRoles.value = (row.roles ?? []).map((r) => r.id);
  drawerPlanId.value = null;
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

async function onDrawerSetSubscription() {
  if (!drawerRow.value) return;
  try {
    await setUserSubscriptionApi(drawerRow.value.id, drawerPlanId.value);
    message.success(drawerPlanId.value ? '套餐已设置' : '套餐已清除');
    load();
  } catch {
    // interceptor
  }
}

async function onDrawerClearSubscription() {
  if (!drawerRow.value) return;
  try {
    await setUserSubscriptionApi(drawerRow.value.id, null);
    message.success('套餐已清除');
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
    title: '套餐',
    key: 'subscription_name',
    width: 200,
    render: (r) =>
      h('div', { class: 'flex flex-col' }, [
        h('span', { class: 'text-sm' }, r.subscription_name ?? '-'),
        r.subscription_name
          ? h('span', { class: 'text-xs text-muted-foreground' }, [
              r.subscription_is_unlimited
                ? '不限次'
                : `剩余 ${r.subscription_remain_quota ?? 0} 次`,
              r.subscription_expire_at
                ? ` · 到期：${r.subscription_expire_at}`
                : ' · 永久',
            ])
          : null,
      ]),
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
        :scroll-x="1700"
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

        <!-- 编辑模式：余额调整（走 adjust-balance 接口，带流水） -->
        <template v-if="editing">
          <NFormItem label="当前余额">
            <span class="text-sm">¥{{ editing.balance ?? '0.00' }}</span>
          </NFormItem>
          <NFormItem label="调整金额">
            <NInputNumber
              v-model:value="form.balance_delta"
              :precision="2"
              :step="1"
              placeholder="正数增加、负数扣减，0 表示不调整"
              style="width: 100%"
            />
          </NFormItem>
          <NFormItem
            v-if="form.balance_delta !== 0"
            label="调整备注"
            required
          >
            <NInput v-model:value="form.balance_remark" placeholder="必填" />
          </NFormItem>

          <!-- 编辑模式：套餐变更（走 set-subscription 接口，不吊销 token） -->
          <NFormItem label="当前套餐">
            <span class="text-sm">
              {{ editing.subscription_name ?? '无' }}
              <span
                v-if="editing.subscription_expire_at"
                class="ml-2 text-xs text-muted-foreground"
              >
                到期：{{ editing.subscription_expire_at }}
              </span>
            </span>
          </NFormItem>
          <NFormItem label="变更套餐">
            <NSelect
              v-model:value="form.plan_id"
              :options="[{ label: '无套餐（清除）', value: 0 }, ...planOptions]"
              placeholder="留空则不变更"
              clearable
            />
          </NFormItem>
        </template>
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
                  {{ drawerRow?.subscription_name ?? '无' }}
                  <span v-if="drawerRow?.subscription_name" class="ml-2 text-xs text-muted-foreground">
                    {{ drawerRow?.subscription_is_unlimited ? '不限次' : `剩余 ${drawerRow?.subscription_remain_quota ?? 0} 次` }}
                    {{ drawerRow?.subscription_expire_at ? ` · 到期：${drawerRow.subscription_expire_at}` : ' · 永久' }}
                  </span>
                </span>
              </NFormItem>
              <NFormItem label="余额">
                <span class="text-sm">¥{{ drawerRow?.balance ?? '0.00' }}</span>
              </NFormItem>
            </NForm>

            <NCard title="编辑套餐" size="small" class="mt-3">
              <p class="text-xs text-muted-foreground mb-3">
                设置后立即生效，不会影响用户登录状态。
              </p>
              <NForm label-placement="left" label-width="auto">
                <NFormItem label="套餐">
                  <NSelect v-model:value="drawerPlanId" :options="planOptions" clearable placeholder="选择套餐" />
                </NFormItem>
              </NForm>
              <NSpace>
                <NButton type="primary" :disabled="!drawerPlanId" @click="onDrawerSetSubscription">
                  设置套餐
                </NButton>
                <NPopconfirm v-if="drawerRow?.subscription_name" @positive-click="onDrawerClearSubscription">
                  <template #trigger>
                    <NButton type="warning">清除套餐</NButton>
                  </template>
                  确认清除 {{ drawerRow?.username }} 的当前套餐？
                </NPopconfirm>
              </NSpace>
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
