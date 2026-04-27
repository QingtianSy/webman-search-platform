<script lang="ts" setup>
import { computed, h, onMounted, ref } from 'vue';
import { useRouter } from 'vue-router';

import { useUserStore } from '@vben/stores';

import {
  NAlert,
  NButton,
  NEmpty,
  NGrid,
  NGridItem,
  NIcon,
  NSkeleton,
  NSpin,
  NTag,
  useMessage,
} from 'naive-ui';

import {
  getUserDashboardApi,
  type UserDashboardApi,
} from '#/api/user/dashboard';
import {
  derivePlanType,
  getPopularPlansApi,
  type PlanApi,
} from '#/api/user/plan';
import {
  listAnnouncementsApi,
  type AnnouncementApi,
} from '#/api/user/announcement';

const router = useRouter();
const userStore = useUserStore();
const message = useMessage();

const username = computed(
  () => userStore.userInfo?.realName ?? userStore.userInfo?.username ?? '访客',
);

// 吉祥物：优先 /assets/dashboard-mascot.svg；加载失败（如 public 被裁）退回 emoji
const mascotSrc = '/assets/dashboard-mascot.svg';
const mascotFailed = ref(false);

// A 欢迎条：按本地时间算问候
const greeting = computed(() => {
  const h = new Date().getHours();
  if (h >= 5 && h < 11) return '早上好';
  if (h >= 11 && h < 13) return '中午好';
  if (h >= 13 && h < 18) return '下午好';
  if (h >= 18 && h < 22) return '晚上好';
  return '晚安';
});

// B 指标卡
const loading = ref(false);
/** 首屏骨架屏：只在从无到有首次加载时展示，之后切 NSpin 局部刷新避免闪烁 */
const firstLoad = ref(true);
const overview = ref<UserDashboardApi.Overview | null>(null);
const overviewFailed = ref(false);

const balance = computed(() => {
  const v = overview.value?.balance;
  return v === undefined || overviewFailed.value ? '--' : `¥ ${Number(v).toFixed(2)}`;
});
const totalUsage = computed(() =>
  overviewFailed.value ? '--' : (overview.value?.total_usage ?? 0),
);

const plan = computed(() => overview.value?.current_plan);
const planName = computed(() => {
  if (overviewFailed.value) return '--';
  const n = plan.value?.name;
  return !n || n === '无套餐' ? '无套餐' : n;
});
const planExpireText = computed(() => {
  if (!plan.value || plan.value.name === '无套餐') return '暂无有效套餐';
  return plan.value.expire_at ? `有效期至 ${plan.value.expire_at}` : '永久有效';
});
const remainingQuotaText = computed(() => {
  if (overviewFailed.value) return '--';
  const p = plan.value;
  if (!p || p.name === '无套餐') return '0';
  if (p.is_unlimited) return '不限';
  return String(p.remain_quota ?? 0);
});
const remainingQuotaSub = computed(() => {
  const p = plan.value;
  if (!p || p.name === '无套餐') return '暂无套餐额度';
  return p.is_unlimited ? '无额度限制' : '本月剩余';
});

interface StatCard {
  key: string;
  label: string;
  value: number | string;
  sub: string;
  barColor: string;
  badge: string;
  badgeType: 'error' | 'info' | 'success' | 'warning';
  icon: string;
  to: string;
}

const statCards = computed<StatCard[]>(() => [
  {
    key: 'balance',
    label: '账户余额',
    value: balance.value,
    sub: '当前可用余额',
    barColor: '#d03050',
    badge: 'Balance',
    badgeType: 'error',
    icon: 'wallet',
    to: '/user/log/balance',
  },
  {
    key: 'plan',
    label: '当前套餐',
    value: planName.value,
    sub: planExpireText.value,
    barColor: '#2080f0',
    badge: 'Plan',
    badgeType: 'info',
    icon: 'clipboard',
    to: '/user/plan',
  },
  {
    key: 'remaining',
    label: '剩余额度',
    value: remainingQuotaText.value,
    sub: remainingQuotaSub.value,
    barColor: '#18a058',
    badge: 'Remaining',
    badgeType: 'success',
    icon: 'gauge',
    to: '/user/log/balance',
  },
  {
    key: 'used',
    label: '已用额度',
    value: totalUsage.value,
    sub: '累计调用次数',
    barColor: '#f0a020',
    badge: 'Used',
    badgeType: 'warning',
    icon: 'chart',
    to: '/user/log/search',
  },
]);

// C 热门套餐
const popularLoading = ref(false);
const popular = ref<PlanApi.Plan[]>([]);

async function loadOverview() {
  loading.value = true;
  overviewFailed.value = false;
  try {
    overview.value = await getUserDashboardApi();
  } catch {
    overviewFailed.value = true;
    message.error('加载首页数据失败');
  } finally {
    loading.value = false;
    firstLoad.value = false;
  }
}

