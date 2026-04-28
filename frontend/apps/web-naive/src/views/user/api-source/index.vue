<script lang="ts" setup>
import { computed, h, onMounted, reactive, ref } from 'vue';

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
  NPopover,
  NSelect,
  NSpace,
  NSwitch,
  NTag,
  useDialog,
  useMessage,
  type DataTableColumns,
  type DataTableRowKey,
  type FormInst,
  type FormRules,
} from 'naive-ui';

import {
  createApiSourceApi,
  deleteApiSourceApi,
  listApiSourcesApi,
  testApiSourceApi,
  toggleApiSourceApi,
  updateApiSourceApi,
  type ApiSourceApi,
} from '#/api/user/api-source';
import { usePagination } from '#/composables/usePagination';

const message = useMessage();
const dialog = useDialog();

// 筛选
const filter = reactive({ name: '', status: undefined as number | undefined });

// 分页
const pg = usePagination(10);

// 表格数据
const loading = ref(false);
const rows = ref<ApiSourceApi.Source[]>([]);
const checkedKeys = ref<DataTableRowKey[]>([]);

async function loadList() {
  loading.value = true;
  try {
    const r = await listApiSourcesApi({
      name: filter.name || undefined,
      status: filter.status,
      page: pg.page.value,
      page_size: pg.pageSize.value,
    });
    rows.value = r.list ?? [];
    pg.apply(r);
  } finally {
    loading.value = false;
  }
}

function onSearch() {
  pg.page.value = 1;
  loadList();
}

function onReset() {
  filter.name = '';
  filter.status = undefined;
  onSearch();
}

// 编辑 Modal
const modalVisible = ref(false);
const modalMode = ref<'create' | 'edit'>('create');
const formRef = ref<FormInst | null>(null);
const form = reactive<Partial<ApiSourceApi.Source> & { keyword?: string }>({
  name: '',
  url: '',
  method: 'GET',
  keyword_param: 'question',
  type_param: '',
  timeout: 5,
  sort: 0,
  status: 1,
  response_type: 'json',
  answer_field: 'data',
});
const editingId = ref<null | number>(null);

function resetForm() {
  form.name = '';
  form.url = '';
  form.method = 'GET';
  form.keyword_param = 'question';
  form.type_param = '';
  form.timeout = 5;
  form.sort = 0;
  form.status = 1;
  form.response_type = 'json';
  form.answer_field = 'data';
  editingId.value = null;
}

function openCreate() {
  modalMode.value = 'create';
  resetForm();
  modalVisible.value = true;
}

const formRules: FormRules = {
  name: { required: true, message: '请输入接口名称', trigger: 'blur' },
  url: { required: true, message: '请输入接口地址', trigger: 'blur' },
  keyword_param: {
    required: true,
    message: '请输入题干参数名',
    trigger: 'blur',
  },
  timeout: {
    required: true,
    type: 'number',
    message: '请输入超时秒数',
    trigger: ['blur', 'change'],
  },
};

const submitting = ref(false);

async function submitForm() {
  try {
    await formRef.value?.validate();
  } catch {
    return;
  }
  submitting.value = true;
  try {
    const payload = { ...form } as any;
    delete payload.keyword;
    if (modalMode.value === 'create') {
      await createApiSourceApi(payload);
      message.success('添加成功');
    } else if (editingId.value) {
      await updateApiSourceApi(editingId.value, payload);
      message.success('更新成功');
    }
    modalVisible.value = false;
    loadList();
  } catch {
    message.error('保存失败');
  } finally {
    submitting.value = false;
  }
}

// 测试
const testing = ref(false);
const testResult = ref<ApiSourceApi.TestResult | null>(null);
const testKeyword = ref('测试题目');

async function runTest() {
  testing.value = true;
  testResult.value = null;
  try {
    testResult.value = await testApiSourceApi({
      ...form,
      keyword: testKeyword.value,
    });
  } finally {
    testing.value = false;
  }
}

// 批量删除
async function batchDelete() {
  if (checkedKeys.value.length === 0) return;
  dialog.warning({
    title: '批量删除',
    content: `确认删除选中的 ${checkedKeys.value.length} 条记录？`,
    positiveText: '确认',
    negativeText: '取消',
    onPositiveClick: async () => {
      try {
        await deleteApiSourceApi(checkedKeys.value.map((k) => Number(k)));
        message.success('已删除');
        checkedKeys.value = [];
        loadList();
      } catch {
        message.error('删除失败');
      }
    },
  });
}

async function deleteOne(id: number) {
  try {
    await deleteApiSourceApi(id);
    message.success('已删除');
    loadList();
  } catch {
    message.error('删除失败');
  }
}

