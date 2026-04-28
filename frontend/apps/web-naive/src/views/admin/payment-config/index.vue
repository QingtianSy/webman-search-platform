<script lang="ts" setup>
// 管理端 · 支付配置。docs/07 §3.2.12。
// 彩虹易支付 · 支付方式开关 · 测试支付
import { computed, onMounted, reactive, ref } from 'vue';

import {
  NButton,
  NCard,
  NForm,
  NFormItem,
  NInput,
  NInputNumber,
  NSelect,
  NSpace,
  NSwitch,
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

interface FieldDef {
  key: string;
  label: string;
  channel: 'epay' | 'global';
  type: 'boolean' | 'number' | 'password' | 'select' | 'string' | 'textarea';
  hint?: string;
  placeholder?: string;
}

const SIGN_TYPE_OPTIONS = [
  { label: 'v1', value: 'v1' },
  { label: 'v2', value: 'v2' },
];

const PAY_METHOD_FIELDS: FieldDef[] = [
  { key: 'epay_alipay_enabled', label: '支付宝支付', channel: 'epay', type: 'boolean' },
  { key: 'epay_wxpay_enabled', label: '微信支付', channel: 'epay', type: 'boolean' },
  { key: 'epay_qqpay_enabled', label: 'QQ支付', channel: 'epay', type: 'boolean' },
];

const EPAY_FIELDS: FieldDef[] = [
  { key: 'epay_apiurl', label: '易支付接口地址', channel: 'epay', type: 'string', placeholder: 'https://pay.example.com/submit.php' },
  { key: 'epay_pid', label: '商户 PID', channel: 'epay', type: 'string' },
  { key: 'epay_sign_type', label: '签名类型', channel: 'epay', type: 'select' },
  { key: 'epay_key', label: '商户 Key', channel: 'epay', type: 'string' },
  { key: 'epay_platform_public_key', label: '平台公钥', channel: 'epay', type: 'textarea' },
  { key: 'epay_merchant_private_key', label: '商户私钥', channel: 'epay', type: 'textarea' },
];

const GLOBAL_FIELDS: FieldDef[] = [
  { key: 'payment_min_amount', label: '最小金额', channel: 'global', type: 'number' },
  { key: 'payment_max_amount', label: '最大金额', channel: 'global', type: 'number' },
];

const FIELDS: FieldDef[] = [
  ...EPAY_FIELDS,
  ...PAY_METHOD_FIELDS,
  ...GLOBAL_FIELDS,
];

const loading = ref(false);
const saving = ref(false);
const original = reactive<Record<string, string>>({});
const current = reactive<Record<string, string>>({});

function normalizeSignType(value: string) {
  const v = value.trim().toUpperCase();
  return v === 'RSA' || v === 'V2' ? 'v2' : 'v1';
}

function boolValue(key: string) {
  return ['1', 'true'].includes(String(current[key] ?? '').toLowerCase());
}

function setBoolValue(key: string, value: boolean) {
  current[key] = value ? '1' : '0';
}

async function load() {
  loading.value = true;
  try {
    const data = await listPaymentConfigApi();
    const list = Array.isArray(data) ? data : (data as any)?.list ?? [];
    for (const k of Object.keys(original)) delete original[k];
    for (const k of Object.keys(current)) delete current[k];
    for (const it of list as AdminPaymentConfigApi.Item[]) {
      const v = it.config_value ?? '';
      const normalized = it.config_key === 'epay_sign_type' ? normalizeSignType(v) : v;
      original[it.config_key] = normalized;
      current[it.config_key] = normalized;
    }
    for (const f of FIELDS) {
      if (!(f.key in current)) {
        const fb = f.type === 'number'
          ? '0'
          : f.type === 'boolean'
            ? '1'
            : f.key === 'epay_sign_type'
              ? 'v1'
              : '';
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

function isDirty(key: string) {
  return original[key] !== current[key];
}

async function saveOne(key: string) {
  const val = current[key] ?? '';
  saving.value = true;
  try {
    await updatePaymentConfigApi(key, val);
    original[key] = val;
    message.success(`${key} 已保存`);
  } catch {
    // interceptor
  } finally {
    saving.value = false;
  }
}

async function saveAll() {
  const dirty = editableFields.value
    .filter((f) => isDirty(f.key))
    .sort((a, b) => {
      if (a.key === 'epay_sign_type') return 1;
      if (b.key === 'epay_sign_type') return -1;
      return 0;
    });
  if (dirty.length === 0) {
    message.info('无改动');
    return;
  }
  saving.value = true;
  try {
    for (const f of dirty) {
      const v = current[f.key] ?? '';
      await updatePaymentConfigApi(f.key, v);
      original[f.key] = v;
    }
    message.success(`已保存 ${dirty.length} 项`);
  } catch {
    message.error('部分项保存失败');
  } finally {
    saving.value = false;
  }
}

function onReset(key: string) {
  current[key] = original[key] ?? '';
}

function onResetAll() {
  for (const f of editableFields.value) {
    current[f.key] = original[f.key] ?? '';
  }
}

async function savePayMethodSwitches() {
  const dirty = PAY_METHOD_FIELDS.filter((f) => isDirty(f.key));
  if (dirty.length === 0) return;
  saving.value = true;
  try {
    for (const f of dirty) {
      const v = current[f.key] ?? '0';
      await updatePaymentConfigApi(f.key, v);
      original[f.key] = v;
    }
    message.success('支付方式开关已保存');
  } catch {
    message.error('支付方式开关保存失败');
  } finally {
    saving.value = false;
  }
}

function resetPayMethodSwitches() {
  for (const f of PAY_METHOD_FIELDS) {
    current[f.key] = original[f.key] ?? '0';
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

const isV2Sign = computed(() => normalizeSignType(current.epay_sign_type ?? '') === 'v2');

function isFieldVisible(f: FieldDef) {
  if (f.key === 'epay_key') return !isV2Sign.value;
  if (f.key === 'epay_platform_public_key' || f.key === 'epay_merchant_private_key') {
    return isV2Sign.value;
  }
  return true;
}

const epayApiurlField = EPAY_FIELDS[0]!;
const epayFields = computed(() =>
  EPAY_FIELDS.filter((f) => f.key !== 'epay_apiurl').filter(isFieldVisible),
);
const globalFields = computed(() => GLOBAL_FIELDS);
const editableFields = computed(
  () => [epayApiurlField, ...PAY_METHOD_FIELDS, ...epayFields.value, ...globalFields.value],
);
const dirtyCount = computed(
  () => editableFields.value.filter((f) => isDirty(f.key)).length,
);
const payMethodDirty = computed(() =>
  PAY_METHOD_FIELDS.some((f) => isDirty(f.key)),
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

      <NTabs type="line" default-value="epay">
        <NTabPane name="epay" tab="彩虹易支付">
          <NForm label-placement="left" label-width="160px">
            <NFormItem :label="epayApiurlField.label">
              <div class="flex items-start gap-2 w-full">
                <NInput
                  v-model:value="current[epayApiurlField.key]"
                  :placeholder="epayApiurlField.placeholder"
                  style="width: 360px"
                />
                <NTag
                  v-if="isDirty(epayApiurlField.key)"
                  type="warning"
                  size="tiny"
                >
                  改动
                </NTag>
                <div class="text-xs text-muted-foreground flex-1 pt-1">
                  <div>key: <code>{{ epayApiurlField.key }}</code></div>
                </div>
                <div>
                  <NButton
                    v-if="isDirty(epayApiurlField.key)"
                    size="tiny"
                    @click="onReset(epayApiurlField.key)"
                  >
                    还原
                  </NButton>
                  <NButton
                    v-if="isDirty(epayApiurlField.key)"
                    size="tiny"
                    type="primary"
                    :loading="saving"
                    @click="saveOne(epayApiurlField.key)"
                  >
                    保存
                  </NButton>
                </div>
              </div>
            </NFormItem>

            <NFormItem label="支付方式开关">
              <div class="flex items-center gap-4 w-full">
                <NSpace>
                  <div
                    v-for="f in PAY_METHOD_FIELDS"
                    :key="f.key"
                    class="pay-method-switch"
                  >
                    <span>{{ f.label }}</span>
                    <NSwitch
                      :value="boolValue(f.key)"
                      @update:value="(v) => setBoolValue(f.key, v)"
                    />
                    <NTag
                      v-if="isDirty(f.key)"
                      type="warning"
                      size="tiny"
                    >
                      改动
                    </NTag>
                  </div>
                </NSpace>
                <div class="text-xs text-muted-foreground flex-1">
                  关闭后用户端不可选择该支付方式
                </div>
                <div>
                  <NButton
                    v-if="payMethodDirty"
                    size="tiny"
                    @click="resetPayMethodSwitches"
                  >
                    还原
                  </NButton>
                  <NButton
                    v-if="payMethodDirty"
                    size="tiny"
                    type="primary"
                    :loading="saving"
                    @click="savePayMethodSwitches"
                  >
                    保存
                  </NButton>
                </div>
              </div>
            </NFormItem>

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
                    :placeholder="f.placeholder"
                    style="width: 360px"
                  />
                </template>
                <template v-else-if="f.type === 'textarea'">
                  <NInput
                    v-model:value="current[f.key]"
                    type="textarea"
                    :autosize="{ minRows: 3, maxRows: 8 }"
                    :placeholder="f.placeholder"
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
                <template v-else-if="f.type === 'select'">
                  <NSelect
                    v-model:value="current[f.key]"
                    :options="SIGN_TYPE_OPTIONS"
                    style="width: 200px"
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
      </NTabs>
    </NCard>
  </div>
</template>

<style scoped>
.pay-method-switch {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  min-height: 32px;
}
</style>
