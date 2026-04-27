<script lang="ts" setup>
import { computed, onMounted, ref } from 'vue';
import { useRouter } from 'vue-router';

import {
  NAlert,
  NButton,
  NCard,
  NEmpty,
  NModal,
  NRadio,
  NRadioGroup,
  NSpace,
  NSpin,
  NTabs,
  NTabPane,
  NTag,
  useMessage,
} from 'naive-ui';

import {
  getPlanListByTypeApi,
  type PlanApi,
} from '#/api/user/plan';
import { getCurrentPlanApi, type WalletApi } from '#/api/user/wallet';
import { createOrderApi } from '#/api/user/order';
import {
  getPaymentMethodsApi,
  type PaymentApi,
} from '#/api/user/payment';
import { useWalletStore } from '#/store/wallet';
import PlanCard from '#/components/plan-card.vue';

const router = useRouter();
const message = useMessage();
const walletStore = useWalletStore();

// 左侧「我的套餐」
const currentLoading = ref(false);
const current = ref<WalletApi.CurrentPlan | null>(null);

const hasPlan = computed(() => {
  const n = current.value?.name;
  return !!n && n !== '无套餐';
});

const quotaText = computed(() => {
  if (!current.value) return '0';
  if (current.value.is_unlimited) return '不限';
  return String(current.value.remain_quota ?? 0);
});

const expireText = computed(() => {
  if (!current.value) return '--';
  return current.value.expire_at ?? '永久';
});

async function loadCurrent() {
  currentLoading.value = true;
  try {
    current.value = await getCurrentPlanApi();
  } catch {
    current.value = null;
  } finally {
    currentLoading.value = false;
  }
}

// 右侧 Tabs：三类套餐
type TabKey = PlanApi.PlanType;
const activeTab = ref<TabKey>('unlimited');

const tabLoading = ref<Record<TabKey, boolean>>({
  unlimited: false,
  limited: false,
  exhaustive: false,
});
const tabPlans = ref<Record<TabKey, PlanApi.Plan[]>>({
  unlimited: [],
  limited: [],
  exhaustive: [],
});

async function loadTab(type: TabKey) {
  if (tabPlans.value[type].length > 0) return;
  tabLoading.value[type] = true;
  try {
    const list = await getPlanListByTypeApi(type);
    // 本地排序：推荐在前，sort 次之，价格降序
    tabPlans.value[type] = [...list].sort((a, b) => {
      const ra = a.is_recommended ? 1 : 0;
      const rb = b.is_recommended ? 1 : 0;
      if (ra !== rb) return rb - ra;
      const sa = Number(a.sort ?? 0);
      const sb = Number(b.sort ?? 0);
      if (sa !== sb) return sb - sa;
      return Number(b.price) - Number(a.price);
    });
  } finally {
    tabLoading.value[type] = false;
  }
}

function onTabChange(key: TabKey) {
  activeTab.value = key;
  loadTab(key);
}

// 购买 Modal
const buyModalVisible = ref(false);
const selectedPlan = ref<PlanApi.Plan | null>(null);
const payMethods = ref<PaymentApi.Method[]>([]);
const selectedPayMethod = ref<string>('');
const payMethodsLoading = ref(false);
const submitting = ref(false);

async function loadPayMethods() {
  payMethodsLoading.value = true;
  try {
    payMethods.value = await getPaymentMethodsApi();
    // 默认选第一个启用的
    const first = payMethods.value.find((m) => m.enabled);
    if (first) selectedPayMethod.value = first.code;
  } finally {
    payMethodsLoading.value = false;
  }
}

function onPlanAction(plan: PlanApi.Plan) {
  selectedPlan.value = plan;
  selectedPayMethod.value = '';
  buyModalVisible.value = true;
  loadPayMethods();
}

