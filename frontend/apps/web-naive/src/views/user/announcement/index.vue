<script lang="ts" setup>
import { computed, h, onMounted, reactive, ref } from 'vue';

import {
  NButton,
  NCard,
  NDataTable,
  NDrawer,
  NDrawerContent,
  NInput,
  NSelect,
  NSpace,
  NSpin,
  NTag,
  useMessage,
  type DataTableColumns,
} from 'naive-ui';

import {
  getAnnouncementDetailApi,
  listAnnouncementsApi,
  markAnnouncementReadApi,
  type AnnouncementApi,
} from '#/api/user/announcement';
import { usePagination } from '#/composables/usePagination';

const message = useMessage();

const filter = reactive({
  title: '',
  creator: '',
  type: undefined as number | string | undefined,
});

const typeOptions = [
  { label: '公告', value: 1 },
  { label: '活动', value: 2 },
  { label: '维护', value: 3 },
  { label: '通知', value: 4 },
];

const pg = usePagination(10);
const rows = ref<AnnouncementApi.Item[]>([]);
const loading = ref(false);

async function loadList() {
  loading.value = true;
  try {
    const r = await listAnnouncementsApi({
      title: filter.title || undefined,
      creator: filter.creator || undefined,
      type: filter.type,
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
  filter.title = '';
  filter.creator = '';
  filter.type = undefined;
  onSearch();
}

// 详情 Drawer
const drawerVisible = ref(false);
const detailLoading = ref(false);
const detail = ref<AnnouncementApi.Detail | null>(null);

async function openDetail(row: AnnouncementApi.Item) {
  drawerVisible.value = true;
  detailLoading.value = true;
  detail.value = null;
  try {
    detail.value = await getAnnouncementDetailApi(row.id);
    // 前端同步：打开后即标为已读，避免红点残留
    if (row.unread) {
      row.unread = false;
      // 后端同步（显式接口，🆕）；失败不阻塞 UI，detail 的隐式标记仍作为兜底
      markAnnouncementReadApi(row.id);
    }
  } catch {
    message.error('加载详情失败');
  } finally {
    detailLoading.value = false;
  }
}

function typeLabel(t: AnnouncementApi.Item['type']) {
  if (typeof t === 'string') return t;
  const map: Record<number, string> = {
    1: '公告',
    2: '活动',
    3: '维护',
    4: '通知',
  };
  return map[Number(t)] ?? String(t);
}

function typeColor(
  t: AnnouncementApi.Item['type'],
): 'default' | 'error' | 'info' | 'success' | 'warning' {
  const s = typeLabel(t);
  if (s === '公告') return 'info';
  if (s === '活动') return 'warning';
  if (s === '维护') return 'error';
  return 'default';
}

// 超轻量 markdown 渲染兜底（仅换行、标题、粗体、code、list、link）
function renderMarkdown(src: string): string {
  if (!src) return '';
  let s = src
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;');
  // 代码块 ```
  s = s.replace(/```([\s\S]*?)```/g, '<pre class="md-code">$1</pre>');
  // inline code
  s = s.replace(/`([^`]+)`/g, '<code>$1</code>');
  // 标题
  s = s.replace(/^### (.*)$/gm, '<h3>$1</h3>');
  s = s.replace(/^## (.*)$/gm, '<h2>$1</h2>');
  s = s.replace(/^# (.*)$/gm, '<h1>$1</h1>');
  // 粗体
  s = s.replace(/\*\*([^*]+)\*\*/g, '<strong>$1</strong>');
  // 链接
  s = s.replace(
    /\[([^\]]+)\]\(([^)]+)\)/g,
    '<a href="$2" target="_blank" rel="noopener">$1</a>',
  );
  // 列表
  s = s.replace(/^- (.*)$/gm, '<li>$1</li>');
  s = s.replace(/(<li>.*<\/li>\n?)+/g, (m) => `<ul>${m}</ul>`);
  // 段落 / 换行
  s = s
    .split(/\n{2,}/)
    .map((p) => (p.startsWith('<') ? p : `<p>${p.replace(/\n/g, '<br/>')}</p>`))
    .join('');
  return s;
}

const renderedHtml = computed(() => {
  if (!detail.value) return '';
  const fmt = detail.value.content_format;
  if (fmt === 'html') return detail.value.content;
  return renderMarkdown(detail.value.content);
});

const columns = computed<DataTableColumns<AnnouncementApi.Item>>(() => [
  {
    title: '',
    key: 'unread',
    width: 40,
    render: (row) =>
      row.unread ? h('span', { class: 'unread-dot', title: '未读' }) : null,
  },
  {
    title: '标题',
    key: 'title',
    minWidth: 280,
    render: (row) =>
      h(
        'div',
        { class: 'flex items-center gap-1' },
        [
          row.is_pinned
            ? h('span', { class: 'pin-tag', title: '置顶' }, '📌')
            : null,
          h(
            NButton,
            {
              text: true,
              type: 'primary',
              onClick: () => openDetail(row),
            },
            { default: () => row.title },
          ),
        ],
      ),
  },
  {
    title: '类型',
    key: 'type',
    width: 100,
    render: (row) =>
      h(
        NTag,
        { size: 'small', type: typeColor(row.type), round: true },
        { default: () => typeLabel(row.type) },
      ),
  },
  { title: '创建者', key: 'creator', width: 120 },
  { title: '发布时间', key: 'publish_at', width: 180 },
  { title: '阅读数', key: 'read_count', width: 100 },
  {
    title: '操作',
    key: 'actions',
    width: 100,
    render: (row) =>
      h(
        NButton,
        { size: 'small', ghost: true, onClick: () => openDetail(row) },
        { default: () => '查看' },
      ),
  },
]);

onMounted(loadList);
</script>

<template>
  <div class="p-6">
    <NCard :bordered="false" size="small" class="mb-3">
      <NSpace>
        <NInput
          v-model:value="filter.title"
          placeholder="标题关键字"
          clearable
          style="width: 200px"
        />
        <NInput
          v-model:value="filter.creator"
          placeholder="创建者"
          clearable
          style="width: 160px"
        />
        <NSelect
          v-model:value="filter.type"
          placeholder="类型"
          clearable
          style="width: 140px"
          :options="typeOptions"
        />
        <NButton type="primary" @click="onSearch">查询</NButton>
        <NButton @click="onReset">重置</NButton>
      </NSpace>
    </NCard>

    <NCard :bordered="false" size="small">
      <NDataTable
        remote
        :columns="columns"
        :data="rows"
        :loading="loading"
        :row-key="(r: AnnouncementApi.Item) => r.id"
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
      />
    </NCard>

    <NDrawer v-model:show="drawerVisible" :width="720" placement="right">
      <NDrawerContent :title="detail?.title ?? '公告详情'" closable>
        <NSpin :show="detailLoading">
          <div v-if="detail" class="detail-body">
            <div class="meta">
              <NTag size="small" :type="typeColor(detail.type)" round>
                {{ typeLabel(detail.type) }}
              </NTag>
              <span class="ml-2">{{ detail.creator ?? '系统' }}</span>
              <span class="ml-2 text-gray-400">
                {{ detail.publish_at ?? detail.created_at }}
              </span>
            </div>
            <!-- eslint-disable-next-line vue/no-v-html -->
            <div class="content" v-html="renderedHtml" />
          </div>
        </NSpin>
      </NDrawerContent>
    </NDrawer>
  </div>
</template>

<style scoped>
.unread-dot {
  display: inline-block;
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background: #d03050;
}
.pin-tag {
  font-size: 13px;
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
.ml-2 {
  margin-left: 8px;
}
.mb-3 {
  margin-bottom: 12px;
}
.text-gray-400 {
  color: #999;
}
.detail-body .meta {
  margin-bottom: 16px;
  font-size: 13px;
  color: #666;
}
.content {
  font-size: 14px;
  line-height: 1.7;
  color: #333;
}
.content :deep(h1) {
  font-size: 20px;
  font-weight: 600;
  margin: 16px 0 8px;
}
.content :deep(h2) {
  font-size: 18px;
  font-weight: 600;
  margin: 14px 0 8px;
}
.content :deep(h3) {
  font-size: 16px;
  font-weight: 600;
  margin: 12px 0 6px;
}
.content :deep(p) {
  margin: 0 0 10px;
}
.content :deep(ul) {
  padding-left: 24px;
  margin: 8px 0;
}
.content :deep(code) {
  background: #f5f7fa;
  padding: 2px 6px;
  border-radius: 3px;
  font-size: 12px;
}
.content :deep(.md-code) {
  background: #f5f7fa;
  padding: 12px;
  border-radius: 4px;
  font-size: 12px;
  overflow: auto;
}
.content :deep(a) {
  color: #2080f0;
}
</style>
