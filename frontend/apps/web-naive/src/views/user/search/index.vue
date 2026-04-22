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
  return `${o.key}. ${o.content}`;
}
</script>

<template>
  <div class="p-6">
    <NCard title="搜题" class="mb-4">
      <NInputGroup>
        <NInput
          v-model:value="keyword"
          placeholder="输入题干关键词"
          clearable
          @keydown.enter="onSubmit"
        />
        <NButton type="primary" :loading="loading" @click="onSubmit">
          搜索
        </NButton>
      </NInputGroup>
      <div
        v-if="result"
        class="text-muted-foreground mt-3 flex flex-wrap gap-4 text-sm"
      >
        <span>命中：{{ result.hit_count }}</span>
        <span>消耗配额：{{ result.consume_quota }}</span>
        <span v-if="result.log_no">日志号：{{ result.log_no }}</span>
      </div>
    </NCard>

    <NSpin :show="loading">
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
              v-for="opt in item.options"
              :key="opt.key"
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