async function onConfirmBuy() {
  if (!selectedPlan.value) return;
  if (!selectedPayMethod.value) {
    message.warning('请选择支付方式');
    return;
  }
  submitting.value = true;
  try {
    const r = await createOrderApi({
      order_type: 'plan',
      plan_id: Number(selectedPlan.value.id),
      pay_method: selectedPayMethod.value,
    });
    // 订单创建后，钱包/套餐状态即将变动，清 TTL，避免回到 dashboard 仍显示旧值
    walletStore.invalidate();
    buyModalVisible.value = false;
    if (r?.pay_url) {
      window.location.href = r.pay_url;
    } else {
      // 站内跳充值流程继续
      router.push({
        path: '/user/recharge',
        query: {
          order_id: String(r?.order_id ?? ''),
          order_no: r?.out_trade_no ?? '',
        },
      });
    }
  } catch {
    message.error('下单失败，请稍后重试');
  } finally {
    submitting.value = false;
  }
}

function renewCurrent() {
  // 续费当前套餐：按名称在三个 Tab 里找，找不到则提示
  const name = current.value?.name;
  if (!name) {
    message.info('暂无可续费的套餐');
    return;
  }
  const all = [
    ...tabPlans.value.unlimited,
    ...tabPlans.value.limited,
    ...tabPlans.value.exhaustive,
  ];
  const match = all.find((p) => p.name === name);
  if (match) {
    onPlanAction(match);
  } else {
    // Tabs 可能还没全加载，先强制都加载一次
    Promise.all([
      loadTab('unlimited'),
      loadTab('limited'),
      loadTab('exhaustive'),
    ]).then(() => {
      const all2 = [
        ...tabPlans.value.unlimited,
        ...tabPlans.value.limited,
        ...tabPlans.value.exhaustive,
      ];
      const m2 = all2.find((p) => p.name === name);
      if (m2) onPlanAction(m2);
      else message.info('当前套餐已下架，请选择其他套餐');
    });
  }
}

function isCurrentActive(p: PlanApi.Plan) {
  return !!current.value?.name && current.value.name === p.name;
}

onMounted(() => {
  loadCurrent();
  loadTab('unlimited');
});
</script>

