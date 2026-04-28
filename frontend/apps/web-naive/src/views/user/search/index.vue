<script lang="ts" setup>
import { computed, ref } from 'vue';

import {
  NAlert,
  NButton,
  NCard,
  NCollapseTransition,
  NEmpty,
  NInput,
  NInputGroup,
  NProgress,
  NSpin,
  NTag,
  useMessage,
} from 'naive-ui';

import { searchQueryApi, type SearchApi } from '#/api/user/search';
import { useWalletStore } from '#/store/wallet';

const message = useMessage();
const walletStore = useWalletStore();

const keyword = ref('');
const loading = ref(false);
const result = ref<SearchApi.QueryResult | null>(null);
/** 展开哪些题目的答案/解析（question_id 集合） */
const expanded = ref<Set<string>>(new Set());

// ======== 粘贴拆分批量搜索 ========
// 粘贴大段文本触发：拆出 N 条候选；用户确认后串行调用单题接口。
// 不做本地并发：后端按配额扣，前端串行一方面保节流，一方面方便中途展示进度。
interface BatchItem {
  q: string;
  status: 'done' | 'failed' | 'pending' | 'running';
  result?: SearchApi.QueryResult | null;
  error?: string;
}
const batchCandidates = ref<string[]>([]);
const batchResults = ref<BatchItem[]>([]);
const batchRunning = ref(false);
const batchCancelled = ref(false);

/**
 * 把粘贴文本按常见分隔规则拆分：
 *   1. `###`（后端 API 支持的 split 也是这个）
 *   2. 换行（两个及以上连续换行优先；单换行也允许）
 *   3. `1.` / `1、` / `(1)` 开头的题号
 * 最短 4 个字符才算一条，避免把空行/标点当题目。
 */
