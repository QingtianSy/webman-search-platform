<script lang="ts" setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { onBeforeRouteLeave, useRoute, useRouter } from 'vue-router';

import {
  NAlert,
  NButton,
  NCard,
  NDivider,
  NInputNumber,
  NQrCode,
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
import { useWalletStore } from '#/store/wallet';

const route = useRoute();
const router = useRouter();
const dialog = useDialog();
const message = useMessage();
const walletStore = useWalletStore();

const isNarrow = ref(false);
let mqRemove: (() => void) | null = null;

function setupResponsive() {
  if (typeof window === 'undefined' || !window.matchMedia) return;
  const mq = window.matchMedia('(max-width: 640px)');
  isNarrow.value = mq.matches;
  const handler = (event: MediaQueryListEvent) => {
    isNarrow.value = event.matches;
  };
  mq.addEventListener?.('change', handler);
  mqRemove = () => mq.removeEventListener?.('change', handler);
}

const step = ref(0);

const STORAGE_KEY = 'recharge:pending';

interface PendingState {
  amount: number;
  orderId: null | number | string;
  orderNo: string;
  payMethod: string;
  payUrl: string;
  qrUrl: string;
}

function savePending() {
  if (!orderId.value && !orderNo.value) return;
  const state: PendingState = {
    amount: Number(amount.value ?? 0),
    orderId: orderId.value,
    orderNo: orderNo.value,
    payMethod: selectedPayMethod.value,
    payUrl: payUrl.value,
    qrUrl: qrUrl.value,
  };
  try {
    sessionStorage.setItem(STORAGE_KEY, JSON.stringify(state));
  } catch {
    // ignore sessionStorage failures
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
    return raw ? (JSON.parse(raw) as PendingState) : null;
  } catch {
    return null;
  }
}

const amount = ref<null | number>(null);
const quickAmounts = [10, 30, 50, 100, 300, 500];
const payMethods = ref<PaymentApi.Method[]>([]);
const selectedPayMethod = ref('');
const methodsLoading = ref(false);

const orderId = ref<null | number | string>(null);
const orderNo = ref('');
const payUrl = ref('');
const qrUrl = ref('');
const creating = ref(false);
const polling = ref(false);
const pollTimer = ref<null | ReturnType<typeof setInterval>>(null);
const pollStartAt = ref(0);
const POLL_INTERVAL_MS = 3000;
const POLL_TIMEOUT_MS = 10 * 60 * 1000;

const resultStatus = ref<'failed' | 'pending' | 'success' | 'timeout'>('pending');
const finalOrder = ref<null | OrderApi.Order>(null);

const amountValid = computed(() => {
  const value = Number(amount.value ?? 0);
  return value >= 1 && value <= 50_000;
});

const canSubmit = computed(() => {
  return !creating.value && amountValid.value && selectedPayMethod.value.length > 0;
});

const payMethodLabel = computed(() => {
  return (
    payMethods.value.find((item) => item.code === selectedPayMethod.value)?.name ??
    selectedPayMethod.value
  );
});

const qrImageUrl = computed(() => {
  return qrUrl.value.startsWith('data:image') ? qrUrl.value : '';
});

async function loadMethods() {
  methodsLoading.value = true;
  try {
    payMethods.value = (await getPaymentMethodsApi()).filter((item) => item.enabled);
    const currentEnabled = payMethods.value.find(
      (item) => item.code === selectedPayMethod.value,
    );
    const firstEnabled = payMethods.value[0];
    if (!currentEnabled) {
      selectedPayMethod.value = firstEnabled?.code ?? '';
    }
  } finally {
    methodsLoading.value = false;
  }
}

function setQuickAmount(value: number) {
  amount.value = value;
}

async function submitRecharge() {
  if (!canSubmit.value) return;
  creating.value = true;
  try {
    const response = await createOrderApi({
      order_type: 'recharge',
      amount: amount.value!,
      pay_method: selectedPayMethod.value,
    });
    amount.value = Number(response.amount ?? amount.value ?? 0);
    orderId.value = response.order_id ?? response.id ?? null;
    orderNo.value = response.order_no || response.out_trade_no;
    payUrl.value = response.pay_url ?? '';
    qrUrl.value = response.qr_code_url ?? '';
    step.value = 1;
    resultStatus.value = 'pending';
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
  pollTimer.value = setInterval(() => {
    void pollOnce();
  }, POLL_INTERVAL_MS);
  void pollOnce();
}

function stopPolling() {
  if (pollTimer.value) {
    clearInterval(pollTimer.value);
    pollTimer.value = null;
  }
  polling.value = false;
}

async function pollOnce() {
  const orderKey = orderNo.value || orderId.value;
  if (!orderKey) return;

  if (Date.now() - pollStartAt.value > POLL_TIMEOUT_MS) {
    stopPolling();
    resultStatus.value = 'timeout';
    clearPending();
    step.value = 2;
    return;
  }

  try {
    const detail = await getOrderDetailApi(orderKey);
    if (!detail) return;

    finalOrder.value = detail;
    amount.value = Number(detail.amount ?? amount.value ?? 0);
    orderId.value = detail.order_id ?? orderId.value;
    orderNo.value = detail.order_no || detail.out_trade_no || orderNo.value;
    payUrl.value = detail.pay_url ?? payUrl.value;
    qrUrl.value = detail.qr_code_url ?? qrUrl.value;

    const status = Number(detail.status);
    if (status === 1 || detail.status_text === 'success') {
      stopPolling();
      resultStatus.value = 'success';
      walletStore.invalidate();
      clearPending();
      step.value = 2;
      return;
    }

    if (status === 2 || detail.status_text === 'cancelled') {
      stopPolling();
      resultStatus.value = 'failed';
      clearPending();
      step.value = 2;
      return;
    }

    savePending();
  } catch {
    stopPolling();
    setTimeout(() => {
      if (step.value === 1) {
        startPolling();
      }
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
    message.success('订单已取消');
  } catch {
    // ignore; page will reset locally either way
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
    const response = await continueOrderApi(orderNo.value);
    orderId.value = response.order_id ?? response.id ?? orderId.value;
    orderNo.value = response.order_no || response.out_trade_no || orderNo.value;
    payUrl.value = response.pay_url ?? payUrl.value;
    qrUrl.value = response.qr_code_url ?? qrUrl.value;
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
  if (payUrl.value) {
    window.open(payUrl.value, '_blank');
  }
}

onBeforeRouteLeave((_to, _from, next) => {
  if (step.value !== 1 || !polling.value) {
    next();
    return;
  }

  dialog.warning({
    title: '确定离开？',
    content: '订单尚未完成支付。稍后你可以在支付记录里继续支付，也可以现在取消订单。',
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
});

onBeforeUnmount(() => {
  stopPolling();
  mqRemove?.();
});

onMounted(() => {
  setupResponsive();
  void loadMethods();

  const queryOrderId = route.query.order_id;
  const queryOrderNo = route.query.order_no;
  if (queryOrderId || queryOrderNo) {
    orderId.value = queryOrderId ? String(queryOrderId) : null;
    orderNo.value = queryOrderNo ? String(queryOrderNo) : '';
    step.value = 1;
    savePending();
    startPolling();
    return;
  }

  const pending = readPending();
  if (pending?.orderId || pending?.orderNo) {
    amount.value = pending.amount;
    orderId.value = pending.orderId;
    orderNo.value = pending.orderNo;
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
      <NSteps :current="step + 1" class="mb-4" :vertical="isNarrow">
        <NStep title="填写金额" description="选择充值金额和支付方式" />
        <NStep title="扫码支付" description="扫描二维码完成支付" />
        <NStep title="支付完成" description="查看充值结果" />
      </NSteps>

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
            v-for="value in quickAmounts"
            :key="value"
            size="small"
            :type="amount === value ? 'primary' : 'default'"
            @click="setQuickAmount(value)"
          >
            ¥ {{ value }}
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
            暂无可用支付方式，请先在支付配置中开启支付宝支付、微信支付或 QQ支付
          </NAlert>
          <NRadioGroup v-else v-model:value="selectedPayMethod">
            <NSpace>
              <NRadio
                v-for="method in payMethods"
                :key="method.code"
                :value="method.code"
              >
                {{ method.name }}
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

      <div v-else-if="step === 1" class="step-body text-center">
        <div v-if="qrImageUrl" class="qr-wrap">
          <img :src="qrImageUrl" alt="支付二维码" class="qr-img" />
        </div>
        <div v-else-if="payUrl" class="qr-wrap">
          <NQrCode :value="payUrl" :size="220" />
        </div>
        <div v-else class="py-8">
          <NSpin size="large" />
          <div class="mt-3 text-gray-500">等待支付渠道返回可用二维码</div>
        </div>

        <div class="pay-tip mt-2">
          请使用
          <NTag size="small" type="info">{{ payMethodLabel }}</NTag>
          扫描上方二维码完成支付
        </div>
        <div class="text-xs text-gray-400">
          订单号 {{ orderNo || '--' }}，订单金额 ¥ {{ Number(amount ?? 0).toFixed(2) }}
        </div>

        <div class="mt-4">
          <NButton v-if="payUrl" type="primary" text @click="openPayUrl">
            在新窗口打开收银台
          </NButton>
        </div>

        <NDivider />

        <NSpace justify="center">
          <NButton @click="cancelCurrent">取消订单</NButton>
          <NButton type="primary" ghost :loading="polling" @click="pollOnce">
            我已完成支付
          </NButton>
        </NSpace>
      </div>

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
          description="长时间未收到支付结果，请稍后在支付记录中继续支付"
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
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 16px;
  background: #fff;
  border: 1px solid #eee;
  border-radius: 8px;
  min-width: 252px;
  min-height: 252px;
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