async function loadPopular() {
  popularLoading.value = true;
  try {
    popular.value = await getPopularPlansApi(3);
  } catch {
    popular.value = [];
  } finally {
    popularLoading.value = false;
  }
}

// 未读公告横幅
const unreadAnnouncement = ref<AnnouncementApi.Item | null>(null);
const bannerVisible = ref(true);

async function loadUnreadAnnouncement() {
  try {
    const r = await listAnnouncementsApi({ unread: 1, limit: 1 });
    unreadAnnouncement.value = r?.list?.[0] ?? null;
  } catch {
    unreadAnnouncement.value = null;
  }
}

function go(path: string) {
  router.push(path);
}

function onPlanClick(p: PlanApi.Plan) {
  router.push({ path: '/user/plan', query: { plan_id: String(p.id) } });
}

// 套餐卡特性文案：优先 features，否则由字段拼
function planFeatures(p: PlanApi.Plan): string[] {
  if (Array.isArray(p.features) && p.features.length > 0) {
    return p.features.slice(0, 3);
  }
  const f: string[] = [];
  if (p.is_unlimited) f.push('API 调用：不限次');
  else if (p.quota) f.push(`API 调用：${p.quota} 次`);
  if (p.duration) f.push(`有效期：${p.duration} 天`);
  f.push('技术支持：标准');
  return f.slice(0, 3);
}

function planDuration(p: PlanApi.Plan): string {
  const t = derivePlanType(p);
  if (t === 'exhaustive') return '永久';
  if (p.duration) {
    const d = Number(p.duration);
    if (d >= 30 && d % 30 === 0) return `${d / 30} 个月`;
    return `${d} 天`;
  }
  return '';
}

onMounted(() => {
  loadOverview();
  // 延后 300ms 拉热门/公告避免堵塞首屏
  setTimeout(() => {
    loadPopular();
    loadUnreadAnnouncement();
  }, 300);
});

// 轻量勾选图标（避免引 iconify）
const CheckIcon = {
  render: () =>
    h(
      'svg',
      {
        viewBox: '0 0 24 24',
        width: '14',
        height: '14',
        fill: 'none',
        stroke: 'currentColor',
        'stroke-width': 2.5,
        'stroke-linecap': 'round',
        'stroke-linejoin': 'round',
      },
      [h('polyline', { points: '20 6 9 17 4 12' })],
    ),
};
</script>

<template>
  <div class="dashboard-page p-6">
    <!-- 未读公告横幅 -->
    <NAlert
      v-if="bannerVisible && unreadAnnouncement"
      type="info"
      closable
      class="mb-4"
      @close="bannerVisible = false"
    >
      <div class="flex items-center justify-between gap-3">
        <div class="truncate">
          <span class="mr-2 font-medium">新公告：</span>
          <span>{{ unreadAnnouncement.title }}</span>
        </div>
        <NButton text type="primary" @click="go('/user/announcement')">
          查看
        </NButton>
      </div>
    </NAlert>

    <!-- 首屏骨架屏：避免 loading spinner 闪烁带来的空白感；首屏后切 NSpin 局部刷新 -->
    <template v-if="firstLoad">
      <div class="welcome-bar mb-4">
        <div class="flex-1">
          <NSkeleton text :width="180" :sharp="false" />
          <NSkeleton text :width="120" :sharp="false" style="margin-top: 8px" />
        </div>
        <NSkeleton :width="100" :height="100" :sharp="false" circle />
      </div>
      <NGrid :x-gap="16" :y-gap="16" :cols="4" responsive="screen" item-responsive>
        <NGridItem v-for="i in 4" :key="i" :span="4" :md="2" :l="1">
          <div class="stat-card">
            <NSkeleton text :width="80" />
            <NSkeleton text :width="140" :height="28" style="margin-top: 12px" />
            <NSkeleton text :width="120" style="margin-top: 8px" />
          </div>
        </NGridItem>
      </NGrid>
    </template>

    <NSpin v-else :show="loading">
      <!-- A 欢迎条 -->
      <div class="welcome-bar mb-4">
        <div>
          <div class="welcome-title">{{ greeting }}，{{ username }}</div>
          <div class="welcome-sub">祝您今天工作愉快！</div>
        </div>
        <div class="mascot" aria-hidden="true">
          <img
            v-if="!mascotFailed"
            :src="mascotSrc"
            alt=""
            width="100"
            height="100"
            @error="mascotFailed = true"
          />
          <span v-else>🎓</span>
        </div>
      </div>

      <!-- B 4 色条统计卡 -->
      <NGrid :x-gap="16" :y-gap="16" :cols="4" responsive="screen" item-responsive>
        <NGridItem v-for="c in statCards" :key="c.key" :span="4" :md="2" :l="1">
          <div
            class="stat-card"
            :style="{ borderTopColor: c.barColor }"
            @click="go(c.to)"
          >
            <div class="stat-head">
              <span class="stat-label">{{ c.label }}</span>
              <NTag :type="c.badgeType" size="small" round>{{ c.badge }}</NTag>
            </div>
            <div class="stat-value">{{ c.value }}</div>
            <div class="stat-sub">{{ c.sub }}</div>
          </div>
        </NGridItem>
      </NGrid>

      <!-- C 热门套餐 -->
      <div class="mt-6 flex items-center justify-between">
        <h3 class="section-title">热门套餐</h3>
        <NTag type="error" size="small" round>限时优惠</NTag>
      </div>

      <div class="mt-3">
        <NEmpty
          v-if="!popularLoading && popular.length === 0"
          description="暂无推荐套餐"
          class="py-8"
        />
        <NGrid v-else :x-gap="16" :y-gap="16" :cols="3" responsive="screen">
          <NGridItem v-for="p in popular" :key="p.id" :span="3" :md="3" :l="1">
            <div
              class="plan-card"
              :class="{ recommended: p.is_recommended }"
              @click="onPlanClick(p)"
            >
              <span v-if="p.is_recommended" class="ribbon">推荐</span>
              <div class="plan-name">{{ p.name }}</div>
              <div class="plan-price-row">
                <span class="plan-price">¥ {{ Number(p.price).toFixed(2) }}</span>
                <span v-if="planDuration(p)" class="plan-duration"
                  >/ {{ planDuration(p) }}</span
                >
              </div>
              <ul class="plan-features">
                <li v-for="(f, i) in planFeatures(p)" :key="i">
                  <NIcon :component="CheckIcon" class="text-success" /> {{ f }}
                </li>
              </ul>
              <NButton
                block
                :type="p.is_recommended ? 'primary' : 'default'"
                class="mt-3"
              >
                立即订阅
              </NButton>
            </div>
          </NGridItem>
        </NGrid>
      </div>
    </NSpin>
  </div>
