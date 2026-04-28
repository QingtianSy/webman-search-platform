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
  useDialog,
  useMessage,
} from 'naive-ui';

import {
  type AdminQuestionApi,
  createQuestionApi,
  deleteQuestionApi,
  listQuestionsApi,
  reindexQuestionsApi,
  statsQuestionsApi,
  updateQuestionApi,
} from '#/api/admin';

const message = useMessage();
const dialog = useDialog();

const loading = ref(false);
const rows = ref<AdminQuestionApi.Question[]>([]);
const total = ref(0);
const page = ref(1);
const pageSize = ref(20);

const filter = reactive<{
  keyword: string;
  status: '' | number;
}>({ keyword: '', status: '' });

const statusOptions = [
  { label: '全部', value: '' },
  { label: '启用', value: 1 },
  { label: '禁用', value: 0 },
];

async function load() {
  loading.value = true;
  try {
    const data = await listQuestionsApi({
      keyword: filter.keyword || undefined,
      status: filter.status === '' ? undefined : filter.status,
      page: page.value,
      page_size: pageSize.value,
    });
    rows.value = data.list ?? [];
    total.value = data.total ?? 0;
  } catch {
    message.error('题目列表加载失败');
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

// ======== 编辑弹窗 ========
const editorVisible = ref(false);
const editing = ref<AdminQuestionApi.Question | null>(null);
const form = reactive<AdminQuestionApi.CreatePayload & { id?: string }>({
  stem: '',
  answer_text: '',
  options_text: '',
  type_code: '',
  type_name: '',
  source_name: '',
  course_name: '',
  status: 1,
});
const saving = ref(false);

function openCreate() {
  editing.value = null;
  Object.assign(form, {
    id: undefined,
    stem: '',
    answer_text: '',
    options_text: '',
    type_code: '',
    type_name: '',
    source_name: '',
    course_name: '',
    status: 1,
  });
  editorVisible.value = true;
}

function openEdit(row: AdminQuestionApi.Question) {
  editing.value = row;
  Object.assign(form, {
    id: row.id,
    stem: row.stem ?? '',
    answer_text: row.answer_text ?? '',
    options_text: row.options_text ?? '',
    type_code: row.type_code ?? '',
    type_name: row.type_name ?? '',
    source_name: row.source_name ?? '',
    course_name: row.course_name ?? '',
    status: row.status ?? 1,
  });
  editorVisible.value = true;
}

async function onSave() {
  if (!form.stem?.trim()) {
    message.warning('题干不能为空');
    return;
  }
  saving.value = true;
  try {
    if (editing.value && form.id) {
      await updateQuestionApi({ ...form, id: form.id });
      message.success('更新成功');
    } else {
      await createQuestionApi(form);
      message.success('创建成功，ES 索引将在秒级内同步');
    }
    editorVisible.value = false;
    load();
  } catch {
    // interceptor 会弹提示
  } finally {
    saving.value = false;
  }
}

async function onDelete(row: AdminQuestionApi.Question) {
  if (!row.id) return;
  try {
    await deleteQuestionApi(row.id);
    message.success('删除成功');
    load();
  } catch {
    // interceptor 会弹提示
  }
}

const reindexing = ref(false);
// 行级同步中的 question_id 集合
const rowSyncing = ref<Set<string>>(new Set());

const stats = ref<AdminQuestionApi.Stats | null>(null);
const statsLoading = ref(false);

async function loadStats() {
  statsLoading.value = true;
  try {
    stats.value = await statsQuestionsApi();
  } catch {
    // 失败静默；interceptor 已提示
  } finally {
    statsLoading.value = false;
  }
}

async function onReindexRow(row: AdminQuestionApi.Question) {
  if (!row.question_id) return;
  rowSyncing.value.add(row.question_id);
  // 触发响应
  rowSyncing.value = new Set(rowSyncing.value);
  try {
    await reindexQuestionsApi(row.question_id);
    message.success('已同步到 ES');
    load();
  } catch {
    // interceptor
  } finally {
    rowSyncing.value.delete(row.question_id);
    rowSyncing.value = new Set(rowSyncing.value);
  }
}

function onReindex() {
  dialog.warning({
    title: '全量重建 ES 索引',
    content:
      '耗时操作，会阻塞后台直到完成；仅在 ES 数据不一致时使用。确定继续？',
    positiveText: '执行',
    negativeText: '取消',
    onPositiveClick: async () => {
      reindexing.value = true;
      try {
        const res = await reindexQuestionsApi();
        message.success(
          `重建完成：共 ${res.count ?? '-'} 条${
            res.es_warning ? `（警告：${res.es_warning}）` : ''
          }`,
        );
        load();
      } catch {
        // interceptor
      } finally {
        reindexing.value = false;
      }
    },
  });
}

const columns: DataTableColumns<AdminQuestionApi.Question> = [
  {
    title: 'question_id',
    key: 'question_id',
    width: 210,
    ellipsis: { tooltip: true },
  },
  { title: '题干', key: 'stem', ellipsis: { tooltip: true } },
  { title: '答案', key: 'answer_text', width: 160, ellipsis: { tooltip: true } },
  { title: '题型', key: 'type_name', width: 90 },
  { title: '来源', key: 'source_name', width: 120 },
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
    title: 'ES',
    key: 'es_synced',
    width: 90,
    render: (row) =>
      row.es_synced === false
        ? h(NTag, { type: 'warning', size: 'small' }, () => '同步中')
        : h(NTag, { type: 'info', size: 'small' }, () => '已同步'),
  },
  { title: '更新时间', key: 'updated_at', width: 170 },
  {
    title: '操作',
    key: 'actions',
    width: 220,
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
          {
            size: 'small',
            loading: rowSyncing.value.has(row.question_id ?? ''),
            onClick: () => onReindexRow(row),
          },
          () => '同步ES',
        ),
        h(
          NPopconfirm,
          { onPositiveClick: () => onDelete(row) },
          {
            default: () => '确定删除该题目？ES 索引会自动清理。',
            trigger: () =>
              h(NButton, { size: 'small', type: 'error' }, () => '删除'),
          },
        ),
      ]),
  },
];

