<script lang="ts" setup>
// 管理端 · 套餐管理。docs/07 §3.2.8。
// 扩展：plan_type 联动（unlimited/limited/exhaustive）、推荐 switch、复制、销量列、上下架软删
// 约定：plan_type 写入 features.plan_type；is_recommended 写入 features.is_recommended（后端 BillingController 如此读取）
import type { DataTableColumns } from 'naive-ui';

import { computed, h, onMounted, reactive, ref } from 'vue';

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

const filter = reactive<{ keyword: string; plan_type: string }>({
  keyword: '',
  plan_type: '',
});

const planTypeOptions = [
  { label: '全部类型', value: '' },
  { label: '不限量', value: 'unlimited' },
  { label: '限量', value: 'limited' },
  { label: '用完即止', value: 'exhaustive' },
];

function parseFeatures(plan: AdminPlanApi.Plan): Record<string, any> {
  if (!plan.features) return {};
  try {
    return JSON.parse(plan.features);
  } catch {
    return {};
  }
}

async function load() {
  loading.value = true;
  try {
    const data = await listAdminPlansApi({
      keyword: filter.keyword || undefined,
      page: page.value,
      page_size: pageSize.value,
    });
    let list = data.list ?? [];
    // plan_type 前端过滤（后端 admin 路由未给 plan_type 过滤参数）
    if (filter.plan_type) {
      list = list.filter((p) => {
        const f = parseFeatures(p);
        if (f.plan_type) return f.plan_type === filter.plan_type;
        // 按 code 前缀兜底
        return p.code.startsWith(`${filter.plan_type}_`);
      });
    }
    rows.value = list;
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
  filter.plan_type = '';
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
const form = reactive<{
  id?: number;
  name: string;
  code: string;
  plan_type: 'unlimited' | 'limited' | 'exhaustive';
  price: string;
  duration: number;
  quota: number;
  is_unlimited: boolean;
  is_recommended: boolean;
  sort: number;
  status: number;
  features_extra: string; // 其余 features 的 JSON（不含 plan_type / is_recommended）
}>({
  name: '',
  code: '',
  plan_type: 'limited',
  price: '0.00',
  duration: 30,
  quota: 0,
  is_unlimited: false,
  is_recommended: false,
  sort: 0,
  status: 1,
  features_extra: '',
});
const saving = ref(false);

const showQuota = computed(
  () => form.plan_type === 'limited' || form.plan_type === 'exhaustive',
);
const showDuration = computed(
  () => form.plan_type !== 'exhaustive', // 用完即止类不依赖有效期
);

function openCreate() {
  editing.value = null;
  Object.assign(form, {
    id: undefined,
    name: '',
    code: '',
    plan_type: 'limited',
    price: '0.00',
    duration: 30,
    quota: 100,
    is_unlimited: false,
    is_recommended: false,
    sort: 0,
    status: 1,
    features_extra: '',
  });
  editorVisible.value = true;
}

function openEdit(row: AdminPlanApi.Plan) {
  editing.value = row;
  const f = parseFeatures(row);
  const planType =
    f.plan_type ??
    (row.is_unlimited === 1
      ? 'unlimited'
      : row.code.startsWith('exhaustive_')
        ? 'exhaustive'
        : 'limited');
  // 去除已知字段后剩余 features
  const { plan_type, is_recommended, ...rest } = f;
  void plan_type;
  void is_recommended;
  Object.assign(form, {
    id: row.id,
    name: row.name ?? '',
    code: row.code ?? '',
    plan_type: planType,
    price: row.price ?? '0.00',
    duration: row.duration ?? 30,
    quota: row.quota ?? 0,
    is_unlimited: !!row.is_unlimited,
    is_recommended: !!f.is_recommended,
    sort: row.sort ?? 0,
    status: row.status ?? 1,
    features_extra:
      Object.keys(rest).length > 0 ? JSON.stringify(rest, null, 2) : '',
  });
  editorVisible.value = true;
}

function openClone(row: AdminPlanApi.Plan) {
  openEdit(row);
  editing.value = null;
  form.id = undefined;
  form.name = `${form.name}-副本`;
  form.code = `${form.code}_copy`;
}

async function onSave() {
  if (!form.name.trim() || !form.code.trim()) {
    message.warning('名称和编码不能为空');
    return;
  }
  let extra: Record<string, any> = {};
  if (form.features_extra.trim() !== '') {
    try {
      extra = JSON.parse(form.features_extra);
      if (typeof extra !== 'object' || Array.isArray(extra)) {
        throw new TypeError('not object');
      }
    } catch {
      message.warning('features 扩展必须为合法 JSON 对象');
      return;
    }
  }
  const features = {
    ...extra,
    plan_type: form.plan_type,
    is_recommended: form.is_recommended ? 1 : 0,
  };
  saving.value = true;
  try {
    const payload = {
      name: form.name,
      code: form.code,
      price: form.price,
      duration: form.duration,
      quota: form.is_unlimited ? 0 : form.quota,
      is_unlimited: form.is_unlimited ? 1 : 0,
      sort: form.sort,
      status: form.status,
      features,
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

// 上架/下架（软删）
async function onToggleShelf(row: AdminPlanApi.Plan) {
  try {
    await updatePlanApi({ id: row.id, status: row.status === 1 ? 0 : 1 });
    message.success(row.status === 1 ? '已下架' : '已上架');
    load();
  } catch {
    // interceptor
  }
}

const planTypeTag: Record<string, { type: 'success' | 'warning' | 'info'; text: string }> = {
  unlimited: { type: 'success', text: '不限量' },
  limited: { type: 'info', text: '限量' },
  exhaustive: { type: 'warning', text: '用完即止' },
};

const columns: DataTableColumns<AdminPlanApi.Plan> = [
  { title: 'ID', key: 'id', width: 70 },
  { title: '名称', key: 'name', width: 160, ellipsis: { tooltip: true } },
  { title: '编码', key: 'code', width: 140 },
  {
    title: '类型',
    key: 'plan_type',
    width: 110,
    render: (row) => {
      const f = parseFeatures(row);
      const pt =
        f.plan_type ??
        (row.is_unlimited === 1
          ? 'unlimited'
          : row.code.startsWith('exhaustive_')
            ? 'exhaustive'
            : 'limited');
      const tag = planTypeTag[pt];
      return tag
        ? h(NTag, { size: 'small', type: tag.type }, () => tag.text)
        : pt;
    },
  },
  { title: '价格', key: 'price', width: 100, render: (r) => `¥${r.price}` },
  { title: '有效期', key: 'duration', width: 100, render: (r) => `${r.duration}天` },
  {
    title: '配额',
    key: 'quota',
    width: 100,
    render: (r) =>
      r.is_unlimited === 1
        ? h(NTag, { size: 'small', type: 'info' }, () => '不限')
        : `${r.quota}`,
  },
  {
    title: '推荐',
    key: 'is_recommended',
    width: 80,
    render: (r) =>
      parseFeatures(r).is_recommended
        ? h(NTag, { size: 'small', type: 'warning' }, () => '★')
        : '-',
  },
  {
    title: '销量',
    key: 'sales',
    width: 80,
    // 后端暂未返销量，先占位（Phase 2 末尾补 plan.stats）
    render: () =>
      h('span', { class: 'text-muted-foreground text-xs' }, () => '—'),
  },
  { title: '排序', key: 'sort', width: 70 },
  {
    title: '状态',
    key: 'status',
    width: 100,
    render: (row) =>
      h(NSwitch, {
        size: 'small',
        value: row.status === 1,
        onUpdateValue: () => onToggleShelf(row),
        checkedValue: true,
        uncheckedValue: false,
      }),
  },
  { title: '更新', key: 'updated_at', width: 170 },
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
            onClick: () => openEdit(row),
          },
          () => '编辑',
        ),
        h(
          NButton,
          { size: 'small', quaternary: true, onClick: () => openClone(row) },
          () => '复制',
        ),
        h(
          NPopconfirm,
          { onPositiveClick: () => onDelete(row) },
          {
            default: () => '已订阅该套餐的用户不会被回滚，请谨慎',
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
        <NSelect
          v-model:value="filter.plan_type"
          :options="planTypeOptions"
          style="width: 140px"
        />
        <NButton @click="onReset">重置</NButton>
      </NSpace>

      <NDataTable
        remote
        :loading="loading"
        :columns="columns"
        :data="rows"
        :row-key="(row: AdminPlanApi.Plan) => row.id"
        :scroll-x="1500"
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
      style="width: 680px"
      :mask-closable="false"
    >
      <NForm label-placement="left" label-width="auto">
        <NFormItem label="名称" required>
          <NInput v-model:value="form.name" />
        </NFormItem>
        <NFormItem label="编码" required>
          <NInput v-model:value="form.code" :disabled="!!editing" />
        </NFormItem>
        <NFormItem label="类型" required>
          <NSelect
            :value="form.plan_type"
            :options="planTypeOptions.filter((o) => o.value !== '')"
            style="width: 160px"
            @update:value="(v) => {
              form.plan_type = v;
              form.is_unlimited = v === 'unlimited';
            }"
          />
        </NFormItem>
        <NFormItem label="价格(元)">
          <NInput v-model:value="form.price" placeholder="0.00" />
        </NFormItem>
        <NFormItem v-if="showDuration" label="有效期(天)">
          <NInputNumber v-model:value="form.duration" :min="1" />
        </NFormItem>
        <NFormItem v-if="showQuota" :label="form.plan_type === 'exhaustive' ? '总配额' : '每日配额'">
          <NInputNumber v-model:value="form.quota" :min="0" />
        </NFormItem>
        <NFormItem label="推荐">
          <NSwitch v-model:value="form.is_recommended" />
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
        <NFormItem label="features 扩展">
          <NInput
            v-model:value="form.features_extra"
            type="textarea"
            :autosize="{ minRows: 3, maxRows: 8 }"
            placeholder='可选 JSON 对象，如 {"priority":"high","support":"7x24"}；plan_type/is_recommended 系统自动写入'
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