<template>
  <div class="plan-page p-6">
    <div class="plan-layout">
      <!-- 左：我的套餐 -->
      <aside class="plan-side">
        <NCard title="我的套餐" :bordered="false" size="small">
          <NSpin :show="currentLoading">
            <div v-if="hasPlan" class="my-plan">
              <div class="my-plan-name">{{ current?.name }}</div>
              <div class="my-plan-meta">
                <div>
                  <span class="label">剩余额度</span>
                  <span class="value">{{ quotaText }}</span>
                </div>
                <div>
                  <span class="label">到期时间</span>
                  <span class="value">{{ expireText }}</span>
                </div>
              </div>
              <NButton type="primary" block class="mt-3" @click="renewCurrent">
                续费
              </NButton>
            </div>
            <NEmpty v-else description="暂无有效套餐" class="py-6">
              <template #extra>
                <NTag type="warning" size="small" round>立即选购享专属优惠</NTag>
              </template>
            </NEmpty>
          </NSpin>
        </NCard>
      </aside>

      <!-- 右：套餐列表 -->
      <main class="plan-main">
        <NCard :bordered="false" size="small">
          <NTabs
            v-model:value="activeTab"
            type="line"
            animated
            @update:value="onTabChange"
          >
            <NTabPane name="unlimited" tab="不限次套餐">
              <NSpin :show="tabLoading.unlimited">
                <NEmpty
                  v-if="!tabLoading.unlimited && tabPlans.unlimited.length === 0"
                  description="暂无套餐"
                  class="py-12"
                />
                <div v-else class="plan-grid">
                  <PlanCard
                    v-for="p in tabPlans.unlimited"
                    :key="p.id"
                    :plan="p"
                    :active="isCurrentActive(p)"
                    :action-text="isCurrentActive(p) ? '续费' : '立即购买'"
                    @action="onPlanAction"
                  />
                </div>
              </NSpin>
            </NTabPane>
            <NTabPane name="limited" tab="限次套餐">
              <NSpin :show="tabLoading.limited">
                <NEmpty
                  v-if="!tabLoading.limited && tabPlans.limited.length === 0"
                  description="暂无套餐"
                  class="py-12"
                />
                <div v-else class="plan-grid">
                  <PlanCard
                    v-for="p in tabPlans.limited"
                    :key="p.id"
                    :plan="p"
                    :active="isCurrentActive(p)"
                    :action-text="isCurrentActive(p) ? '续费' : '立即购买'"
                    @action="onPlanAction"
                  />
                </div>
              </NSpin>
            </NTabPane>
            <NTabPane name="exhaustive" tab="用完即止套餐">
              <NSpin :show="tabLoading.exhaustive">
                <NEmpty
                  v-if="!tabLoading.exhaustive && tabPlans.exhaustive.length === 0"
                  description="暂无套餐"
                  class="py-12"
                />
                <div v-else class="plan-grid">
                  <PlanCard
                    v-for="p in tabPlans.exhaustive"
                    :key="p.id"
                    :plan="p"
                    :active="isCurrentActive(p)"
                    :action-text="isCurrentActive(p) ? '续费' : '立即购买'"
                    @action="onPlanAction"
                  />
                </div>
              </NSpin>
            </NTabPane>
          </NTabs>
        </NCard>
      </main>
    </div>

    <!-- 购买 Modal：选支付方式 -->
    <NModal
      v-model:show="buyModalVisible"
      preset="card"
      title="确认购买"
      style="width: 480px"
      :mask-closable="!submitting"
      :close-on-esc="!submitting"
    >
      <div v-if="selectedPlan" class="buy-body">
        <div class="buy-plan">
          <div class="buy-plan-name">{{ selectedPlan.name }}</div>
          <div class="buy-plan-price">
            ¥ {{ Number(selectedPlan.price).toFixed(2) }}
          </div>
        </div>

        <div class="buy-section-label">选择支付方式</div>
        <NSpin :show="payMethodsLoading">
          <NAlert
            v-if="!payMethodsLoading && payMethods.length === 0"
            type="error"
            class="mb-2"
            :show-icon="false"
          >
            暂无可用支付渠道，请联系管理员
          </NAlert>
          <NRadioGroup v-else v-model:value="selectedPayMethod">
            <NSpace vertical>
              <NRadio
                v-for="m in payMethods"
                :key="m.code"
                :value="m.code"
                :disabled="!m.enabled"
              >
                {{ m.name }}
                <NTag
                  v-if="!m.enabled"
                  size="small"
                  type="default"
                  class="ml-2"
                  round
                >
                  暂未开放
                </NTag>
              </NRadio>
            </NSpace>
          </NRadioGroup>
        </NSpin>
      </div>

      <template #footer>
        <NSpace justify="end">
          <NButton :disabled="submitting" @click="buyModalVisible = false">
            取消
          </NButton>
          <NButton type="primary" :loading="submitting" @click="onConfirmBuy">
            确认支付
          </NButton>
        </NSpace>
      </template>
    </NModal>
  </div>
</template>

<style scoped>
.plan-page {
  max-width: 1400px;
  margin: 0 auto;
}
.plan-layout {
  display: grid;
  grid-template-columns: 280px 1fr;
  gap: 16px;
  align-items: start;
}
@media (max-width: 900px) {
  .plan-layout {
    grid-template-columns: 1fr;
  }
}
.plan-side {
  position: sticky;
  top: 16px;
}

.my-plan-name {
  font-size: 18px;
  font-weight: 600;
  color: #2080f0;
  margin-bottom: 12px;
}
.my-plan-meta {
  display: flex;
  flex-direction: column;
  gap: 8px;
  font-size: 13px;
}
.my-plan-meta .label {
  color: #999;
  margin-right: 6px;
}
.my-plan-meta .value {
  color: #333;
  font-weight: 500;
}

.plan-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
  gap: 16px;
  padding-top: 8px;
}

.buy-body {
  padding-bottom: 4px;
}
.buy-plan {
  display: flex;
  align-items: baseline;
  justify-content: space-between;
  padding: 12px 0;
  border-bottom: 1px solid #f0f0f0;
}
.buy-plan-name {
  font-size: 16px;
  font-weight: 500;
}
.buy-plan-price {
  font-size: 22px;
  font-weight: 700;
  color: #d03050;
}
.buy-section-label {
  margin-top: 14px;
  margin-bottom: 10px;
  font-size: 13px;
  color: #666;
}
</style>
