<script lang="ts" setup>
// 管理端 · 支付配置。docs/07 §3.2.12。
// 渠道 Tab（易支付为主；支付宝 / 微信槽位留 P3） · 敏感字段 mask · 测试支付
import { computed, onMounted, reactive, ref } from 'vue';

import {
  NAlert,
  NButton,
  NCard,
  NForm,
  NFormItem,
  NInput,
  NInputNumber,
  NSpace,
  NTabPane,
  NTabs,
  NTag,
  useMessage,
} from 'naive-ui';

import {
  type AdminPaymentConfigApi,
  listPaymentConfigApi,
  testPayAdminApi,
  updatePaymentConfigApi,
} from '#/api/admin';

const message = useMessage();

const MASKED_KEYS = new Set([
  'epay_key',
  'epay_platform_public_key',
  'epay_merchant_private_key',
]);

interface FieldDef {
  key: string;
  label: string;
  channel: 'epay' | 'global';
  type: 'number' | 'password' | 'string' | 'textarea';
  hint?: string;
  placeholder?: string;
}

const FIELDS: FieldDef[] = [
  { key: 'epay_apiurl', label: '易支付接口地址', channel: 'epay', type: 'string', placeholder: 'https://pay.example.com/submit.php' },
  { key: 'epay_pid', label: '商户 PID', channel: 'epay', type: 'string' },
  { key: 'epay_sign_type', label: '签名类型', channel: 'epay', type: 'string', hint: '常用:MD5 / RSA' },
  { key: 'epay_key', label: '商户 Key', channel: 'epay', type: 'password', hint: '敏感，后端已脱敏，留空则保持原值' },
  { key: 'epay_platform_public_key', label: '平台公钥', channel: 'epay', type: 'textarea', hint: '敏感(RSA)，留空保持原值' },
  { key: 'epay_merchant_private_key', label: '商户私钥', channel: 'epay', type: 'textarea', hint: '敏感(RSA)，留空保持原值' },
  { key: 'payment_min_amount', label: '最小金额', channel: 'global', type: 'number' },
  { key: 'payment_max_amount', label: '最大金额', channel: 'global', type: 'number' },
];

const loading = ref(false);
const saving = ref(false);
const original = reactive<Record<string, string>>({});
const current = reactive<Record<string, string>>({});
const maskedMap = reactive<Record<string, boolean>>({});

async function load() {
  loading.value = true;
  try {
    const data = await listPaymentConfigApi();
    const list = Array.isArray(data) ? data : (data as any)?.list ?? [];
    for (const k of Object.keys(original)) delete original[k];
    for (const k of Object.keys(current)) delete current[k];
    for (const k of Object.keys(maskedMap)) delete maskedMap[k];
    for (const it of list as AdminPaymentConfigApi.Item[]) {
      const v = it.config_value ?? '';
      const isMasked =
        MASKED_KEYS.has(it.config_key) &&
        (v.includes('****') || v === '' || Boolean(it.masked));
      // 敏感字段 UI 里初始值清空，避免误把 **** 提交；提示"留空保持原值"
      original[it.config_key] = isMasked ? '' : v;
      current[it.config_key] = isMasked ? '' : v;
      maskedMap[it.config_key] = isMasked;
    }
    for (const f of FIELDS) {
      if (!(f.key in current)) {
        const fb = f.type === 'number' ? '0' : '';
        original[f.key] = fb;
        current[f.key] = fb;
      }
    }
  } catch {
    message.error('加载失败');
  } finally {
    loading.value = false;
  }
}

function isSensitive(key: string) {
  return MASKED_KEYS.has(key);
}

function isDirty(key: string) {
  if (isSensitive(key)) {
    // 敏感字段只有"输入非空"才算改动（留空保持原值）
    return (current[key] ?? '').trim() !== '';
  }
  return original[key] !== current[key];
}

const dirtyCount = computed(
  () => FIELDS.filter((f) => isDirty(f.key)).length,
);

