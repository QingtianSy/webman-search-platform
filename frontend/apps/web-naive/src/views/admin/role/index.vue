<script lang="ts" setup>
// 管理端 · 角色管理。docs/07 §3.2.3。
// 布局：左列表 + 右 3 Tab（基本 / 权限树 / 菜单）；支持克隆 / 权限 diff
import { computed, onMounted, reactive, ref } from 'vue';

import {
  NButton,
  NCard,
  NDivider,
  NEmpty,
  NForm,
  NFormItem,
  NInput,
  NInputNumber,
  NList,
  NListItem,
  NPopconfirm,
  NScrollbar,
  NSelect,
  NSpace,
  NTabs,
  NTabPane,
  NTag,
  NThing,
  NTree,
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
const saving = ref(false);
const rows = ref<AdminRoleApi.Role[]>([]);
const keyword = ref('');
const selectedId = ref<null | number>(null);

const current = computed(
  () => rows.value.find((r) => r.id === selectedId.value) ?? null,
);

// 权限树（list-as-tree 扁平源）
const permissions = ref<AdminRoleApi.Permission[]>([]);
const treeData = computed(() =>
  // 扁平 → 简单按 code 前缀 group；code 形如 "admin.user.list"
  groupPermissionsByPrefix(permissions.value),
);

function groupPermissionsByPrefix(list: AdminRoleApi.Permission[]) {
  const map = new Map<string, any>();
  for (const p of list) {
    const parts = p.code.split('.');
    const groupKey = parts.slice(0, 2).join('.');
    if (!map.has(groupKey)) {
      map.set(groupKey, {
        key: `group:${groupKey}`,
        label: groupKey,
        children: [],
      });
    }
    map.get(groupKey).children.push({ key: p.id, label: `${p.name}（${p.code}）` });
  }
  return [...map.values()];
}

// 已选权限
const permChecked = ref<Array<number | string>>([]);

// 表单（基本 Tab）
const form = reactive<{
  id?: number;
  name: string;
  code: string;
  sort: number;
  status: number;
}>({ name: '', code: '', sort: 0, status: 1 });

function syncForm() {
  if (current.value) {
    Object.assign(form, {
      id: current.value.id,
      name: current.value.name,
      code: current.value.code,
      sort: current.value.sort ?? 0,
      status: current.value.status ?? 1,
    });
    permChecked.value = (current.value.permissions ?? []).map((p) => p.id);
  } else {
    Object.assign(form, { id: undefined, name: '', code: '', sort: 0, status: 1 });
    permChecked.value = [];
  }
}

async function loadRoles() {
  loading.value = true;
  try {
    const data = await listRolesApi({
      keyword: keyword.value || undefined,
      page: 1,
      page_size: 100,
    });
    rows.value = data.list ?? [];
    if (!selectedId.value && rows.value.length > 0) {
      selectedId.value = rows.value[0]!.id;
    }
    syncForm();
  } catch {
    message.error('角色列表加载失败');
  } finally {
    loading.value = false;
  }
}

async function loadPermissions() {
  try {
    const data = await listPermissionsApi({ page: 1, page_size: 500 });
    permissions.value = data.list ?? [];
  } catch {
    // ignore
  }
}

function selectRole(id: number) {
  selectedId.value = id;
  syncForm();
}

async function onCreate() {
  const name = prompt('新角色名称？', '新角色');
  if (!name) return;
  const code = prompt('新角色编码（如 editor）？', '');
  if (!code) return;
  try {
    await createRoleApi({ name, code, sort: 0, status: 1 });
    message.success('已创建');
    await loadRoles();
  } catch {
    // interceptor
  }
}

async function onClone() {
  if (!current.value) return;
  const name = prompt('克隆后的新角色名称？', `${current.value.name}-副本`);
  if (!name) return;
  const code = prompt('克隆后的新编码？', `${current.value.code}_copy`);
  if (!code) return;
  try {
    const created: any = await createRoleApi({
      name,
      code,
      sort: current.value.sort,
      status: current.value.status,
    });
    const newId = created?.id;
    if (newId && current.value.permissions?.length) {
      await assignRolePermissionsApi(
        newId,
        current.value.permissions.map((p) => p.id),
      );
    }
    message.success('克隆完成');
    selectedId.value = null;
    await loadRoles();
  } catch {
    // interceptor
  }
}

async function onSaveBasic() {
  if (!form.id) return;
  if (!form.name.trim() || !form.code.trim()) {
    message.warning('名称和编码不能为空');
    return;
  }
  saving.value = true;
  try {
    await updateRoleApi({
      id: form.id,
      name: form.name,
      code: form.code,
      sort: form.sort,
      status: form.status,
    });
    message.success('已保存基本信息');
    await loadRoles();
  } catch {
    // interceptor
  } finally {
    saving.value = false;
  }
}

// 权限 diff
const permDiff = computed(() => {
  if (!current.value) return { added: [], removed: [] };
  const original = new Set((current.value.permissions ?? []).map((p) => p.id));
  const now = new Set(
    permChecked.value.filter((v): v is number => typeof v === 'number'),
  );
  const added = [...now].filter((x) => !original.has(x));
  const removed = [...original].filter((x) => !now.has(x));
  return { added, removed };
});

async function onSavePermissions() {
  if (!form.id) return;
  saving.value = true;
  try {
    const ids = permChecked.value.filter(
      (v): v is number => typeof v === 'number',
    );
    await assignRolePermissionsApi(form.id, ids);
    message.success('权限已更新（该角色下所有用户 token 立即失效）');
    await loadRoles();
  } catch {
    // interceptor
  } finally {
    saving.value = false;
  }
}

async function onDelete() {
  if (!current.value) return;
  try {
    await deleteRoleApi(current.value.id);
    message.success('删除成功');
    selectedId.value = null;
    await loadRoles();
  } catch {
    // interceptor
  }
}

onMounted(async () => {
  await loadPermissions();
  await loadRoles();
});
</script>

<template>
  <div class="p-6">
    <NCard title="角色管理">
      <div class="flex gap-4" style="min-height: 600px">
        <!-- 左侧角色列表 -->
        <div style="width: 280px; flex-shrink: 0">
          <NSpace class="mb-3">
            <NInput
              v-model:value="keyword"
              placeholder="搜索角色"
              clearable
              @keydown.enter="loadRoles"
            />
            <NButton type="primary" size="small" @click="onCreate">新建</NButton>
          </NSpace>

          <NScrollbar style="max-height: 640px">
            <NList hoverable clickable>
              <NListItem
                v-for="r in rows"
                :key="r.id"
                :class="{ 'bg-primary/10': r.id === selectedId }"
                @click="selectRole(r.id)"
              >
                <NThing>
                  <template #header>
                    <span class="font-medium">{{ r.name }}</span>
                  </template>
                  <template #header-extra>
                    <NTag
                      :type="r.status === 1 ? 'success' : 'error'"
                      size="tiny"
                    >
                      {{ r.status === 1 ? '启用' : '禁用' }}
                    </NTag>
                  </template>
                  <template #description>
                    <span class="text-xs text-muted-foreground">{{ r.code }}</span>
                  </template>
                </NThing>
              </NListItem>
            </NList>
            <div
              v-if="!loading && rows.length === 0"
              class="text-center py-6 text-muted-foreground text-sm"
            >
              暂无角色
            </div>
          </NScrollbar>
        </div>

        <NDivider vertical style="height: auto" />

        <!-- 右侧 3 Tab -->
        <div class="flex-1">
          <NEmpty v-if="!current" description="请选择左侧角色" class="mt-20" />
          <NTabs v-else type="line">
            <NTabPane name="basic" tab="基本">
              <NForm label-placement="left" label-width="auto" style="max-width: 520px">
                <NFormItem label="名称" required>
                  <NInput v-model:value="form.name" />
                </NFormItem>
                <NFormItem label="编码" required>
                  <NInput v-model:value="form.code" :disabled="true" />
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
              <NSpace>
                <NButton type="primary" :loading="saving" @click="onSaveBasic">
                  保存基本信息
                </NButton>
                <NButton @click="onClone">克隆此角色</NButton>
                <NPopconfirm @positive-click="onDelete">
                  <template #trigger>
                    <NButton type="error">删除</NButton>
                  </template>
                  删除会清空 role_permission / user_role 关联，请谨慎
                </NPopconfirm>
              </NSpace>
            </NTabPane>

            <NTabPane name="permission" tab="权限">
              <div class="mb-3 text-xs text-muted-foreground">
                按 code 前缀自动分组；保存后该角色下所有用户 token 立即失效。
              </div>
              <NTree
                v-model:checked-keys="permChecked"
                :data="treeData"
                checkable
                cascade
                :default-expand-all="true"
                style="max-height: 420px; overflow: auto"
              />
              <div class="mt-3 text-xs">
                <NSpace :size="4" :wrap="true">
                  <NTag
                    v-if="permDiff.added.length > 0"
                    type="success"
                    size="small"
                  >
                    +{{ permDiff.added.length }} 新增
                  </NTag>
                  <NTag
                    v-if="permDiff.removed.length > 0"
                    type="error"
                    size="small"
                  >
                    -{{ permDiff.removed.length }} 移除
                  </NTag>
                  <span
                    v-if="permDiff.added.length === 0 && permDiff.removed.length === 0"
                    class="text-muted-foreground"
                  >
                    未改动
                  </span>
                </NSpace>
              </div>
              <NButton
                type="primary"
                :loading="saving"
                class="mt-3"
                @click="onSavePermissions"
              >
                保存权限
              </NButton>
            </NTabPane>

            <NTabPane name="menu" tab="菜单">
              <div class="text-sm text-muted-foreground">
                菜单与权限同源：凡包含 <code class="mx-1">.menu</code> /
                <code class="mx-1">.view</code> 前缀的权限即为侧边栏入口；
                当前角色菜单由「权限」Tab 勾选内容自动派生。
              </div>
              <NDivider />
              <NSpace :size="[4, 4]" :wrap="true">
                <NTag
                  v-for="p in current.permissions ?? []"
                  :key="p.id"
                  size="small"
                  type="info"
                >
                  {{ p.name }}
                </NTag>
                <span
                  v-if="(current.permissions ?? []).length === 0"
                  class="text-xs text-muted-foreground"
                >
                  该角色当前无任何菜单入口
                </span>
              </NSpace>
            </NTabPane>
          </NTabs>
        </div>
      </div>
    </NCard>
  </div>
</template>