function splitPaste(text: string): string[] {
  if (!text) return [];
  // 先按 ### 拆
  let parts: string[] = text.includes('###') ? text.split(/###+/) : [text];
  // 再按换行拆
  parts = parts.flatMap((p) => p.split(/\r?\n+/));
  // 再按题号拆（保留题干本身，去掉"1."前缀）
  parts = parts.flatMap((p) => p.split(/(?:^|\s)(?:\(?\d{1,3}[\.、\)])\s*/));
  return parts
    .map((s) => s.trim())
    .filter((s) => s.length >= 4)
    .slice(0, 50); // 硬上限 50 条，防误粘大文本
}

function onPaste(ev: ClipboardEvent) {
  const text = ev.clipboardData?.getData('text') ?? '';
  const items = splitPaste(text);
  // 2 条以上才认为是批量意图；1 条走原流程不打扰
  if (items.length >= 2) {
    batchCandidates.value = items;
  }
}

function cancelBatch() {
  batchCandidates.value = [];
}

async function runBatch() {
  const items = batchCandidates.value.slice();
  if (items.length === 0) return;
  batchResults.value = items.map((q) => ({ q, status: 'pending' }));
  batchRunning.value = true;
  batchCancelled.value = false;
  batchCandidates.value = [];
  keyword.value = ''; // 清掉单条输入框
  result.value = null;
  try {
    for (let i = 0; i < items.length; i += 1) {
      if (batchCancelled.value) break;
      batchResults.value[i]!.status = 'running';
      try {
        const data = await searchQueryApi({ q: items[i]! });
        batchResults.value[i]!.result = data;
        batchResults.value[i]!.status = 'done';
      } catch (err: any) {
        batchResults.value[i]!.status = 'failed';
        batchResults.value[i]!.error = err?.message ?? '失败';
      }
      // 触发响应式（直接索引赋值在数组/ref 上需要手动重赋一次保险）
      batchResults.value = batchResults.value.slice();
    }
  } finally {
    batchRunning.value = false;
    walletStore.invalidate();
  }
}

function stopBatch() {
  batchCancelled.value = true;
}

const batchProgress = computed(() => {
  const total = batchResults.value.length;
  if (total === 0) return 0;
  const done = batchResults.value.filter(
    (r) => r.status === 'done' || r.status === 'failed',
  ).length;
  return Math.round((done / total) * 100);
});

const list = computed(() => result.value?.list ?? []);
const apiResults = computed(() => result.value?.api_results ?? []);
/** 是否全空：本地命中为 0 且第三方也都没 success 数据 */
const empty = computed(() => {
  if (!result.value) return false;
  if (list.value.length > 0) return false;
  return !apiResults.value.some(
    (r) => r.status === 'success' && Array.isArray(r.data) && r.data.length > 0,
  );
});

async function onSubmit() {
  const q = keyword.value.trim();
  if (!q) {
    message.warning('请输入搜索关键词');
    return;
  }
  loading.value = true;
  try {
    const data = await searchQueryApi({ q });
    result.value = data;
    expanded.value = new Set();
    // 配额会被后端按命中扣减，主动 invalidate wallet store 让下次读取重拉
    walletStore.invalidate();
    if (data.hit_count === 0) {
      message.info('未搜索到结果');
    }
  } catch (err) {
    // request 拦截器已经吐过 toast，这里只收尾
    result.value = null;
  } finally {
    loading.value = false;
  }
}

function toggleExpand(id: string) {
  if (expanded.value.has(id)) expanded.value.delete(id);
  else expanded.value.add(id);
  // 触发响应式
  expanded.value = new Set(expanded.value);
}

function optionText(o: SearchApi.QuestionOption): string {
  const prefix = (o.key ?? o.label ?? '').trim();
  return prefix ? `${prefix}. ${o.content}` : o.content;
}
</script>

<template>
  <div class="p-6">
    <NCard title="搜题" class="mb-4">
      <NInputGroup>
        <NInput
          v-model:value="keyword"
          placeholder="输入题干关键词；粘贴多条题目可一键批量搜索"
          clearable
          @keydown.enter="onSubmit"
          @paste="onPaste"
        />
        <NButton type="primary" :loading="loading" @click="onSubmit">
          搜索
        </NButton>
      </NInputGroup>

      <!-- 粘贴拆分引导条 -->
      <NAlert
        v-if="batchCandidates.length > 0"
        type="info"
        class="mt-3"
        :show-icon="false"
      >
        <div class="flex items-center justify-between gap-3">
          <span>
            检测到粘贴内容包含
            <b>{{ batchCandidates.length }}</b>
            条题目，是否批量搜索？（串行执行，按每条题目独立扣配额）
          </span>
          <div class="flex gap-2">
            <NButton size="small" type="primary" @click="runBatch">
              批量搜索
            </NButton>
            <NButton size="small" @click="cancelBatch">取消</NButton>
          </div>
        </div>
      </NAlert>

      <!-- 批量进度条 -->
      <div v-if="batchResults.length > 0" class="mt-3">
        <div class="mb-1 flex items-center justify-between text-sm">
          <span>
            批量进度
            {{ batchResults.filter((r) => r.status === 'done' || r.status === 'failed').length }}
            / {{ batchResults.length }}
          </span>
          <NButton
            v-if="batchRunning"
            size="small"
            type="warning"
            ghost
            @click="stopBatch"
          >
            停止
          </NButton>
        </div>
        <NProgress
          type="line"
          :percentage="batchProgress"
          :show-indicator="false"
          :height="6"
        />
      </div>

      <div
        v-if="result"
        class="text-muted-foreground mt-3 flex flex-wrap gap-4 text-sm"
      >
        <span>命中：{{ result.hit_count }}</span>
        <span>消耗配额：{{ result.consume_quota }}</span>
        <span v-if="result.log_no">日志号：{{ result.log_no }}</span>
      </div>
    </NCard>

    <!-- 批量结果 -->
    <template v-if="batchResults.length > 0">
      <NCard
        v-for="(b, i) in batchResults"
        :key="`batch-${i}`"
        class="mb-3"
        size="small"
      >
        <template #header>
          <div class="flex items-start gap-2">
            <span class="text-muted-foreground text-sm">#{{ i + 1 }}</span>
            <span class="flex-1 font-medium">{{ b.q }}</span>
          </div>
        </template>
        <template #header-extra>
          <NTag
            size="small"
            :type="
              b.status === 'done'
                ? 'success'
                : b.status === 'failed'
                  ? 'error'
                  : b.status === 'running'
                    ? 'info'
                    : 'default'
            "
          >
            {{ b.status }}
          </NTag>
        </template>
        <div v-if="b.status === 'pending'" class="text-muted-foreground text-sm">
          等待中…
        </div>
        <NSpin v-else-if="b.status === 'running'" size="small">
          <div class="py-3" />
        </NSpin>
        <NAlert v-else-if="b.status === 'failed'" type="error" :show-icon="false">
          查询失败：{{ b.error ?? '未知错误' }}
        </NAlert>
        <template v-else-if="b.result">
          <div class="text-muted-foreground mb-2 text-sm">
            命中 {{ b.result.hit_count }} · 消耗 {{ b.result.consume_quota }}
          </div>
          <NEmpty
            v-if="(b.result.list ?? []).length === 0"
            description="未命中"
            size="small"
          />
          <div
            v-for="item in b.result.list ?? []"
            :key="item.question_id"
            class="mb-2 rounded border p-2 text-sm"
          >
            <div class="font-medium">{{ item.stem }}</div>
            <div v-if="item.answer_text" class="text-success mt-1">
              答案：{{ item.answer_text }}
            </div>
          </div>
        </template>
      </NCard>
    </template>

    <NSpin v-else :show="loading">
      <div v-if="!result" class="text-muted-foreground p-8 text-center">
        输入关键词开始搜索
      </div>

      <NEmpty v-else-if="empty" description="未搜索到任何结果" class="py-10" />

      <template v-else>
        <NCard
          v-for="(item, idx) in list"
          :key="item.question_id"
          class="mb-3"
          size="small"
        >
          <template #header>
            <div class="flex items-start gap-2">
              <span class="text-muted-foreground text-sm">#{{ idx + 1 }}</span>
              <span class="flex-1 font-medium">{{ item.stem }}</span>
            </div>
          </template>
          <template #header-extra>
            <div class="flex items-center gap-2">
              <NTag v-if="item.type_name" size="small" type="info">
                {{ item.type_name }}
              </NTag>
              <NTag
                v-if="item.es_synced === false"
                size="small"
                type="warning"
              >
                索引同步中
              </NTag>
              <NButton size="small" @click="toggleExpand(item.question_id)">
                {{ expanded.has(item.question_id) ? '收起' : '查看答案' }}
              </NButton>
            </div>
          </template>

          <div v-if="item.options?.length" class="space-y-1 text-sm">
            <div
              v-for="(opt, optIdx) in item.options"
              :key="opt.key ?? opt.label ?? `${item.question_id}-opt-${optIdx}`"
              class="text-muted-foreground"
            >
              {{ optionText(opt) }}
            </div>
          </div>

          <NCollapseTransition :show="expanded.has(item.question_id)">
            <div class="mt-3 space-y-2 border-t pt-3">
              <div v-if="item.answer_text">
                <span class="font-medium">答案：</span>
                <span>{{ item.answer_text }}</span>
              </div>
              <div v-if="item.analysis" class="text-muted-foreground">
                <span class="font-medium">解析：</span>
                <span>{{ item.analysis }}</span>
              </div>
            </div>
          </NCollapseTransition>
        </NCard>

        <NCard
          v-for="api in apiResults"
          :key="`api-${api.source_id}`"
          class="mb-3"
          size="small"
        >
          <template #header>
            <span class="text-sm">
              第三方 · {{ api.source_name || `源${api.source_id}` }}
            </span>
          </template>
          <template #header-extra>
            <NTag
              size="small"
              :type="api.status === 'success' ? 'success' : 'error'"
            >
              {{ api.status }}
            </NTag>
          </template>
          <NAlert
            v-if="api.status !== 'success'"
            type="warning"
            :show-icon="false"
          >
            该来源无可用结果
          </NAlert>
          <pre
            v-else
            class="text-muted-foreground overflow-auto whitespace-pre-wrap text-xs"
          >{{ JSON.stringify(api.data, null, 2) }}</pre>
        </NCard>
      </template>
    </NSpin>
  </div>
</template>
