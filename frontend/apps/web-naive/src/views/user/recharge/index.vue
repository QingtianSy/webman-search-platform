<script lang="ts" setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { onBeforeRouteLeave, useRoute, useRouter } from 'vue-router';

import { useWalletStore } from '#/store/wallet';

import {
  NAlert,
  NButton,
  NCard,
  NDivider,
  NInputNumber,
  NRadio,
  NRadioGroup,
  NResult,
  NSpace,
  NSpin,
  NStep,
  NSteps,
  NTag,
  useDialog,
  useMessage,
} from 'naive-ui';

import {
  cancelOrderApi,
  continueOrderApi,
  createOrderApi,
  getOrderDetailApi,
  type OrderApi,
} from '#/api/user/order';
import {
  getPaymentMethodsApi,
  type PaymentApi,
} from '#/api/user/payment';

const route = useRoute();
const router = useRouter();
const message = useMessage();

// 移动端断点：< 640px 时 NSteps 改成纵向，避免 3 列描述挤成 1 字 1 行
// 用 matchMedia 侦听，比 window.resize 粒度合适，切横竖屏即时生效
const isNarrow = ref(false);
let mqRemove: (() => void) | null = null;
function setupResponsive() {
  if (typeof window === 'undefined' || !window.matchMedia) return;
  const mq = window.matchMedia('(max-width: 640px)');
  isNarrow.value = mq.matches;
  const handler = (e: MediaQueryListEvent) => {
    isNarrow.value = e.matches;
  };
  mq.addEventListener?.('change', handler);
  mqRemove = () => mq.removeEventListener?.('change', handler);
}
const dialog = useDialog();
const walletStore = useWalletStore();

// 步骤：0 填金额 / 1 扫码支付 / 2 结果
const step = ref(0);

// sessionStorage 持久化：支付中刷新不丢单
const STORAGE_KEY = 'recharge:pending';
interface PendingState {
  orderId: number | string;
  orderNo: string;
  amount: number;
  payMethod: string;
  payUrl: string;
  qrUrl: string;
}
function savePending() {
  if (!orderId.value) return;
  const s: PendingState = {
    orderId: orderId.value,
    orderNo: orderNo.value,
    amount: Number(amount.value ?? 0),
    payMethod: selectedPayMethod.value,
    payUrl: payUrl.value,
    qrUrl: qrUrl.value,
  };
  try {
    sessionStorage.setItem(STORAGE_KEY, JSON.stringify(s));
  } catch {
    // 隐私模式或存储配额爆了，忽略——至少 in-memory 流程不受影响
  }
}
function clearPending() {
  try {
    sessionStorage.removeItem(STORAGE_KEY);
  } catch {
    // ignore
  }
}
function readPending(): null | PendingState {
  try {
    const raw = sessionStorage.getItem(STORAGE_KEY);
    if (!raw) return null;
    return JSON.parse(raw) as PendingState;
  } catch {
    return null;
  }
}

// Step 1 数据
const amount = ref<null | number>(null);
const quickAmounts = [10, 30, 50, 100, 300, 500];
const payMethods = ref<PaymentApi.Method[]>([]);
const selectedPayMethod = ref<string>('');
const methodsLoading = ref(false);

// Step 2 数据
const orderId = ref<number | string | null>(null);
const orderNo = ref<string>('');
const payUrl = ref<string>('');
const qrUrl = ref<string>('');
const creating = ref(false);
const polling = ref(false);
const pollTimer = ref<null | ReturnType<typeof setInterval>>(null);
const pollStartAt = ref(0);
const POLL_INTERVAL_MS = 3000;
const POLL_TIMEOUT_MS = 10 * 60 * 1000; // 10min

// Step 3 数据
const resultStatus = ref<'failed' | 'pending' | 'success' | 'timeout'>('pending');
const finalOrder = ref<null | OrderApi.Order>(null);

// 校验：金额 1–50000，两位小数
const amountValid = computed(() => {
  const v = Number(amount.value ?? 0);
  return v >= 1 && v <= 50_000;
});

const canSubmit = computed(
  () =>
    !creating.value && amountValid.value && selectedPayMethod.value.length > 0,
);

async function loadMethods() {
  methodsLoading.value = true;
  try {
    payMethods.value = await getPaymentMethodsApi();
    const first = payMethods.value.find((m) => m.enabled);
    if (first) selectedPayMethod.value = first.code;
  } finally {
    methodsLoading.value = false;
  }
}

function setQuickAmount(v: number) {
  amount.value = v;
}

async function submitRecharge() {
  if (!canSubmit.value) return;
  creating.value = true;
  try {
    const r = await createOrderApi({
      order_type: 'recharge',
      amount: amount.value!,
      pay_method: selectedPayMethod.value,
    });
    orderId.value = r.order_id;
    orderNo.value = r.out_trade_no;
    payUrl.value = r.pay_url ?? '';
    qrUrl.value = r.qr_code_url ?? '';
    step.value = 1;
    savePending();
    startPolling();
  } catch {
    message.error('创建订单失败，请稍后重试');
  } finally {
    creating.value = false;
  }
}

