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
  NSelect,
  NSpace,
  NSwitch,
  NTag,
  useMessage,
  type DataTableColumns,
  type FormInst,
  type FormRules,
} from 'naive-ui';

import {
  createApiSourceApi,
  deleteApiSourceApi,
  listApiSourcesApi,
  toggleApiSourceApi,
  updateApiSourceApi,
  type ApiSourceApi,
} from '#/api/user/api-source';
import { usePagination } from '#/composables/usePagination';

const message = useMessage();

// 筛选
const filter = reactive({ name: '', status: undefined as number | undefined });

// 分页
const pg = usePagination(10);

// 表格数据
const loading = ref(false);
const rows = ref<ApiSourceApi.Source[]>([]);

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
const form = reactive<Record<string, any>>({
  name: '',
  url: '',
  method: 'GET',
  keyword_param: 'q',
  keyword_position: 'url_param',
  type_param: '',
  type_position: 'url_param',
  option_delimiter: '###',
  option_format: '',
  headers: '',
  extra_config: '',
  data_path: 'data',
  success_code_field: 'code',
  success_code_value: '1',
  timeout: 10,
  sort_order: 0,
  status: 1,
  remark: '',
});
const editingId = ref<null | number>(null);

function resetForm() {
  form.name = '';
  form.url = '';
  form.method = 'GET';
  form.keyword_param = 'q';
  form.keyword_position = 'url_param';
  form.type_param = '';
  form.type_position = 'url_param';
  form.option_delimiter = '###';
  form.option_format = '';
  form.headers = '';
  form.extra_config = '';
  form.data_path = 'data';
  form.success_code_field = 'code';
  form.success_code_value = '1';
  form.timeout = 10;
  form.sort_order = 0;
  form.status = 1;
  form.remark = '';
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
    message: '请输入关键词参数名',
    trigger: 'blur',
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
    const payload = { ...form };
    if (modalMode.value === 'create') {
      await createApiSourceApi(payload as any);
      message.success('添加成功');
    } else if (editingId.value) {
      await updateApiSourceApi(editingId.value, payload as any);
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

// 删除
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

const columns = computed<DataTableColumns<ApiSourceApi.Source>>(() => [
  { title: 'ID', key: 'id', width: 70 },
  { title: '接口名称', key: 'name', minWidth: 140 },
  {
    title: '接口地址',
    key: 'url',
    minWidth: 260,
    ellipsis: { tooltip: true },
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
  { title: '超时(s)', key: 'timeout', width: 90 },
  { title: '排序', key: 'sort_order', width: 80 },
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
    width: 160,
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
                Object.assign(form, {
                  name: row.name ?? '',
                  url: row.url ?? '',
                  method: row.method ?? 'GET',
                  keyword_param: row.keyword_param ?? 'q',
                  keyword_position: (row as any).keyword_position ?? 'url_param',
                  type_param: (row as any).type_param ?? '',
                  type_position: (row as any).type_position ?? 'url_param',
                  option_delimiter: (row as any).option_delimiter ?? '###',
                  option_format: (row as any).option_format ?? '',
                  headers: (row as any).headers ?? '',
                  extra_config: (row as any).extra_config ?? '',
                  data_path: (row as any).data_path ?? 'data',
                  success_code_field: (row as any).success_code_field ?? 'code',
                  success_code_value: (row as any).success_code_value ?? '1',
                  timeout: row.timeout ?? 10,
                  sort_order: (row as any).sort_order ?? 0,
                  status: row.status ?? 1,
                  remark: (row as any).remark ?? '',
                });
                editingId.value = row.id;
                modalMode.value = 'edit';
                modalVisible.value = true;
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
        </NSpace>
      </template>

      <NDataTable
        remote
        :columns="columns"
        :data="rows"
        :loading="loading"
        :row-key="(r: ApiSourceApi.Source) => r.id"
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
        :scroll-x="1200"
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
          <NInput v-model:value="form.url" placeholder="https://example.com/api/search" />
        </NFormItem>
        <NFormItem label="请求方式">
          <NSelect
            v-model:value="form.method"
            :options="[{ label: 'GET', value: 'GET' }, { label: 'POST', value: 'POST' }]"
            style="width: 160px"
          />
        </NFormItem>

        <div class="section-title">参数映射</div>
        <NFormItem label="关键词参数名" path="keyword_param">
          <NInput v-model:value="form.keyword_param" placeholder="如 q / question" />
        </NFormItem>
        <NFormItem label="关键词位置">
          <NSelect
            v-model:value="form.keyword_position"
            :options="[{ label: 'URL参数', value: 'url_param' }, { label: 'Body', value: 'body' }]"
            style="width: 200px"
          />
        </NFormItem>
        <NFormItem label="类型参数名">
          <NInput v-model:value="form.type_param" placeholder="可选" />
        </NFormItem>
        <NFormItem label="类型位置">
          <NSelect
            v-model:value="form.type_position"
            :options="[{ label: 'URL参数', value: 'url_param' }, { label: 'Body', value: 'body' }]"
            style="width: 200px"
          />
        </NFormItem>
        <NFormItem label="选项分隔符">
          <NInput v-model:value="form.option_delimiter" placeholder="###" style="width: 200px" />
        </NFormItem>
        <NFormItem label="选项格式">
          <NInput v-model:value="form.option_format" placeholder="可选" />
        </NFormItem>

        <div class="section-title">请求配置</div>
        <NFormItem label="请求头">
          <NInput v-model:value="form.headers" type="textarea" :autosize="{ minRows: 2, maxRows: 5 }" placeholder='{"Authorization": "Bearer xxx"}' />
        </NFormItem>
        <NFormItem label="扩展配置">
          <NInput v-model:value="form.extra_config" type="textarea" :autosize="{ minRows: 2, maxRows: 5 }" placeholder="JSON 格式，可选" />
        </NFormItem>

        <div class="section-title">解析配置</div>
        <NFormItem label="数据路径">
          <NInput v-model:value="form.data_path" placeholder="如 data / data.list" />
        </NFormItem>
        <NFormItem label="成功码字段">
          <NInput v-model:value="form.success_code_field" placeholder="如 code" style="width: 200px" />
        </NFormItem>
        <NFormItem label="成功码值">
          <NInput v-model:value="form.success_code_value" placeholder="如 1 / 200" style="width: 200px" />
        </NFormItem>

        <div class="section-title">运行控制</div>
        <NFormItem label="超时(秒)">
          <NInputNumber v-model:value="form.timeout" :min="1" :max="60" />
        </NFormItem>
        <NFormItem label="排序">
          <NInputNumber v-model:value="form.sort_order" :min="0" :max="9999" />
        </NFormItem>
        <NFormItem label="状态">
          <NSwitch :value="form.status === 1" @update:value="(v: boolean) => (form.status = v ? 1 : 0)" />
        </NFormItem>
        <NFormItem label="备注">
          <NInput v-model:value="form.remark" type="textarea" :autosize="{ minRows: 1, maxRows: 3 }" placeholder="可选" />
        </NFormItem>
      </NForm>

      <template #footer>
        <NSpace justify="end">
          <NButton @click="modalVisible = false">取消</NButton>
          <NButton type="primary" :loading="submitting" @click="submitForm">确认保存</NButton>
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
.mb-3 {
  margin-bottom: 12px;
}
</style>
