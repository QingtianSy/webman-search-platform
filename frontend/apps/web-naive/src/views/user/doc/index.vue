<script lang="ts" setup>
import { onMounted, ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';

import {
  NAlert,
  NButton,
  NCard,
  NEmpty,
  NInput,
  NInputGroup,
  NSpin,
  NTag,
  useMessage,
} from 'naive-ui';

import {
  getDocArticleApi,
  listDocCategoriesApi,
  type UserDocApi,
} from '#/api/user/doc';

const route = useRoute();
const router = useRouter();
const message = useMessage();

const categoriesLoading = ref(false);
const categories = ref<UserDocApi.Category[]>([]);

const articleLoading = ref(false);
const article = ref<null | UserDocApi.Article>(null);
const notFound = ref(false);

// slug 既走 URL query 也走手输，URL 为准；用 watch 同步。
const slugInput = ref('');

async function loadCategories() {
  categoriesLoading.value = true;
  try {
    // 后端分页默认 1/20，这里 page_size 设大一些覆盖常见规模。
    const res = await listDocCategoriesApi({ page: 1, page_size: 100 });
    categories.value = res.list ?? [];
  } catch {
    message.error('文档分类加载失败');
  } finally {
    categoriesLoading.value = false;
  }
}

async function loadArticle(slug: string) {
  if (!slug) {
    article.value = null;
    notFound.value = false;
    return;
  }
  articleLoading.value = true;
  notFound.value = false;
  article.value = null;
  try {
    article.value = await getDocArticleApi(slug);
  } catch (err: any) {
    // 后端 40004 会被统一拦截器弹 toast；这里同时把页面切到"未找到"态。
    if (err?.response?.data?.code === 40_004) {
      notFound.value = true;
    }
  } finally {
    articleLoading.value = false;
  }
}

function openSlug() {
  const s = slugInput.value.trim();
  if (!s) {
    message.warning('请输入文档 slug');
    return;
  }
  // 写到 URL 让 watch 去加载，保证刷新/分享链接也能命中同一文档。
  router.replace({ query: { ...route.query, slug: s } });
}

function clearSlug() {
  slugInput.value = '';
  router.replace({ query: { ...route.query, slug: undefined } });
}

watch(
  () => route.query.slug,
  (slug) => {
    const s = typeof slug === 'string' ? slug : '';
    slugInput.value = s;
    loadArticle(s);
  },
  { immediate: false },
);

onMounted(() => {
  loadCategories();
  const initial = typeof route.query.slug === 'string' ? route.query.slug : '';
  slugInput.value = initial;
  loadArticle(initial);
});
</script>

<template>
  <div class="p-6">
    <NCard title="文档中心" class="mb-4">
      <NAlert type="info" :show-icon="false" class="mb-3">
        文档按 slug 直达。可通过公告/消息中的链接自动打开，也可在下方手动输入
        slug（如 <code>getting-started</code>）查看。
      </NAlert>

      <NInputGroup>
        <NInput
          v-model:value="slugInput"
          placeholder="输入文档 slug，如 getting-started"
          @keyup.enter="openSlug"
        />
        <NButton type="primary" @click="openSlug">打开</NButton>
        <NButton v-if="slugInput" @click="clearSlug">清空</NButton>
      </NInputGroup>
    </NCard>

    <div class="grid gap-4 md:grid-cols-[280px_1fr]">
      <NCard title="分类" :segmented="{ content: true }">
        <NSpin :show="categoriesLoading">
          <NEmpty
            v-if="!categoriesLoading && categories.length === 0"
            description="暂无分类"
            class="py-6"
          />
          <ul v-else class="space-y-2">
            <li
              v-for="c in categories"
              :key="c.id"
              class="border-border flex items-center justify-between border-b py-1.5"
            >
              <span class="text-sm">{{ c.name }}</span>
              <NTag size="small" :bordered="false" type="info">
                {{ c.slug }}
              </NTag>
            </li>
          </ul>
          <div class="text-muted-foreground mt-3 text-xs">
            分类下的文章列表目前仅在管理端开放（后端约束）。
          </div>
        </NSpin>
      </NCard>

      <NCard :title="article?.title ?? '文章内容'">
        <NSpin :show="articleLoading">
          <NAlert
            v-if="notFound"
            type="warning"
            title="文档不存在"
            :show-icon="false"
          >
            请确认 slug 是否正确，或稍后重试。
          </NAlert>

          <NEmpty
            v-else-if="!article"
            description="请在上方输入 slug 打开文档"
            class="py-10"
          />

          <template v-else>
            <div class="text-muted-foreground mb-3 text-xs">
              slug: <code>{{ article.slug }}</code>
              <span v-if="article.updated_at"> · 更新于 {{ article.updated_at }}</span>
            </div>
            <p v-if="article.summary" class="text-muted-foreground mb-3 text-sm">
              {{ article.summary }}
            </p>
            <!-- MVP：后端存的是 markdown 源文，暂未引入 markdown 渲染器；
                 用 pre 保留换行便于阅读，P4 再接入 markdown-it 做富文本渲染。 -->
            <pre class="bg-card whitespace-pre-wrap break-words rounded p-3 text-sm leading-relaxed">{{ article.content_md }}</pre>
          </template>
        </NSpin>
      </NCard>
    </div>
  </div>
</template>