function startPolling() {
  stopPolling();
  polling.value = true;
  pollStartAt.value = Date.now();
  pollTimer.value = setInterval(pollOnce, POLL_INTERVAL_MS);
  // 立刻查一次
  pollOnce();
}

function stopPolling() {
  if (pollTimer.value) {
    clearInterval(pollTimer.value);
    pollTimer.value = null;
  }
  polling.value = false;
}

async function pollOnce() {
  if (!orderId.value) return;
  // 超时停
  if (Date.now() - pollStartAt.value > POLL_TIMEOUT_MS) {
    stopPolling();
    resultStatus.value = 'timeout';
    clearPending();
    step.value = 2;
    return;
  }
  try {
    const d = await getOrderDetailApi(orderId.value);
    if (!d) return;
    finalOrder.value = d;
    const s = String(d.status);
    if (s === 'success' || s === '1' || s === '2') {
      stopPolling();
      resultStatus.value = 'success';
      walletStore.invalidate();
      clearPending();
      step.value = 2;
    } else if (
      s === 'failed' ||
      s === 'cancelled' ||
      s === 'expired' ||
      s === '3' ||
      s === '4' ||
      s === '5'
    ) {
      stopPolling();
      resultStatus.value = 'failed';
      clearPending();
      step.value = 2;
    }
  } catch {
    // 50001 由拦截器统一提示，这里暂停 30s 再恢复
    stopPolling();
    setTimeout(() => {
      if (step.value === 1) startPolling();
    }, 30_000);
  }
}

async function cancelCurrent() {
  if (!orderNo.value) {
    goBackStep0();
    return;
  }
  try {
    await cancelOrderApi(orderNo.value);
    message.success('已取消订单');
  } catch {
    // ignore
  }
  goBackStep0();
}

function goBackStep0() {
  stopPolling();
  orderId.value = null;
  orderNo.value = '';
  payUrl.value = '';
  qrUrl.value = '';
  finalOrder.value = null;
  resultStatus.value = 'pending';
  step.value = 0;
  clearPending();
}

async function retryPay() {
  if (!orderNo.value) {
    goBackStep0();
    return;
  }
  try {
    const r = await continueOrderApi(orderNo.value);
    orderId.value = r.order_id ?? orderId.value;
    payUrl.value = r.pay_url ?? payUrl.value;
    qrUrl.value = r.qr_code_url ?? qrUrl.value;
    resultStatus.value = 'pending';
    step.value = 1;
    savePending();
    startPolling();
  } catch {
    message.error('重新发起支付失败');
  }
}

function goDashboard() {
  router.push('/user/dashboard');
}

function goBalanceLog() {
  router.push('/user/log/balance');
}

function openPayUrl() {
  if (payUrl.value) window.open(payUrl.value, '_blank');
}

// 路由守卫：支付中离开二次确认
onBeforeRouteLeave((_to, _from, next) => {
  if (step.value === 1 && polling.value) {
    dialog.warning({
      title: '确定离开？',
      content:
        '订单尚未支付完成。您可以稍后在「订单记录」里继续支付，也可以直接取消。',
      positiveText: '继续离开',
      negativeText: '留在当前页',
      onPositiveClick: () => {
        stopPolling();
        next();
      },
      onNegativeClick: () => {
        next(false);
      },
    });
  } else {
    next();
  }
});

onBeforeUnmount(() => {
  stopPolling();
  mqRemove?.();
});

onMounted(() => {
  setupResponsive();
  loadMethods();
  // 优先级：URL query > sessionStorage pending > 默认 Step 0
  const qId = route.query.order_id;
  const qNo = route.query.order_no;
  if (qId) {
    orderId.value = String(qId);
    orderNo.value = String(qNo ?? '');
    step.value = 1;
    savePending();
    startPolling();
    return;
  }
  const pending = readPending();
  if (pending?.orderId) {
    orderId.value = pending.orderId;
    orderNo.value = pending.orderNo;
    amount.value = pending.amount;
    selectedPayMethod.value = pending.payMethod;
    payUrl.value = pending.payUrl;
    qrUrl.value = pending.qrUrl;
    step.value = 1;
    startPolling();
  }
});
</script>

