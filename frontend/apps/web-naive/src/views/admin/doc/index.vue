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
  NInputNumber,
  NModal,
  NPopconfirm,
  NSelect,
  NSpace,
  NTag,
  useMessage,
} from 'naive-ui';

import {
  type AdminDocApi,
  createAdminDocApi,
  deleteAdminDocApi,
  listAdminDocsApi,
  updateAdminDocApi,
} from '#/api/admin';

const message = useMessage();

const loading = ref(false);
const rows = ref<AdminDocApi.Article[]>([]);
const total = ref(0);
const page = ref(1);
const pageSize = ref(20);

async function load() {
  loading.value = true;
  try {
    const data = await listAdminDocsApi({
      page: page.value,
      page_size: pageSize.value,
    });
    rows.value = data.list ?? [];
    total.value = data.total ?? 0;
  } catch {
    message.error('文档列表加载失败');
  } finally {
    loading.value = false;
  }
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
const editing = ref<AdminDocApi.Article | null>(null);
const form = reactive<{
  id?: number;
  title: string;
  slug: string;
  category_id: number;
  summary: string;
  content_md: string;
  status: number;
}>({
  title: '',
  slug: '',
  category_id: 1,
  summary: '',
  content_md: '',
  status: 1,
});
const saving = ref(false);

function openCreate() {
  editing.value = null;
  Object.assign(form, {
    id: undefined,
    title: '',
    slug: '',
    category_id: 1,
    summary: '',
    content_md: '',
    status: 1,
  });
  editorVisible.value = true;
}

function openEdit(row: AdminDocApi.Article) {
  editing.value = row;
  Object.assign(form, {
    id: row.id,
    title: row.title ?? '',
    slug: row.slug ?? '',
    category_id: row.category_id ?? 1,
    summary: row.summary ?? '',
    content_md: row.content_md ?? '',
    status: row.status ?? 1,
  });
  editorVisible.value = true;
}

async function onSave() {
  if (!form.title.trim()) {
    message.warning('标题不能为空');
    return;
  }
  if (!form.slug.trim()) {
    message.warning('slug 不能为空');
    return;
  }
  saving.value = true;
  try {
    if (editing.value && form.id) {
      await updateAdminDocApi({
        id: form.id,
        title: form.title,
        slug: form.slug,
        category_id: form.category_id,
        summary: form.summary,
        content_md: form.content_md,
        status: form.status,
      });
      message.success('更新成功');
    } else {
      await createAdminDocApi({
        title: form.title,
        slug: form.slug,
        category_id: form.category_id,
        summary: form.summary,
        content_md: form.content_md,
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

async function onDelete(row: AdminDocApi.Article) {
  try {
    await deleteAdminDocApi(row.id);
    message.success('删除成功');
    load();
  } catch {
    // interceptor
  }
}

const columns: DataTableColumns<AdminDocApi.Article> = [
  { title: 'ID', key: 'id', width: 70 },
  { title: '标题', key: 'title', width: 220, ellipsis: { tooltip: true } },
  { title: 'slug', key: 'slug', width: 180, ellipsis: { tooltip: true } },
  { title: '分类ID', key: 'category_id', width: 80 },
  { title: '摘要', key: 'summary', ellipsis: { tooltip: true } },
  {
    title: '状态',
    key: 'status',
    width: 80,
    render: (row) =>
      row.status === 1
        ? h(NTag, { type: 'success', size: 'small' }, () => '启用')
        : h(NTag, { size: 'small' }, () => '禁用'),
  },
  { title: '更新时间', key: 'updated_at', width: 170 },
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
            default: () => '删除后无法恢复',
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
    <NCard title="文档管理">
      <template #header-extra>
        <NSpace>
          <NButton @click="load">刷新</NButton>
          <NButton type="primary" @click="openCreate">新增文档</NButton>
        </NSpace>
      </template>

      <NDataTable
        remote
        :loading="loading"
        :columns="columns"
        :data="rows"
        :row-key="(row: AdminDocApi.Article) => row.id"
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

    <NModal
      v-model:show="editorVisible"
      preset="card"
      :title="editing ? '编辑文档' : '新增文档'"
      style="width: 760px"
      :mask-closable="false"
    >
      <NForm label-placement="left" label-width="auto">
        <NFormItem label="标题" required>
          <NInput v-model:value="form.title" />
        </NFormItem>
        <NFormItem label="slug" required>
          <NInput
            v-model:value="form.slug"
            placeholder="用于用户端 URL，全局唯一，建议英文小写+短横"
          />
        </NFormItem>
        <NFormItem label="分类 ID">
          <NInputNumber v-model:value="form.category_id" :min="1" />
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
        <NFormItem label="摘要">
          <NInput
            v-model:value="form.summary"
            type="textarea"
            :autosize="{ minRows: 1, maxRows: 4 }"
          />
        </NFormItem>
        <NFormItem label="正文(MD)">
          <NInput
            v-model:value="form.content_md"
            type="textarea"
            :autosize="{ minRows: 8, maxRows: 20 }"
            placeholder="Markdown 原文，用户端目前以纯文本展示"
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
