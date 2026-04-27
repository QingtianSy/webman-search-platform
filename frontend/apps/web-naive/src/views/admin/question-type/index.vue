<script lang="ts" setup>
// 管理端 · 题型字典。docs/07 §3.2.5 附属。
// code 唯一（对齐题目 type_code），更新时禁止随意改 code（会孤立旧题）。
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
  type AdminQuestionTypeApi,
  createQuestionTypeApi,
  deleteQuestionTypeApi,
  listQuestionTypesApi,
  updateQuestionTypeApi,
} from '#/api/admin';

const message = useMessage();

const loading = ref(false);
const rows = ref<AdminQuestionTypeApi.Type[]>([]);
const total = ref(0);
const page = ref(1);
const pageSize = ref(20);
const keyword = ref('');

async function load() {
  loading.value = true;
  try {
    const data = await listQuestionTypesApi({
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
const editing = ref<AdminQuestionTypeApi.Type | null>(null);
const form = reactive<{
  id?: number;
  code: string;
  name: string;
  sort: number;
  status: number;
}>({
  code: '',
  name: '',
  sort: 0,
  status: 1,
});
const saving = ref(false);

function openCreate() {
  editing.value = null;
  Object.assign(form, {
    id: undefined,
    code: '',
    name: '',
    sort: 0,
    status: 1,
  });
  editorVisible.value = true;
}

function openEdit(row: AdminQuestionTypeApi.Type) {
  editing.value = row;
  Object.assign(form, {
    id: row.id,
    code: row.code,
    name: row.name,
    sort: row.sort ?? 0,
    status: row.status ?? 1,
  });
  editorVisible.value = true;
}

async function onSave() {
  if (!form.code.trim() || !form.name.trim()) {
    message.warning('编码和名称不能为空');
    return;
  }
  saving.value = true;
  try {
    const payload = {
      code: form.code,
      name: form.name,
      sort: form.sort,
      status: form.status,
    };
    if (editing.value && form.id) {
      await updateQuestionTypeApi({ id: form.id, ...payload });
      message.success('更新成功');
    } else {
      await createQuestionTypeApi(payload);
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

async function onDelete(row: AdminQuestionTypeApi.Type) {
  try {
    await deleteQuestionTypeApi(row.id);
    message.success('删除成功');
    load();
  } catch {
    // interceptor
  }
}

const columns: DataTableColumns<AdminQuestionTypeApi.Type> = [
  { title: 'ID', key: 'id', width: 70 },
  { title: '编码', key: 'code', width: 140 },
  { title: '名称', key: 'name', width: 160, ellipsis: { tooltip: true } },
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
          { size: 'tiny', onClick: () => openEdit(row) },
          () => '编辑',
        ),
        h(
          NPopconfirm,
          { onPositiveClick: () => onDelete(row) },
          {
            default: () => '确定删除该题型？',
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
    <NCard title="题型字典">
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
          <NButton type="primary" @click="openCreate">新建题型</NButton>
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
      :title="editing ? '编辑题型' : '新建题型'"
      preset="card"
      style="width: 520px"
    >
      <NForm label-placement="left" label-width="90px">
        <NFormItem label="编码" required>
          <NInput
            v-model:value="form.code"
            placeholder="如 single_choice / multi_choice"
            :disabled="!!editing"
          />
        </NFormItem>
        <NFormItem label="名称" required>
          <NInput v-model:value="form.name" placeholder="展示名" />
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
