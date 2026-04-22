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
  NInputNumber,
  NModal,
  NPopconfirm,
  NSelect,
  NSpace,
  NSwitch,
  NTag,
  useMessage,
} from 'naive-ui';

import {
  type AdminPlanApi,
  createPlanApi,
  deletePlanApi,
  listAdminPlansApi,
  updatePlanApi,
} from '#/api/admin';

const message = useMessage();

const loading = ref(false);
const rows = ref<AdminPlanApi.Plan[]>([]);
const total = ref(0);
const page = ref(1);
const pageSize = ref(20);

const filter = reactive<{ keyword: string }>({ keyword: '' });

async function load() {
  loading.value = true;
  try {
    const data = await listAdminPlansApi({
      keyword: filter.keyword || undefined,
      page: page.value,
      page_size: pageSize.value,
    });
    rows.value = data.list ?? [];
    total.value = data.total ?? 0;
  } catch {
    message.error('套餐列表加载失败');
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

// ========= 编辑 =========
const editorVisible = ref(false);
const editing = ref<AdminPlanApi.Plan | null>(null);
const featuresText = ref('');
const form = reactive<{
  id?: number;
  name: string;
  code: string;
  price: string;
  duration: number;
  quota: number;
  is_unlimited: boolean;
  sort: number;
  status: number;
}>({
  name: '',
  code: '',
  price: '0.00',
  duration: 30,
  quota: 0,
  is_unlimited: false,
  sort: 0,
  status: 1,
});
const saving = ref(false);

function openCreate() {
  editing.value = null;
  Object.assign(form, {
    id: undefined,
    name: '',
    code: '',
    price: '0.00',
    duration: 30,
    quota: 0,
    is_unlimited: false,
    sort: 0,
    status: 1,
  });
  featuresText.value = '';
  editorVisible.value = true;
}

function openEdit(row: AdminPlanApi.Plan) {
  editing.value = row;
  Object.assign(form, {
    id: row.id,
    name: row.name ?? '',
    code: row.code ?? '',
    price: row.price ?? '0.00',
    duration: row.duration ?? 30,
    quota: row.quota ?? 0,
    is_unlimited: !!row.is_unlimited,
    sort: row.sort ?? 0,
    status: row.status ?? 1,
  });
  featuresText.value = row.features ?? '';
  editorVisible.value = true;
}

async function onSave() {
  if (!form.name.trim() || !form.code.trim()) {
    message.warning('名称和编码不能为空');
    return;
  }
  // features 可选 JSON 字符串；非空则尝试 parse
  let features: any;
  if (featuresText.value.trim() !== '') {
    try {
      features = JSON.parse(featuresText.value);
    } catch {
      message.warning('features 不是合法 JSON');
      return;
    }
  }
  saving.value = true;
  try {
    const payload = {
      name: form.name,
      code: form.code,
      price: form.price,
      duration: form.duration,
      quota: form.quota,
      is_unlimited: form.is_unlimited ? 1 : 0,
      sort: form.sort,
      status: form.status,
      ...(features === undefined ? {} : { features }),
    };
    if (editing.value && form.id) {
      await updatePlanApi({ id: form.id, ...payload });
      message.success('更新成功');
    } else {
      await createPlanApi(payload);
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

async function onDelete(row: AdminPlanApi.Plan) {
  try {
    await deletePlanApi(row.id);
    message.success('删除成功');
    load();
  } catch {
    // interceptor
  }
}

const columns: DataTableColumns<AdminPlanApi.Plan> = [
  { title: 'ID', key: 'id', width: 70 },
  { title: '名称', key: 'name', width: 160 },
  { title: '编码', key: 'code', width: 140 },
  {
    title: '价格',
    key: 'price',
    width: 100,
    render: (row) => `¥${row.price}`,
  },
  {
    title: '有效期(天)',
    key: 'duration',
    width: 110,
  },
  {
    title: '配额/天',
    key: 'quota',
    width: 100,
    render: (row) =>
      row.is_unlimited === 1
        ? h(NTag, { size: 'small', type: 'info' }, () => '不限量')
        : `${row.quota}`,
  },
  { title: '排序', key: 'sort', width: 70 },
  {
    title: '状态',
    key: 'status',
    width: 80,
    render: (row) =>
      row.status === 1
        ? h(NTag, { type: 'success', size: 'small' }, () => '上架')
        : h(NTag, { size: 'small' }, () => '下架'),
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
            default: () => '已订阅该套餐的用户不会被回滚，请谨慎',
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
    <NCard title="套餐管理">
      <template #header-extra>
        <NButton type="primary" @click="openCreate">新增套餐</NButton>
      </template>

      <NSpace class="mb-4">
        <NInputGroup>
          <NInput
            v-model:value="filter.keyword"
            placeholder="名称关键词"
            clearable
            style="width: 240px"
            @keydown.enter="onSearch"
          />
          <NButton type="primary" @click="onSearch">搜索</NButton>
        </NInputGroup>
        <NButton @click="onReset">重置</NButton>
      </NSpace>

      <NDataTable
        remote
        :loading="loading"
        :columns="columns"
        :data="rows"
        :row-key="(row: AdminPlanApi.Plan) => row.id"
        :scroll-x="1300"
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
      :title="editing ? '编辑套餐' : '新增套餐'"
      style="width: 640px"
      :mask-closable="false"
    >
      <NForm label-placement="left" label-width="auto">
        <NFormItem label="名称" required>
          <NInput v-model:value="form.name" />
        </NFormItem>
        <NFormItem label="编码" required>
          <NInput v-model:value="form.code" :disabled="!!editing" />
        </NFormItem>
        <NFormItem label="价格(元)">
          <NInput v-model:value="form.price" placeholder="0.00" />
        </NFormItem>
        <NFormItem label="有效期(天)">
          <NInputNumber v-model:value="form.duration" :min="1" />
        </NFormItem>
        <NFormItem label="无限量">
          <NSwitch v-model:value="form.is_unlimited" />
        </NFormItem>
        <NFormItem label="每日配额" v-if="!form.is_unlimited">
          <NInputNumber v-model:value="form.quota" :min="0" />
        </NFormItem>
        <NFormItem label="排序">
          <NInputNumber v-model:value="form.sort" :min="0" />
        </NFormItem>
        <NFormItem label="状态">
          <NSelect
            v-model:value="form.status"
            :options="[
              { label: '上架', value: 1 },
              { label: '下架', value: 0 },
            ]"
            style="width: 140px"
          />
        </NFormItem>
        <NFormItem label="features">
          <NInput
            v-model:value="featuresText"
            type="textarea"
            :autosize="{ minRows: 3, maxRows: 8 }"
            placeholder='可选 JSON，如 {"priority":"high","support":"7x24"}'
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
