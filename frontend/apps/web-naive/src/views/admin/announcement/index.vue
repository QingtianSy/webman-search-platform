<script lang="ts" setup>
// 管理端 · 公告管理。docs/07 §3.2.9。
// 扩展：Markdown 正文（简易编辑器 + 预览 Tab）/ 预览 Modal / 目标用户（全部/付费/VIP）/ 发布时间调度
// bytemd 未安装 → 采用 NInput textarea + 预览切换 + 极简 markdown→html 兜底（保留换行/粗体/链接/代码）
import type { DataTableColumns } from 'naive-ui';

import { computed, h, onMounted, reactive, ref } from 'vue';

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
  NTabs,
  NTabPane,
  NTag,
  useMessage,
} from 'naive-ui';

import {
  type AdminAnnouncementApi,
  createAnnouncementApi,
  deleteAnnouncementApi,
  listAdminAnnouncementsApi,
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
const targetOptions = [
  { label: '全部用户', value: 'all' },
  { label: '付费用户', value: 'paid' },
  { label: 'VIP 用户', value: 'vip' },
];

async function load() {
  loading.value = true;
  try {
    const data = await listAdminAnnouncementsApi({
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

// ========= 极简 markdown → html（兜底，bytemd 未装）=========
function renderMarkdown(src: string): string {
  if (!src) return '';
  // 转义 HTML
  let html = src
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;');

  // 代码块 ```
  html = html.replaceAll(
    /```([\s\S]*?)```/g,
    (_m, code) =>
      `<pre style="background:#f5f5f5;padding:12px;border-radius:6px;overflow:auto"><code>${code}</code></pre>`,
  );
  // 行内代码 `x`
  html = html.replaceAll(
    /`([^`\n]+)`/g,
    '<code style="background:#f5f5f5;padding:2px 6px;border-radius:4px">$1</code>',
  );
  // 标题 ## / ###
  html = html.replaceAll(
    /^### (.*)$/gm,
    '<h3 style="margin:12px 0 6px;font-size:16px;font-weight:600">$1</h3>',
  );
  html = html.replaceAll(
    /^## (.*)$/gm,
    '<h2 style="margin:16px 0 8px;font-size:18px;font-weight:600">$1</h2>',
  );
  html = html.replaceAll(
    /^# (.*)$/gm,
    '<h1 style="margin:18px 0 10px;font-size:22px;font-weight:700">$1</h1>',
  );
  // 粗体 **x**
  html = html.replaceAll(/\*\*([^*]+)\*\*/g, '<strong>$1</strong>');
  // 斜体 *x*
  html = html.replaceAll(/\*([^*]+)\*/g, '<em>$1</em>');
  // 链接 [text](url)
  html = html.replaceAll(
    /\[([^\]]+)\]\(([^)]+)\)/g,
    '<a href="$2" target="_blank" style="color:#2080f0;text-decoration:underline">$1</a>',
  );
  // 换行
  html = html.replaceAll('\n', '<br/>');
  return html;
}

// ========= 新增/编辑 =========
const editorVisible = ref(false);
const editing = ref<AdminAnnouncementApi.Announcement | null>(null);
const editorTab = ref<'edit' | 'preview'>('edit');
const publishAtTs = ref<null | number>(null);
const form = reactive<{
  id?: number;
  title: string;
  content: string;
  type: string;
  status: number;
  target: string;
}>({ title: '', content: '', type: 'notice', status: 1, target: 'all' });
const saving = ref(false);

const previewHtml = computed(() => renderMarkdown(form.content));

function openCreate() {
  editing.value = null;
  Object.assign(form, {
    id: undefined,
    title: '',
    content: '',
    type: 'notice',
    status: 1,
    target: 'all',
  });
  publishAtTs.value = null;
  editorTab.value = 'edit';
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
    target: (row as any).target ?? 'all',
  });
  publishAtTs.value = row.publish_at ? Date.parse(row.publish_at) : null;
  editorTab.value = 'edit';
  editorVisible.value = true;
}

// 查看预览（列表行）
const previewVisible = ref(false);
const previewRow = ref<AdminAnnouncementApi.Announcement | null>(null);
function openPreview(row: AdminAnnouncementApi.Announcement) {
  previewRow.value = row;
  previewVisible.value = true;
}
const rowPreviewHtml = computed(() =>
  renderMarkdown(previewRow.value?.content ?? ''),
);

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
    const payload: any = {
      title: form.title,
      content: form.content,
      type: form.type,
      status: form.status,
      target: form.target,
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

const typeTagMap: Record<string, 'success' | 'info' | 'warning'> = {
  notice: 'info',
  maintenance: 'warning',
  event: 'success',
};

const columns: DataTableColumns<AdminAnnouncementApi.Announcement> = [
  { title: 'ID', key: 'id', width: 70 },
  { title: '标题', key: 'title', width: 240, ellipsis: { tooltip: true } },
  {
    title: '类型',
    key: 'type',
    width: 90,
    render: (row) =>
      h(
        NTag,
        { size: 'small', type: typeTagMap[row.type ?? 'notice'] ?? 'info' },
        () => row.type ?? '-',
      ),
  },
  {
    title: '目标',
    key: 'target',
    width: 100,
    render: (row) => (row as any).target ?? 'all',
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
    width: 220,
    fixed: 'right',
    render: (row) =>
      h(NSpace, { size: 'small' }, () => [
        h(
          NButton,
          {
            size: 'small',
            quaternary: true,
            onClick: () => openPreview(row),
          },
          () => '预览',
        ),
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
          NPopconfirm,
          { onPositiveClick: () => onDelete(row) },
          {
            default: () => '确定删除该公告？',
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

    <!-- 新增 / 编辑 -->
    <NModal
      v-model:show="editorVisible"
      preset="card"
      :title="editing ? '编辑公告' : '新增公告'"
      style="width: 820px"
      :mask-closable="false"
    >
      <NForm label-placement="left" label-width="auto">
        <NFormItem label="标题" required>
          <NInput v-model:value="form.title" placeholder="必填" />
        </NFormItem>
        <NFormItem label="类型">
          <NSelect
            v-model:value="form.type"
            :options="typeOptions"
            style="width: 160px"
          />
        </NFormItem>
        <NFormItem label="目标用户">
          <NSelect
            v-model:value="form.target"
            :options="targetOptions"
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
        <NFormItem label="正文 (Markdown)">
          <div class="w-full">
            <NTabs v-model:value="editorTab" type="line" size="small">
              <NTabPane name="edit" tab="编辑">
                <NInput
                  v-model:value="form.content"
                  type="textarea"
                  :autosize="{ minRows: 10, maxRows: 20 }"
                  placeholder="支持 **粗体** / *斜体* / `code` / [链接](url) / # 标题 / ```块```"
                />
                <div class="text-xs text-muted-foreground mt-1">
                  约定：bytemd 未接入；切换到"预览"看渲染效果。
                </div>
              </NTabPane>
              <NTabPane name="preview" tab="预览">
                <div
                  class="p-3 border rounded min-h-[260px] text-sm leading-relaxed"
                  v-html="previewHtml"
                />
              </NTabPane>
            </NTabs>
          </div>
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

    <!-- 行预览 Modal -->
    <NModal
      v-model:show="previewVisible"
      preset="card"
      :title="previewRow?.title ?? '预览'"
      style="width: 720px"
    >
      <div class="mb-2 flex gap-2 items-center">
        <NTag
          size="small"
          :type="typeTagMap[previewRow?.type ?? 'notice'] ?? 'info'"
        >
          {{ previewRow?.type ?? '-' }}
        </NTag>
        <span class="text-xs text-muted-foreground">
          发布时间：{{ previewRow?.publish_at ?? '立即' }}
        </span>
      </div>
      <div
        class="p-3 border rounded text-sm leading-relaxed"
        v-html="rowPreviewHtml"
      />
    </NModal>
  </div>
</template>
