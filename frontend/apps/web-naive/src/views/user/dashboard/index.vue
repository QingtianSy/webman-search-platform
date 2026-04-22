<script lang="ts" setup>
import { computed, onMounted, ref } from 'vue';
import { useRouter } from 'vue-router';

import { useUserStore } from '@vben/stores';

import {
  NAlert,
  NButton,
  NCard,
  NEmpty,
  NList,
  NListItem,
  NSpin,
  NStatistic,
  NTag,
  NThing,
  useMessage,
} from 'naive-ui';

import {
  getUserDashboardApi,
  type UserDashboardApi,
} from '#/api/user/dashboard';

const router = useRouter();
const userStore = useUserStore();
const message = useMessage();

const realName = computed(() => userStore.userInfo?.realName ?? '访客');

const loading = ref(false);
const overview = ref<UserDashboardApi.Overview | null>(null);

const balance = computed(() => Number(overview.value?.balance ?? 0));
const todayUsage = computed(() => overview.value?.today_usage ?? 0);
const totalUsage = computed(() => overview.value?.total_usage ?? 0);

const plan = computed(() => overview.value?.current_plan);
const remainingQuotaText = computed(() => {
  const p = plan.value;
  if (!p || p.name === '无套餐') return '—';
  if (p.is_unlimited) return '不限';
  return String(p.remain_quota ?? 0);
});

const announcements = computed(() => overview.value?.announcements ?? []);

async function load() {
  loading.value = true;
  try {
    overview.value = await getUserDashboardApi();
  } catch {
    message.error('加载首页数据失败');
  } finally {
    loading.value = false;
  }
}

function go(path: string) {
  router.push(path);
}

onMounted(load);
</script>

<template>
  <div class="p-6">
    <NSpin :show="loading">
      <NCard class="mb-4">
        <template #header>
          <div class="flex items-center gap-2">
            <span class="text-xl font-semibold">欢迎回来，{{ realName }}</span>
          </div>
        </template>
        <template #header-extra>
          <NButton size="small" :loading="loading" @click="load">刷新</NButton>
        </template>

        <div class="grid gap-4 md:grid-cols-4">
          <NStatistic label="可用余额" :value="balance" />
          <NStatistic label="今日搜题" :value="todayUsage" />
          <NStatistic label="剩余额度" :value="remainingQuotaText" />
          <NStatistic label="累计使用" :value="totalUsage" />
        </div>
      </NCard>

      <div class="grid gap-4 md:grid-cols-2">
        <NCard title="当前套餐">
          <NAlert
            v-if="!plan || plan.name === '无套餐'"
            type="warning"
            :show-icon="false"
          >
            暂无活跃套餐。可前往钱包页或联系管理员分配。
          </NAlert>
          <template v-else>
            <div class="mb-3 flex items-center gap-2">
              <span class="text-lg font-medium">{{ plan.name }}</span>
              <NTag v-if="plan.is_unlimited" type="success" size="small">
                不限次
              </NTag>
            </div>
            <div class="text-muted-foreground space-y-1 text-sm">
              <div>剩余额度：{{ remainingQuotaText }}</div>
              <div>到期时间：{{ plan.expire_at ?? '永久' }}</div>
            </div>
          </template>
          <template #footer>
            <div class="flex gap-2">
              <NButton size="small" @click="go('/user/wallet')">
                前往钱包
              </NButton>
              <NButton size="small" type="primary" @click="go('/user/search')">
                去搜题
              </NButton>
              <NButton size="small" @click="go('/user/api-key')">
                API Key
              </NButton>
            </div>
          </template>
        </NCard>

        <NCard title="最新公告">
          <NEmpty
            v-if="announcements.length === 0"
            description="暂无公告"
            class="py-6"
          />
          <NList
            v-else
            hoverable
            clickable
            :show-divider="true"
            class="max-h-80 overflow-auto"
          >
            <NListItem
              v-for="a in announcements.slice(0, 10)"
              :key="a.id"
              @click="go('/user/dashboard')"
            >
              <NThing>
                <template #header>
                  <span class="text-sm font-medium">{{ a.title }}</span>
                </template>
                <template #description>
                  <span class="text-muted-foreground text-xs">
                    {{ a.publish_at ?? a.created_at ?? '' }}
                  </span>
                </template>
              </NThing>
            </NListItem>
          </NList>
        </NCard>
      </div>
    </NSpin>
  </div>
</template>