async function toggleStatus(row: ApiSourceApi.Source, v: boolean) {
  const newStatus = v ? 1 : 0;
  try {
    await toggleApiSourceApi(row.id, newStatus);
    row.status = newStatus;
    message.success(v ? '已启用' : '已禁用');
  } catch {
    message.error('切换失败');
  }
}

function copy(text: string) {
  navigator.clipboard
    ?.writeText(text)
    .then(() => message.success('已复制'))
    .catch(() => message.error('复制失败'));
}

const columns = computed<DataTableColumns<ApiSourceApi.Source>>(() => [
  { type: 'selection' },
  { title: 'ID', key: 'id', width: 70 },
  { title: '接口名称', key: 'name', minWidth: 140 },
  {
    title: '接口地址',
    key: 'url',
    minWidth: 260,
    render: (row) =>
      h(
        'div',
        { class: 'flex items-center gap-1' },
        [
          h(
            'span',
            {
              class: 'truncate font-mono text-xs',
              style: 'max-width:220px',
              title: row.url,
            },
            row.url,
          ),
          h(
            NButton,
            {
              text: true,
              size: 'tiny',
              type: 'primary',
              onClick: () => copy(row.url),
            },
            { default: () => '复制' },
          ),
        ],
      ),
  },
  {
    title: '请求方式',
    key: 'method',
    width: 100,
    render: (row) =>
      h(
        NTag,
        { size: 'small', type: row.method === 'GET' ? 'success' : 'info' },
        { default: () => row.method },
      ),
  },
  { title: '关键词参数', key: 'keyword_param', width: 130 },
  { title: '类型参数', key: 'type_param', width: 120 },
  { title: '超时(s)', key: 'timeout', width: 90 },
  { title: '排序', key: 'sort', width: 80 },
  {
    title: '状态',
    key: 'status',
    width: 90,
    render: (row) =>
      h(NSwitch, {
        value: row.status === 1,
        'onUpdate:value': (v: boolean) => toggleStatus(row, v),
      }),
  },
  {
    title: '操作',
    key: 'actions',
    width: 220,
    fixed: 'right',
    render: (row) =>
      h(NSpace, { size: 'small' }, {
        default: () => [
          h(
            NButton,
            {
              size: 'small',
              type: 'primary',
              ghost: true,
              onClick: () => {
                Object.assign(form, row);
                editingId.value = row.id;
                modalMode.value = 'edit';
                modalVisible.value = true;
                testResult.value = null;
              },
            },
            { default: () => '编辑' },
          ),
          h(
            NPopconfirm,
            { onPositiveClick: () => deleteOne(row.id) },
            {
              trigger: () =>
                h(
                  NButton,
                  { size: 'small', type: 'error', ghost: true },
                  { default: () => '删除' },
                ),
              default: () => '确认删除该记录？',
            },
          ),
        ],
      }),
  },
]);

onMounted(loadList);
</script>

