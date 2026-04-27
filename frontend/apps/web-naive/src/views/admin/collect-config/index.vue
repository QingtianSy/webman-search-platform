<script lang="ts" setup>
// 管理端 · 采集配置。docs/07 §3.2.21。
// 分组 Tab（并发/速率/代理/其它） · 单项改动黄点 · 保存后刷新 · dry-run 留 P3
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
  NSwitch,
  NTabs,
  NTabPane,
  NTag,
  useMessage,
} from 'naive-ui';

import {
  type AdminCollectConfigApi,
  listCollectConfigApi,
  updateCollectConfigApi,
} from '#/api/admin';

const message = useMessage();

const loading = ref(false);
const items = ref<AdminCollectConfigApi.Item[]>([]);
const original = reactive<Record<string, string>>({});
const current = reactive<Record<string, string>>({});

interface FieldDef {
  key: string;
  label: string;
  group: 'concurrency' | 'rate' | 'proxy' | 'misc';
  type: 'number' | 'string' | 'switch';
  min?: number;
  max?: number;
  hint?: string;
}

const FIELDS: FieldDef[] = [
  { key: 'collect_concurrency', label: '采集并发', group: 'concurrency', type: 'number', min: 1, max: 50, hint: '同时执行的采集任务数' },
  { key: 'collect_course_concurrency', label: '课程并发', group: 'concurrency', type: 'number', min: 1, max: 20, hint: '单任务内课程级并发' },
  { key: 'collect_progress_interval', label: '进度刷新(秒)', group: 'concurrency', type: 'number', min: 1, max: 60 },
  { key: 'collect_request_interval_ms', label: '请求间隔(ms)', group: 'rate', type: 'number', min: 0, max: 10_000, hint: '两次请求最小间隔' },
  { key: 'collect_timeout_seconds', label: '请求超时(秒)', group: 'rate', type: 'number', min: 1, max: 600 },
  { key: 'collect_rate_backoff_ms', label: '限流退避(ms)', group: 'rate', type: 'number', min: 0, max: 60_000 },
  { key: 'collect_rate_recovery_count', label: '恢复计数', group: 'rate', type: 'number', min: 1, max: 1000 },
  { key: 'collect_login_max_attempts', label: '登录最多重试', group: 'rate', type: 'number', min: 1, max: 20 },
  { key: 'collect_proxy_enabled', label: '启用代理', group: 'proxy', type: 'switch' },
  { key: 'collect_proxy_cooldown_min', label: '代理冷却(分钟)', group: 'proxy', type: 'number', min: 0, max: 1440 },
  { key: 'collect_separator', label: '答案分隔符', group: 'misc', type: 'string' },
  { key: 'collect_output_mode', label: '输出模式', group: 'misc', type: 'string', hint: '如 json / plain' },
];

const saving = ref(false);

async function load() {
  loading.value = true;
  try {
    const data = await listCollectConfigApi();
    items.value = Array.isArray(data) ? data : (data as any)?.list ?? [];
    for (const k of Object.keys(original)) delete original[k];
    for (const k of Object.keys(current)) delete current[k];
    for (const it of items.value) {
      original[it.config_key] = it.config_value ?? '';
      current[it.config_key] = it.config_value ?? '';
    }
    // 补齐 FIELDS 缺省（后端可能未 seed）
    for (const f of FIELDS) {
      if (!(f.key in current)) {
        const fallback = f.type === 'switch' ? '0' : f.type === 'number' ? '0' : '';
        original[f.key] = fallback;
        current[f.key] = fallback;
      }
    }
  } catch {
    message.error('加载失败');
  } finally {
    loading.value = false;
  }
}

const isDirty = (key: string) => original[key] !== current[key];
const dirtyCount = computed(
  () => FIELDS.filter((f) => isDirty(f.key)).length,
);

async function saveOne(key: string) {
  saving.value = true;
  try {
    await updateCollectConfigApi(key, current[key] ?? '');
    original[key] = current[key] ?? '';
    message.success(`${key} 已保存`);
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
      await updateCollectConfigApi(f.key, current[f.key] ?? '');
      original[f.key] = current[f.key] ?? '';
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
  for (const f of FIELDS) current[f.key] = original[f.key] ?? '';
}

onMounted(load);

const groups: { name: string; label: string }[] = [
  { name: 'concurrency', label: '并发' },
  { name: 'rate', label: '速率 & 超时' },
  { name: 'proxy', label: '代理' },
  { name: 'misc', label: '其它' },
];

const fieldsByGroup = (g: string) => FIELDS.filter((f) => f.group === g);
</script>

<template>
  <div class="p-6">
    <NCard title="采集配置">
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
        修改后立即对正在运行的采集任务生效（下一次心跳读取）。并发上调请谨慎，避免 API 源触发风控。
      </NAlert>

      <NTabs type="line" default-value="concurrency">
        <NTabPane
          v-for="g in groups"
          :key="g.name"
          :name="g.name"
          :tab="g.label"
        >
          <NForm label-placement="left" label-width="160px">
            <NFormItem
              v-for="f in fieldsByGroup(g.name)"
              :key="f.key"
              :label="f.label"
            >
              <div class="flex items-center gap-2 w-full">
                <template v-if="f.type === 'number'">
                  <NInputNumber
                    :value="Number(current[f.key] ?? 0)"
                    :min="f.min"
                    :max="f.max"
                    style="width: 180px"
                    @update:value="(v) => (current[f.key] = String(v ?? 0))"
                  />
                </template>
                <template v-else-if="f.type === 'switch'">
                  <NSwitch
                    :value="current[f.key] === '1' || current[f.key] === 'true'"
                    @update:value="
                      (v) => (current[f.key] = v ? '1' : '0')
                    "
                  />
                </template>
                <template v-else>
                  <NInput
                    v-model:value="current[f.key]"
                    style="width: 240px"
                  />
                </template>

                <NTag
                  v-if="isDirty(f.key)"
                  type="warning"
                  size="tiny"
                >
                  改动
                </NTag>
                <span class="text-xs text-muted-foreground">
                  {{ f.hint ?? '' }}
                  <span class="ml-2">key: <code>{{ f.key }}</code></span>
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
