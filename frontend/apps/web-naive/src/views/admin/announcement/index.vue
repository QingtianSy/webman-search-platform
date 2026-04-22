<script lang="ts" setup>
import type { DataTableColumns } from 'naive-ui';

import { h, onMounted, reactive, ref } from 'vue';

import {
  NButton,
  NCard,
  NDataTable,
  NDatePicker,
  NForm,
  NFormItem,
  NInput,
  NInputGroup,
  NModal,
  NPopconfirm,
  NSelect,
  NSpace,
  NTag,
  useMessage,
} from 'naive-ui';

import {
  type AdminAnnouncementApi,
  createAnnouncementApi,
  deleteAnnouncementApi,
  listAnnouncementsApi,
  updateAnnouncementApi,
} from '#/api/admin';

const message = useMessage();

const loading = ref(false);
const rows = ref<AdminAnnouncementApi.Announcement[]>([]);
const total = ref(0);
const page = ref(1);
const pageSize = ref(20);

const filter = reactive<{ keyword: string; status: '' | number }>({
  keyword: '',
  status: '',
});
const statusOptions = [
  { label: '全部', value: '' },
  { label: '启用', value: 1 },
  { label: '禁用', value: 0 },
];
const typeOptions = [
  { label: '通知', value: 'notice' },
  { label: '维护', value: 'maintenance' },
  { label: '活动', value: 'event' },
];

async function load() {
  loading.value = true;
  try {
    const data = await listAnnouncementsApi({
      keyword: filter.keyword || undefined,
      status: filter.status === '' ? undefined : filter.status,
      page: page.value,
      page_size: pageSize.value,
    });
    rows.value = data.list ?? [];
    total.value = data.total ?? 0;
  } catch {
    message.error('公告列表加载失败');
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

// ========= 新增/编辑 =========
const editorVisible = ref(false);
const editing = ref<AdminAnnouncementApi.Announcement | null>(null);
const publishAtTs = ref<null | number>(null);
const form = reactive<{
  id?: number;
  title: string;
  content: string;
  type: string;
  status: number;
}>({ title: '', content: '', type: 'notice', status: 1 });
const saving = ref(false);

function openCreate() {
  editing.value = null;
  Object.assign(form, {
    id: undefined,
    title: '',
    content: '',
    type: 'notice',
    status: 1,
  });
  publishAtTs.value = null;
  editorVisible.value = true;
}

function openEdit(row: AdminAnnouncementApi.Announcement) {
  editing.value = row;
  Object.assign(form, {
    id: row.id,
    title: row.title ?? '',
    content: row.content ?? '',
    type: row.type ?? 'notice',
    status: row.status ?? 1,
  });
  publishAtTs.value = row.publish_at ? Date.parse(row.publish_at) : null;
  editorVisible.value = true;
}

function fmtDate(ts: null | number): null | string {
  if (!ts) return null;
  const d = new Date(ts);
  const pad = (n: number) => `${n}`.padStart(2, '0');
  return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())} ${pad(d.getHours())}:${pad(d.getMinutes())}:${pad(d.getSeconds())}`;
}

async function onSave() {
  if (!form.title.trim()) {
    message.warning('标题不能为空');
    return;
  }
  saving.value = true;
  try {
    const payload = {
      title: form.title,
      content: form.content,
      type: form.type,
      status: form.status,
      publish_at: fmtDate(publishAtTs.value),
    };
    if (editing.value && form.id) {
      await updateAnnouncementApi({ id: form.id, ...payload });
      message.success('更新成功');
    } else {
      await createAnnouncementApi(payload);
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

async function onDelete(row: AdminAnnouncementApi.Announcement) {
  try {
    await deleteAnnouncementApi(row.id);
    message.success('删除成功');
    load();
  } catch {
    // interceptor
  }
}

const columns: DataTableColumns<AdminAnnouncementApi.Announcement> = [
  { title: 'ID', key: 'id', width: 70 },
  { title: '标题', key: 'title', width: 240, ellipsis: { tooltip: true } },
  {
    title: '类型',
    key: 'type',
    width: 90,
    render: (row) =>
      h(NTag, { size: 'small', type: 'info' }, () => row.type ?? '-'),
  },
  {
    title: '状态',
    key: 'status',
    width: 80,
    render: (row) =>
      row.status === 1
        ? h(NTag, { type: 'success', size: 'small' }, () => '启用')
        : h(NTag, { size: 'small' }, () => '禁用'),
  },
  { title: '发布时间', key: 'publish_at', width: 170 },
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
            default: () => '确定删除该公告？',
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
    <NCard title="公告管理">
      <template #header-extra>
        <NButton type="primary" @click="openCreate">新增公告</NButton>
      </template>

      <NSpace class="mb-4">
        <NInputGroup>
          <NInput
            v-model:value="filter.keyword"
            placeholder="标题/内容关键词"
            clearable
            style="width: 260px"
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
        :row-key="(row: AdminAnnouncementApi.Announcement) => row.id"
        :scroll-x="1100"
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
      :title="editing ? '编辑公告' : '新增公告'"
      style="width: 640px"
      :mask-closable="false"
    >
      <NForm label-placement="left" label-width="auto">
        <NFormItem label="标题" required>
          <NInput v-model:value="form.title" />
        </NFormItem>
        <NFormItem label="类型">
          <NSelect
            v-model:value="form.type"
            :options="typeOptions"
            style="width: 160px"
          />
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
        <NFormItem label="发布时间">
          <NDatePicker
            v-model:value="publishAtTs"
            type="datetime"
            clearable
            placeholder="留空 = 立即发布"
            style="width: 240px"
          />
        </NFormItem>
        <NFormItem label="正文">
          <NInput
            v-model:value="form.content"
            type="textarea"
            :autosize="{ minRows: 6, maxRows: 14 }"
            placeholder="支持纯文本，用户端原样展示"
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
