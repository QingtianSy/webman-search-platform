<script lang="ts" setup>
// 管理端 · 文档配置（全局 AI）。docs/07 §3.2.10 D 段。
// api_key 后端已 mask 返回；UI 留空则保持原值。providers 数组 JSON 输入并做校验。
import { computed, onMounted, ref } from 'vue';

import {
  NAlert,
  NButton,
  NCard,
  NForm,
  NFormItem,
  NInput,
  NSpace,
  NTag,
  useMessage,
} from 'naive-ui';

import {
  type AdminDocConfigApi,
  listDocConfigApi,
  updateDocConfigApi,
} from '#/api/admin';

const message = useMessage();

const loading = ref(false);
const saving = ref(false);

const apiKey = ref('');           // 输入框；留空保持原值
const apiKeyMasked = ref('');     // 后端返回的 mask（展示用）
const multimodalModel = ref('');
const textModel = ref('');
const providersText = ref('[]');  // JSON 字符串
const providersError = ref('');

const originalMultimodal = ref('');
const originalText = ref('');
const originalProviders = ref('[]');

async function load() {
  loading.value = true;
  try {
    const cfg = await listDocConfigApi();
    apiKeyMasked.value = cfg?.api_key ?? '';
    apiKey.value = '';
    multimodalModel.value = cfg?.multimodal_model ?? '';
    textModel.value = cfg?.text_model ?? '';
    const arr = Array.isArray(cfg?.providers) ? cfg!.providers : [];
    providersText.value = JSON.stringify(arr, null, 2);

    originalMultimodal.value = multimodalModel.value;
    originalText.value = textModel.value;
    originalProviders.value = providersText.value;
  } catch {
    message.error('加载失败');
  } finally {
    loading.value = false;
  }
}

const dirty = computed(() => {
  return (
    apiKey.value.trim() !== '' ||
    multimodalModel.value !== originalMultimodal.value ||
    textModel.value !== originalText.value ||
    providersText.value !== originalProviders.value
  );
});

function validateProviders(): false | any[] {
  providersError.value = '';
  const raw = providersText.value.trim();
  if (!raw) {
    providersError.value = '不能为空，至少应为 []';
    return false;
  }
  try {
    const parsed = JSON.parse(raw);
    if (!Array.isArray(parsed)) {
      providersError.value = 'providers 必须为数组';
      return false;
    }
    return parsed;
  } catch {
    providersError.value = 'JSON 解析失败';
    return false;
  }
}

async function onSave() {
  const providers = validateProviders();
  if (providers === false) {
    message.error('providers JSON 无效');
    return;
  }

  const payload: AdminDocConfigApi.UpdatePayload = {
    multimodal_model: multimodalModel.value,
    text_model: textModel.value,
    providers,
  };
  if (apiKey.value.trim() !== '' && !apiKey.value.includes('****')) {
    payload.api_key = apiKey.value.trim();
  }

  saving.value = true;
  try {
    const cfg = await updateDocConfigApi(payload);
    message.success('已保存');
    apiKey.value = '';
    apiKeyMasked.value = cfg?.api_key ?? apiKeyMasked.value;
    multimodalModel.value = cfg?.multimodal_model ?? multimodalModel.value;
    textModel.value = cfg?.text_model ?? textModel.value;
    if (Array.isArray(cfg?.providers)) {
      providersText.value = JSON.stringify(cfg!.providers, null, 2);
    }
    originalMultimodal.value = multimodalModel.value;
    originalText.value = textModel.value;
    originalProviders.value = providersText.value;
  } catch {
    // interceptor
  } finally {
    saving.value = false;
  }
}

function onReset() {
  apiKey.value = '';
  multimodalModel.value = originalMultimodal.value;
  textModel.value = originalText.value;
  providersText.value = originalProviders.value;
  providersError.value = '';
}

function onFormatProviders() {
  const p = validateProviders();
  if (p !== false) {
    providersText.value = JSON.stringify(p, null, 2);
    message.success('已格式化');
  } else {
    message.error('无法格式化：' + providersError.value);
  }
}

onMounted(load);
</script>

<template>
  <div class="p-6">
    <NCard title="文档 · 全局 AI 配置">
      <template #header-extra>
        <NSpace>
          <NTag v-if="dirty" type="warning" size="small">有未保存改动</NTag>
          <NButton :disabled="!dirty" @click="onReset">还原</NButton>
          <NButton
            type="primary"
            :loading="saving"
            :disabled="!dirty"
            @click="onSave"
          >
            保存
          </NButton>
        </NSpace>
      </template>

      <NAlert type="info" class="mb-4" :bordered="false">
        用于文档解析 / 搜题兜底的全局大模型 Key &amp; 模型配置。api_key 后端已脱敏返回，留空保持原值。
      </NAlert>

      <NForm label-placement="left" label-width="160px">
        <NFormItem label="api_key">
          <div class="flex flex-col w-full gap-1">
            <div class="flex items-center gap-2">
              <NInput
                v-model:value="apiKey"
                type="password"
                show-password-on="click"
                :placeholder="
                  apiKeyMasked
                    ? `当前: ${apiKeyMasked} · 留空保持原值`
                    : '未设置 · 请输入 API Key'
                "
                style="width: 420px"
              />
              <NTag v-if="apiKeyMasked" type="info" size="tiny">已保存</NTag>
            </div>
            <span class="text-xs text-muted-foreground">
              敏感字段，后端仅返回 mask；留空表示不修改
            </span>
          </div>
        </NFormItem>

        <NFormItem label="multimodal_model">
          <NInput
            v-model:value="multimodalModel"
            placeholder="如 gpt-4o / gemini-2.0-flash"
            style="width: 320px"
          />
        </NFormItem>

        <NFormItem label="text_model">
          <NInput
            v-model:value="textModel"
            placeholder="如 gpt-4o-mini"
            style="width: 320px"
          />
        </NFormItem>

        <NFormItem label="providers">
          <div class="flex flex-col w-full gap-1">
            <NInput
              v-model:value="providersText"
              type="textarea"
              :autosize="{ minRows: 6, maxRows: 20 }"
              placeholder='[{"name":"openai","base_url":"...","enabled":true}]'
              style="width: 640px; font-family: monospace"
              @blur="validateProviders"
            />
            <div class="flex items-center gap-2">
              <NButton size="tiny" @click="onFormatProviders">格式化 JSON</NButton>
              <span
                v-if="providersError"
                class="text-xs text-red-500"
              >
                {{ providersError }}
              </span>
              <span
                v-else
                class="text-xs text-muted-foreground"
              >
                必须是 JSON 数组
              </span>
            </div>
          </div>
        </NFormItem>
      </NForm>
    </NCard>
  </div>
</template>