async function saveOne(key: string) {
  const val = current[key] ?? '';
  if (val.includes('****')) {
    message.warning('值含 ****，请清空后重新输入');
    return;
  }
  saving.value = true;
  try {
    await updatePaymentConfigApi(key, val);
    if (!isSensitive(key)) original[key] = val;
    message.success(`${key} 已保存`);
    // 敏感字段保存后清空输入框 + 重新拉取 mask
    if (isSensitive(key)) {
      current[key] = '';
      maskedMap[key] = true;
    }
  } catch {
    // interceptor
  } finally {
    saving.value = false;
  }
}

async function saveAll() {
  const dirty = FIELDS.filter((f) => isDirty(f.key));
  if (dirty.length === 0) {
    message.info('无改动');
    return;
  }
  saving.value = true;
  try {
    for (const f of dirty) {
      const v = current[f.key] ?? '';
      if (v.includes('****')) continue;
      await updatePaymentConfigApi(f.key, v);
      if (!isSensitive(f.key)) original[f.key] = v;
      else {
        current[f.key] = '';
        maskedMap[f.key] = true;
      }
    }
    message.success(`已保存 ${dirty.length} 项`);
  } catch {
    message.error('部分项保存失败');
  } finally {
    saving.value = false;
  }
}

function onReset(key: string) {
  current[key] = isSensitive(key) ? '' : (original[key] ?? '');
}

function onResetAll() {
  for (const f of FIELDS) {
    current[f.key] = isSensitive(f.key) ? '' : (original[f.key] ?? '');
  }
}

// ========= 测试支付 =========
const testing = ref(false);
const testResult = ref<
  | (AdminPaymentConfigApi.TestPayResult & { success?: boolean; message?: string })
  | null
>(null);
const testOk = computed(() => {
  const r = testResult.value;
  if (!r) return false;
  if (typeof r.success === 'boolean') return r.success;
  return r.configured === true && r.apiurl_reachable !== false;
});
const testMessage = computed(() => {
  const r = testResult.value;
  if (!r) return '';
  if (r.message) return r.message;
  if (!r.configured) return '未完成配置';
  if (r.error) return r.error;
  if (r.apiurl_reachable === false) return `接口不可达 (${r.http_status ?? '-'})`;
  return `已接通 (${r.http_status ?? '-'})`;
});
async function onTestPay() {
  testing.value = true;
  testResult.value = null;
  try {
    const r = await testPayAdminApi();
    testResult.value = r;
    if (testOk.value) {
      message.success('测试支付通');
    } else {
      message.error(`测试失败：${testMessage.value}`);
    }
  } catch {
    // 拦截器负责提示
  } finally {
    testing.value = false;
  }
}

onMounted(load);

const epayFields = computed(() => FIELDS.filter((f) => f.channel === 'epay'));
const globalFields = computed(
  () => FIELDS.filter((f) => f.channel === 'global'),
);
</script>