<template>
  <div class="recharge-page p-6">
    <NCard :bordered="false" size="small">
      <NSteps
        :current="step + 1"
        class="mb-4"
        :vertical="isNarrow"
      >
        <NStep title="填写金额" description="选择充值金额和支付方式" />
        <NStep title="扫码支付" description="扫描二维码完成支付" />
        <NStep title="支付完成" description="查看充值结果" />
      </NSteps>

      <!-- Step 1 -->
      <div v-if="step === 0" class="step-body">
        <div class="field-label">充值金额（元）</div>
        <NInputNumber
          v-model:value="amount"
          :min="1"
          :max="50000"
          :precision="2"
          placeholder="请输入金额"
          style="width: 280px"
        />
        <div class="quick-amounts mt-2">
          <NButton
            v-for="v in quickAmounts"
            :key="v"
            size="small"
            :type="amount === v ? 'primary' : 'default'"
            @click="setQuickAmount(v)"
          >
            ¥ {{ v }}
          </NButton>
        </div>

        <NDivider />

        <div class="field-label">选择支付方式</div>
        <NSpin :show="methodsLoading">
          <NAlert
            v-if="!methodsLoading && payMethods.length === 0"
            type="error"
            :show-icon="false"
            class="mb-2"
          >
            暂无可用支付渠道，请联系管理员
          </NAlert>
          <NRadioGroup v-else v-model:value="selectedPayMethod">
            <NSpace>
              <NRadio
                v-for="m in payMethods"
                :key="m.code"
                :value="m.code"
                :disabled="!m.enabled"
              >
                {{ m.name }}
                <NTag v-if="!m.enabled" size="small" class="ml-2" round>
                  暂未开放
                </NTag>
              </NRadio>
            </NSpace>
          </NRadioGroup>
        </NSpin>

        <div class="mt-6">
          <NButton
            type="primary"
            size="large"
            :loading="creating"
            :disabled="!canSubmit"
            @click="submitRecharge"
          >
            下一步
          </NButton>
        </div>
      </div>

      <!-- Step 2 -->
      <div v-else-if="step === 1" class="step-body text-center">
        <div v-if="qrUrl" class="qr-wrap">
          <img :src="qrUrl" alt="支付二维码" class="qr-img" />
        </div>
        <div v-else-if="payUrl" class="py-8">
          <p class="mb-2">正在跳转收银台，如未自动打开请点击下方按钮。</p>
          <NButton type="primary" @click="openPayUrl">打开支付页面</NButton>
        </div>
        <div v-else class="py-8">
          <NSpin size="large" />
          <div class="mt-3 text-gray-500">等待支付渠道返回二维码…</div>
        </div>

        <div class="pay-tip mt-2">
          请使用 <NTag size="small" type="info">{{ selectedPayMethod }}</NTag>
          扫描上方二维码完成支付
        </div>
        <div class="text-xs text-gray-400">
          订单号 {{ orderNo || '--' }}，订单金额 ¥ {{ Number(amount ?? 0).toFixed(2) }}
        </div>

        <NDivider />

        <NSpace justify="center">
          <NButton @click="cancelCurrent">取消订单</NButton>
          <NButton type="primary" ghost :loading="polling" @click="pollOnce">
            我已完成支付
          </NButton>
        </NSpace>
      </div>

      <!-- Step 3 -->
      <div v-else class="step-body">
        <NResult
          v-if="resultStatus === 'success'"
          status="success"
          title="充值成功"
          :description="`订单号 ${orderNo}，金额 ¥ ${Number(amount ?? 0).toFixed(2)}`"
        >
          <template #footer>
            <NSpace>
              <NButton @click="goBalanceLog">查看余额记录</NButton>
              <NButton type="primary" @click="goDashboard">返回首页</NButton>
            </NSpace>
          </template>
        </NResult>
        <NResult
          v-else-if="resultStatus === 'failed'"
          status="error"
          title="支付失败"
          :description="finalOrder?.fail_reason ?? '请重新发起支付或更换支付方式'"
        >
          <template #footer>
            <NSpace>
              <NButton @click="goBackStep0">重新下单</NButton>
              <NButton type="primary" @click="retryPay">重试支付</NButton>
            </NSpace>
          </template>
        </NResult>
        <NResult
          v-else
          status="warning"
          title="支付超时"
          description="长时间未收到支付结果，请稍后在订单记录中继续支付"
        >
          <template #footer>
            <NSpace>
              <NButton @click="goBackStep0">重新下单</NButton>
              <NButton type="primary" @click="retryPay">继续支付</NButton>
            </NSpace>
          </template>
        </NResult>
      </div>
    </NCard>
  </div>
</template>

<style scoped>
.recharge-page {
  max-width: 960px;
  margin: 0 auto;
}

/* 窄屏（<=640px）收敛：减小内边距、QR 缩到 180、快捷面额换行 */
@media (max-width: 640px) {
  .recharge-page {
    padding: 12px !important;
  }
  .step-body {
    padding: 16px 0;
  }
  .qr-img {
    width: 180px;
    height: 180px;
  }
  .quick-amounts :deep(.n-button) {
    min-width: 72px;
  }
}
.step-body {
  padding: 24px 8px;
}
.field-label {
  font-size: 14px;
  color: #666;
  margin-bottom: 10px;
}
.quick-amounts {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
}
.qr-wrap {
  display: inline-block;
  padding: 16px;
  background: #fff;
  border: 1px solid #eee;
  border-radius: 8px;
}
.qr-img {
  width: 220px;
  height: 220px;
  display: block;
}
.pay-tip {
  font-size: 14px;
  color: #666;
  margin-top: 12px;
}
.text-xs {
  font-size: 12px;
}
.text-gray-500 {
  color: #666;
}
.text-gray-400 {
  color: #999;
}
.ml-2 {
  margin-left: 8px;
}
</style>