</template>

<style scoped>
.dashboard-page {
  max-width: 1400px;
  margin: 0 auto;
}

.welcome-bar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 28px 32px;
  border-radius: 8px;
  background: linear-gradient(135deg, #f5f6f8 0%, #e6f0ff 100%);
  min-height: 140px;
}
.welcome-title {
  font-size: 22px;
  font-weight: 600;
  color: #333;
}
.welcome-sub {
  margin-top: 6px;
  font-size: 14px;
  color: #666;
}
.mascot {
  font-size: 72px;
  line-height: 1;
  user-select: none;
}
.mascot img {
  display: block;
  width: 100px;
  height: 100px;
}

.stat-card {
  position: relative;
  padding: 20px 20px 18px;
  background: #fff;
  border: 1px solid #eee;
  border-top: 2px solid transparent;
  border-radius: 6px;
  cursor: pointer;
  transition:
    transform 0.2s,
    box-shadow 0.2s;
}
.stat-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.06);
}
.stat-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 8px;
}
.stat-label {
  font-size: 14px;
  color: #666;
}
.stat-value {
  font-size: 28px;
  font-weight: 700;
  color: #333;
  line-height: 1.2;
  word-break: break-all;
}
.stat-sub {
  margin-top: 6px;
  font-size: 12px;
  color: #999;
}

.section-title {
  font-size: 18px;
  font-weight: 600;
  color: #333;
  margin: 0;
}

.plan-card {
  position: relative;
  padding: 24px 20px;
  background: #fff;
  border: 1px solid #eee;
  border-radius: 8px;
  cursor: pointer;
  transition:
    transform 0.2s,
    box-shadow 0.2s,
    border-color 0.2s;
  overflow: hidden;
}
.plan-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 16px rgba(32, 128, 240, 0.12);
}
.plan-card.recommended {
  background: #ecf5ff;
  border-color: #2080f0;
}
.plan-card .ribbon {
  position: absolute;
  top: 12px;
  right: -32px;
  width: 110px;
  text-align: center;
  transform: rotate(45deg);
  background: #d03050;
  color: #fff;
  font-size: 12px;
  padding: 3px 0;
  letter-spacing: 1px;
}
.plan-name {
  font-size: 16px;
  font-weight: 600;
  text-align: center;
  color: #333;
}
.plan-price-row {
  margin-top: 12px;
  text-align: center;
}
.plan-price {
  font-size: 28px;
  font-weight: 700;
  color: #d03050;
}
.plan-duration {
  margin-left: 4px;
  font-size: 13px;
  color: #999;
}
.plan-features {
  margin: 14px 0 0;
  padding: 0;
  list-style: none;
}
.plan-features li {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 5px 0;
  font-size: 13px;
  color: #666;
}
.text-success {
  color: #18a058;
}
</style>