onMounted(() => {
  load();
  loadStats();
});
</script>

<template>
  <div class="p-6">
    <NCard v-if="stats" class="mb-4" size="small">
      <div class="flex flex-wrap gap-6">
        <div>
          <div class="text-xs text-gray-500">题目总数</div>
          <div class="text-2xl font-semibold">{{ stats.total }}</div>
        </div>
        <div>
          <div class="text-xs text-gray-500">启用 / 禁用</div>
          <div class="text-2xl font-semibold">
            {{ stats.status_breakdown.active }} /
            <span class="text-gray-400">{{ stats.status_breakdown.disabled }}</span>
          </div>
        </div>
      </div>
    </NCard>

    <NCard title="题目管理">
      <template #header-extra>
        <NSpace>
          <NButton :loading="reindexing" @click="onReindex">
            全量重建索引
          </NButton>
          <NButton type="primary" @click="openCreate">新增题目</NButton>
        </NSpace>
      </template>

      <NSpace class="mb-4" :wrap="true">
        <NInputGroup>
          <NInput
            v-model:value="filter.keyword"
            placeholder="题干/答案关键词"
            clearable
            style="width: 280px"
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
        :row-key="(row) => row.id ?? row.question_id"
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
      :title="editing ? '编辑题目' : '新增题目'"
      style="width: 720px"
      :mask-closable="false"
    >
      <NForm label-placement="left" label-width="auto">
        <NFormItem label="题干" required>
          <NInput
            v-model:value="form.stem"
            type="textarea"
            :autosize="{ minRows: 3, maxRows: 8 }"
          />
        </NFormItem>
        <NFormItem label="答案">
          <NInput
            v-model:value="form.answer_text"
            type="textarea"
            :autosize="{ minRows: 1, maxRows: 4 }"
            placeholder="多个答案可写在同一字段内"
          />
        </NFormItem>
        <NFormItem label="选项">
          <NInput
            v-model:value="form.options_text"
            type="textarea"
            :autosize="{ minRows: 2, maxRows: 6 }"
            placeholder="序列化格式：A|选项内容###B|选项内容###C|选项内容"
          />
        </NFormItem>
        <NFormItem label="题型代码">
          <NInput v-model:value="form.type_code" placeholder="single/multi/..." />
        </NFormItem>
        <NFormItem label="题型名称">
          <NInput v-model:value="form.type_name" />
        </NFormItem>
        <NFormItem label="来源">
          <NInput v-model:value="form.source_name" />
        </NFormItem>
        <NFormItem label="课程">
          <NInput v-model:value="form.course_name" />
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