<template>
  <div class="p-6">
    <NCard :bordered="false" size="small" class="mb-3">
      <NSpace>
        <NInput
          v-model:value="filter.name"
          placeholder="接口名称"
          clearable
          style="width: 200px"
        />
        <NSelect
          v-model:value="filter.status"
          placeholder="状态"
          clearable
          style="width: 140px"
          :options="[
            { label: '启用', value: 1 },
            { label: '禁用', value: 0 },
          ]"
        />
        <NButton type="primary" @click="onSearch">查询</NButton>
        <NButton @click="onReset">重置</NButton>
      </NSpace>
    </NCard>

    <NCard :bordered="false" size="small" title="题库配置">
      <template #header-extra>
        <NSpace>
          <NButton type="primary" @click="openCreate">+ 添加</NButton>
          <NButton
            type="error"
            :disabled="checkedKeys.length === 0"
            @click="batchDelete"
          >
            批量删除
          </NButton>
        </NSpace>
      </template>

      <NDataTable
        remote
        :columns="columns"
        :data="rows"
        :loading="loading"
        :row-key="(r: ApiSourceApi.Source) => r.id"
        v-model:checked-row-keys="checkedKeys"
        :pagination="{
          page: pg.page.value,
          pageSize: pg.pageSize.value,
          itemCount: pg.total.value,
          showSizePicker: true,
          pageSizes: [10, 20, 50],
          onUpdatePage: (p: number) => {
            pg.onPageChange(p);
            loadList();
          },
          onUpdatePageSize: (s: number) => {
            pg.onPageSizeChange(s);
            loadList();
          },
        }"
        :scroll-x="1400"
      />
    </NCard>

    <!-- 编辑 Modal -->
    <NModal
      v-model:show="modalVisible"
      preset="card"
      :title="modalMode === 'create' ? '新增接口源' : '编辑接口源'"
      style="width: 720px"
    >
      <NForm ref="formRef" :model="form" :rules="formRules" label-placement="left" label-width="110">
        <div class="section-title">基本信息</div>
        <NFormItem label="接口名称" path="name">
          <NInput v-model:value="form.name" placeholder="如：XX 题库" />
        </NFormItem>
        <NFormItem label="接口地址" path="url">
          <NInput
            v-model:value="form.url"
            placeholder="https://example.com/api/search"
          />
        </NFormItem>
        <NFormItem label="请求方式">
          <NSelect
            v-model:value="form.method"
            :options="[
              { label: 'GET', value: 'GET' },
              { label: 'POST', value: 'POST' },
            ]"
            style="width: 160px"
          />
        </NFormItem>

        <div class="section-title">参数映射</div>
        <NFormItem label="题干参数名" path="keyword_param">
          <NInput
            v-model:value="form.keyword_param"
            placeholder="如 question / title"
          />
        </NFormItem>
        <NFormItem label="类型参数名">
          <NInput v-model:value="form.type_param" placeholder="可选" />
        </NFormItem>

        <div class="section-title">解析配置</div>
        <NFormItem label="响应类型">
          <NSelect
            v-model:value="form.response_type"
            :options="[
              { label: 'JSON', value: 'json' },
              { label: '表单', value: 'form' },
              { label: '文本', value: 'text' },
            ]"
            style="width: 200px"
          />
        </NFormItem>
        <NFormItem label="答案字段">
          <NInput
            v-model:value="form.answer_field"
            placeholder="如 data / answer / data.answer"
          />
        </NFormItem>

        <div class="section-title">运行控制</div>
        <NFormItem label="超时(秒)" path="timeout">
          <NInputNumber v-model:value="form.timeout" :min="1" :max="60" />
        </NFormItem>
        <NFormItem label="排序">
          <NInputNumber v-model:value="form.sort" :min="0" :max="9999" />
        </NFormItem>
        <NFormItem label="状态">
          <NSwitch
            :value="form.status === 1"
            @update:value="(v: boolean) => (form.status = v ? 1 : 0)"
          />
        </NFormItem>

        <div class="section-title">接口测试</div>
        <NFormItem label="测试关键词">
          <NSpace>
            <NInput v-model:value="testKeyword" style="width: 220px" />
            <NPopover trigger="click" placement="right" :width="360">
              <template #trigger>
                <NButton :loading="testing" @click="runTest">发送测试</NButton>
              </template>
              <div v-if="!testResult" class="text-gray-400">尚未测试</div>
              <div v-else>
                <NTag
                  :type="testResult.ok ? 'success' : 'error'"
                  size="small"
                  round
                >
                  {{ testResult.ok ? '成功' : '失败' }}
                </NTag>
                <div class="mt-2 text-xs">
                  HTTP {{ testResult.status ?? '-' }} · 耗时 {{ testResult.cost_ms ?? '-' }}ms
                </div>
                <pre
                  v-if="testResult.sample"
                  class="sample"
                >{{ testResult.sample }}</pre>
                <div v-if="testResult.error" class="text-red-500 mt-1">
                  {{ testResult.error }}
                </div>
              </div>
            </NPopover>
          </NSpace>
        </NFormItem>
      </NForm>

      <template #footer>
        <NSpace justify="end">
          <NButton @click="modalVisible = false">取消</NButton>
          <NButton type="primary" :loading="submitting" @click="submitForm">
            确认保存
          </NButton>
        </NSpace>
      </template>
    </NModal>
  </div>
</template>

<style scoped>
.section-title {
  font-size: 13px;
  font-weight: 600;
  color: #2080f0;
  margin: 12px 0 8px;
  padding-left: 6px;
  border-left: 3px solid #2080f0;
}
.font-mono {
  font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
}
.truncate {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
.flex {
  display: flex;
}
.items-center {
  align-items: center;
}
.gap-1 {
  gap: 4px;
}
.text-xs {
  font-size: 12px;
}
.text-gray-400 {
  color: #999;
}
.text-red-500 {
  color: #d03050;
}
.mt-1 {
  margin-top: 4px;
}
.mt-2 {
  margin-top: 8px;
}
.mb-3 {
  margin-bottom: 12px;
}
.sample {
  margin-top: 8px;
  padding: 8px;
  background: #f5f7fa;
  border-radius: 4px;
  font-size: 12px;
  max-height: 180px;
  overflow: auto;
  white-space: pre-wrap;
  word-break: break-all;
}
</style>
