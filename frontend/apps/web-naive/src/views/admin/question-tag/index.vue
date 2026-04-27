<script lang="ts" setup>
// 管理端 · 题目标签字典。docs/07 §3.2.5 附属。
// name 唯一（见 0008 uk_name）；标签多用于题目的快速标记（灵活打标，非分类）。
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
  NSpace,
  useMessage,
} from 'naive-ui';

import {
  type AdminQuestionTagApi,
  createQuestionTagApi,
  deleteQuestionTagApi,
  listQuestionTagsApi,
  updateQuestionTagApi,
} from '#/api/admin';

const message = useMessage();

const loading = ref(false);
const rows = ref<AdminQuestionTagApi.Tag[]>([]);
const total = ref(0);
const page = ref(1);
const pageSize = ref(20);
const keyword = ref('');

async function load() {
  loading.value = true;
  try {
    const data = await listQuestionTagsApi({
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

const editorVisible = ref(false);
const editing = ref<AdminQuestionTagApi.Tag | null>(null);
const form = reactive<{ id?: number; name: string; sort: number }>({
  name: '',
  sort: 0,
});
const saving = ref(false);

function openCreate() {
  editing.value = null;
  Object.assign(form, { id: undefined, name: '', sort: 0 });
  editorVisible.value = true;
}

function openEdit(row: AdminQuestionTagApi.Tag) {
  editing.value = row;
  Object.assign(form, {
    id: row.id,
    name: row.name,
    sort: row.sort ?? 0,
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
    const payload = { name: form.name, sort: form.sort };
    if (editing.value && form.id) {
      await updateQuestionTagApi({ id: form.id, ...payload });
      message.success('更新成功');
    } else {
      await createQuestionTagApi(payload);
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

async function onDelete(row: AdminQuestionTagApi.Tag) {
  try {
    await deleteQuestionTagApi(row.id);
    message.success('删除成功');
    load();
  } catch {
    // interceptor
  }
}

const columns: DataTableColumns<AdminQuestionTagApi.Tag> = [
  { title: 'ID', key: 'id', width: 80 },
  { title: '名称', key: 'name', width: 200, ellipsis: { tooltip: true } },
  { title: '排序', key: 'sort', width: 100 },
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
          { size: 'tiny', onClick: () => openEdit(row) },
          () => '编辑',
        ),
        h(
          NPopconfirm,
          { onPositiveClick: () => onDelete(row) },
          {
            default: () => '确定删除该标签？',
            trigger: () =>
              h(NButton, { size: 'tiny', type: 'error' }, () => '删除'),
          },
        ),
      ]),
  },
];

onMounted(load);
</script>

<template>
  <div class="p-6">
    <NCard title="题目标签">
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
          <NButton type="primary" @click="openCreate">新建标签</NButton>
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
      :title="editing ? '编辑标签' : '新建标签'"
      preset="card"
      style="width: 480px"
    >
      <NForm label-placement="left" label-width="90px">
        <NFormItem label="名称" required>
          <NInput v-model:value="form.name" placeholder="标签名（需唯一）" />
        </NFormItem>
        <NFormItem label="排序">
          <NInputNumber v-model:value="form.sort" :min="0" />
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