<template>
  <div class="p-6">
    <NCard title="支付配置">
      <template #header-extra>
        <NSpace>
          <NTag v-if="dirtyCount > 0" type="warning" size="small">
            未保存 {{ dirtyCount }} 项
          </NTag>
          <NButton :disabled="dirtyCount === 0" @click="onResetAll">
            全部还原
          </NButton>
          <NButton type="primary" :loading="saving" @click="saveAll">
            保存全部改动
          </NButton>
        </NSpace>
      </template>

      <NAlert type="warning" class="mb-4" :bordered="false">
        修改后对新订单立即生效；敏感字段（Key / 公私钥）后端返回 mask，前端留空则保持原值，不会覆盖。
      </NAlert>

      <NTabs type="line" default-value="epay">
        <NTabPane name="epay" tab="彩虹易支付">
          <NForm label-placement="left" label-width="160px">
            <NFormItem
              v-for="f in epayFields"
              :key="f.key"
              :label="f.label"
            >
              <div class="flex items-start gap-2 w-full">
                <template v-if="f.type === 'password'">
                  <NInput
                    v-model:value="current[f.key]"
                    type="password"
                    show-password-on="click"
                    :placeholder="
                      maskedMap[f.key] ? '已保存，留空保持原值' : f.placeholder
                    "
                    style="width: 360px"
                  />
                </template>
                <template v-else-if="f.type === 'textarea'">
                  <NInput
                    v-model:value="current[f.key]"
                    type="textarea"
                    :autosize="{ minRows: 3, maxRows: 8 }"
                    :placeholder="
                      maskedMap[f.key] ? '已保存，留空保持原值' : f.placeholder
                    "
                    style="width: 460px"
                  />
                </template>
                <template v-else-if="f.type === 'number'">
                  <NInputNumber
                    :value="Number(current[f.key] ?? 0)"
                    :min="0"
                    style="width: 200px"
                    @update:value="(v) => (current[f.key] = String(v ?? 0))"
                  />
                </template>
                <template v-else>
                  <NInput
                    v-model:value="current[f.key]"
                    :placeholder="f.placeholder"
                    style="width: 360px"
                  />
                </template>

                <NTag
                  v-if="isDirty(f.key)"
                  type="warning"
                  size="tiny"
                >
                  改动
                </NTag>
                <NTag
                  v-if="maskedMap[f.key]"
                  type="info"
                  size="tiny"
                >
                  已脱敏
                </NTag>
                <div class="text-xs text-muted-foreground flex-1 pt-1">
                  {{ f.hint ?? '' }}
                  <div>key: <code>{{ f.key }}</code></div>
                </div>
                <div>
                  <NButton
                    v-if="isDirty(f.key)"
                    size="tiny"
                    @click="onReset(f.key)"
                  >
                    还原
                  </NButton>
                  <NButton
                    v-if="isDirty(f.key)"
                    size="tiny"
                    type="primary"
                    :loading="saving"
                    @click="saveOne(f.key)"
                  >
                    保存
                  </NButton>
                </div>
              </div>
            </NFormItem>

            <NFormItem label=" ">
              <NSpace>
                <NButton :loading="testing" @click="onTestPay">
                  测试支付
                </NButton>
                <NTag
                  v-if="testResult"
                  :type="testOk ? 'success' : 'error'"
                  size="small"
                >
                  {{ testOk ? '通' : testMessage }}
                </NTag>
              </NSpace>
            </NFormItem>
          </NForm>
        </NTabPane>

        <NTabPane name="global" tab="金额限制">
          <NForm label-placement="left" label-width="160px">
            <NFormItem
              v-for="f in globalFields"
              :key="f.key"
              :label="f.label"
            >
              <div class="flex items-center gap-2 w-full">
                <NInputNumber
                  :value="Number(current[f.key] ?? 0)"
                  :min="0"
                  style="width: 200px"
                  @update:value="(v) => (current[f.key] = String(v ?? 0))"
                />
                <NTag
                  v-if="isDirty(f.key)"
                  type="warning"
                  size="tiny"
                >
                  改动
                </NTag>
                <span class="text-xs text-muted-foreground">
                  key: <code>{{ f.key }}</code>
                </span>
                <div class="ml-auto">
                  <NButton
                    v-if="isDirty(f.key)"
                    size="tiny"
                    @click="onReset(f.key)"
                  >
                    还原
                  </NButton>
                  <NButton
                    v-if="isDirty(f.key)"
                    size="tiny"
                    type="primary"
                    :loading="saving"
                    @click="saveOne(f.key)"
                  >
                    保存
                  </NButton>
                </div>
              </div>
            </NFormItem>
          </NForm>
        </NTabPane>

        <NTabPane name="alipay" tab="支付宝（P3）" disabled>
          <NAlert type="info" :bordered="false">
            支付宝官方渠道将在 P3 阶段接入
          </NAlert>
        </NTabPane>

        <NTabPane name="wechat" tab="微信支付（P3）" disabled>
          <NAlert type="info" :bordered="false">
            微信官方渠道将在 P3 阶段接入
          </NAlert>
        </NTabPane>
      </NTabs>
    </NCard>
  </div>
</template>
