<script lang="ts" setup>
// 管理端 · 题目分类字典 CRUD。docs/07 §3.2.5 附属。
// 简单 CRUD：表格 + 搜索 + 新建/编辑 Modal + parent_id 下拉（允许空即顶级）。
import type { DataTableColumns } from 'naive-ui';

import { h, onMounted, reactive, ref } from 'vue';

import {
  NButton,
  NCard,
  NDataTable,
  NForm,
  NFormItem,
  NInput,
  NInputNumber,
  NModal,
  NPopconfirm,
  NSelect,
  NSpace,
  NTag,
  useMessage,
} from 'naive-ui';

import {
  type AdminQuestionCategoryApi,
  createQuestionCategoryApi,
  deleteQuestionCategoryApi,
  listQuestionCategoriesApi,
  updateQuestionCategoryApi,
} from '#/api/admin';

const message = useMessage();

const loading = ref(false);
const rows = ref<AdminQuestionCategoryApi.Category[]>([]);
const total = ref(0);
const page = ref(1);
const pageSize = ref(20);
const keyword = ref('');

// 所有分类（下拉 parent 使用，单独取 500 条兜底）
const allCategories = ref<AdminQuestionCategoryApi.Category[]>([]);

const parentOptions = ref<{ label: string; value: number }[]>([]);

async function loadAll() {
  try {
    const data = await listQuestionCategoriesApi({ page: 1, page_size: 500 });
    allCategories.value = data.list ?? [];
    parentOptions.value = [
      { label: '— 顶级分类 —', value: 0 },
      ...allCategories.value.map((c) => ({ label: c.name, value: c.id })),
    ];
  } catch {
    // 兜底
  }
}

async function load() {
  loading.value = true;
  try {
    const data = await listQuestionCategoriesApi({
      keyword: keyword.value || undefined,
      page: page.value,
      page_size: pageSize.value,
    });
    rows.value = data.list ?? [];
    total.value = data.total ?? 0;
  } catch {
    message.error('加载失败');
  } finally {
    loading.value = false;
  }
}

function onSearch() {
  page.value = 1;
  load();
}
function onReset() {
  keyword.value = '';
  page.value = 1;
  load();
}

// ======== 编辑 ========
const editorVisible = ref(false);
const editing = ref<AdminQuestionCategoryApi.Category | null>(null);
const form = reactive<{
  id?: number;
  name: string;
  parent_id: number;
  sort: number;
  status: number;
}>({
  name: '',
  parent_id: 0,
  sort: 0,
  status: 1,
});
const saving = ref(false);

function openCreate() {
  editing.value = null;
  Object.assign(form, {
    id: undefined,
    name: '',
    parent_id: 0,
    sort: 0,
    status: 1,
  });
  editorVisible.value = true;
}

function openEdit(row: AdminQuestionCategoryApi.Category) {
  editing.value = row;
  Object.assign(form, {
    id: row.id,
    name: row.name,
    parent_id: row.parent_id ?? 0,
    sort: row.sort ?? 0,
    status: row.status ?? 1,
  });
  editorVisible.value = true;
}

async function onSave() {
  if (!form.name.trim()) {
    message.warning('名称不能为空');
    return;
  }
  saving.value = true;
  try {
    const payload = {
      name: form.name,
      parent_id: form.parent_id,
      sort: form.sort,
      status: form.status,
    };
    if (editing.value && form.id) {
      await updateQuestionCategoryApi({ id: form.id, ...payload });
      message.success('更新成功');
    } else {
      await createQuestionCategoryApi(payload);
      message.success('创建成功');
    }
    editorVisible.value = false;
    load();
    loadAll();
  } catch {
    // interceptor
  } finally {
    saving.value = false;
  }
}

async function onDelete(row: AdminQuestionCategoryApi.Category) {
  try {
    await deleteQuestionCategoryApi(row.id);
    message.success('删除成功');
    load();
    loadAll();
  } catch {
    // interceptor
  }
}

function parentName(id: number): string {
  if (!id) return '顶级';
  return allCategories.value.find((c) => c.id === id)?.name ?? `#${id}`;
}

const columns: DataTableColumns<AdminQuestionCategoryApi.Category> = [
  { title: 'ID', key: 'id', width: 70 },
  { title: '名称', key: 'name', width: 180, ellipsis: { tooltip: true } },
  {
    title: '上级',
    key: 'parent_id',
    width: 140,
    render: (r) => parentName(r.parent_id),
  },
  { title: '排序', key: 'sort', width: 80 },
  {
    title: '状态',
    key: 'status',
    width: 80,
    render: (r) =>
      h(
        NTag,
        { size: 'small', type: r.status === 1 ? 'success' : 'default' },
        () => (r.status === 1 ? '启用' : '停用'),
      ),
  },
  { title: '更新', key: 'updated_at', width: 170 },
  {
    title: '操作',
    key: 'actions',
    width: 160,
    fixed: 'right',
    render: (row) =>
      h(NSpace, { size: 'small' }, () => [
        h(
          NButton,
          {
            size: 'tiny',
            onClick: () => openEdit(row),
          },
          () => '编辑',
        ),
        h(
          NPopconfirm,
          {
            onPositiveClick: () => onDelete(row),
          },
          {
            default: () => '确定删除该分类吗？',
            trigger: () =>
              h(NButton, { size: 'tiny', type: 'error' }, () => '删除'),
          },
        ),
      ]),
  },
];

onMounted(() => {
  load();
  loadAll();
});
</script>

<template>
  <div class="p-6">
    <NCard title="题目分类">
      <template #header-extra>
        <NSpace>
          <NInput
            v-model:value="keyword"
            placeholder="按名称搜索"
            clearable
            style="width: 200px"
            @keyup.enter="onSearch"
          />
          <NButton type="primary" @click="onSearch">搜索</NButton>
          <NButton @click="onReset">重置</NButton>
          <NButton type="primary" @click="openCreate">新建分类</NButton>
        </NSpace>
      </template>

      <NDataTable
        :columns="columns"
        :data="rows"
        :loading="loading"
        :pagination="{
          page,
          pageSize,
          itemCount: total,
          showSizePicker: true,
          pageSizes: [10, 20, 50, 100],
          onChange: (p: number) => {
            page = p;
            load();
          },
          onUpdatePageSize: (s: number) => {
            pageSize = s;
            page = 1;
            load();
          },
        }"
        :row-key="(r: any) => r.id"
      />
    </NCard>

    <NModal
      v-model:show="editorVisible"
      :title="editing ? '编辑分类' : '新建分类'"
      preset="card"
      style="width: 520px"
    >
      <NForm label-placement="left" label-width="90px">
        <NFormItem label="名称" required>
          <NInput v-model:value="form.name" placeholder="分类名" />
        </NFormItem>
        <NFormItem label="上级分类">
          <NSelect
            v-model:value="form.parent_id"
            :options="parentOptions"
            filterable
          />
        </NFormItem>
        <NFormItem label="排序">
          <NInputNumber v-model:value="form.sort" :min="0" />
        </NFormItem>
        <NFormItem label="状态">
          <NSelect
            v-model:value="form.status"
            :options="[
              { label: '启用', value: 1 },
              { label: '停用', value: 0 },
            ]"
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
