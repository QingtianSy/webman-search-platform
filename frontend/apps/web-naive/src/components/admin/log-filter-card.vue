<script lang="ts" setup>
// 管理端跨用户日志筛选卡，5 个 log 页（search/balance/payment/operate/login）共用。
// 协议：
//   - 通用字段：user_id / username / 日期范围（rangeTs）
//   - 可选自定义字段通过 slot="extra" 注入（如 balance 的 type、login 的 status/ip）
//   - URL 持久化由调用方在 watch(filter) 里做；本组件只负责展示 + emit
//
// emit:
//   update:modelValue — 同步回调；父组件用 v-model 即可
//   search — 点"搜索"按钮或 Enter
//   reset — 点"重置"
//   export — 点"导出 CSV"（optional：showExport=false 隐藏）

import { computed } from 'vue';

import {
  NButton,
  NDatePicker,
  NInput,
  NSpace,
} from 'naive-ui';

export interface LogFilterModel {
  user_id?: number | null;
  username?: string;
  rangeTs?: [number, number] | null;
}

const props = withDefaults(
  defineProps<{
    modelValue: LogFilterModel;
    title?: string;
    loading?: boolean;
    exporting?: boolean;
    showExport?: boolean;
    showDelete?: boolean;
    deleteDisabled?: boolean;
  }>(),
  {
    title: '筛选',
    loading: false,
    exporting: false,
    showExport: true,
    showDelete: false,
    deleteDisabled: true,
  },
);

const emit = defineEmits<{
  (e: 'update:modelValue', v: LogFilterModel): void;
  (e: 'search'): void;
  (e: 'reset'): void;
  (e: 'export'): void;
  (e: 'delete'): void;
}>();

const filter = computed({
  get: () => props.modelValue,
  set: (v) => emit('update:modelValue', v),
});

function onUpdate<K extends keyof LogFilterModel>(
  key: K,
  value: LogFilterModel[K],
) {
  emit('update:modelValue', { ...props.modelValue, [key]: value });
}
</script>

<template>
  <div class="log-filter-card mb-4">
    <NSpace :wrap="true" align="center">
      <NInput
        :value="filter.user_id == null ? '' : String(filter.user_id)"
        placeholder="用户 ID"
        clearable
        style="width: 140px"
        @update:value="
          (v) => onUpdate('user_id', v === '' ? null : Number(v) || null)
        "
        @keydown.enter="emit('search')"
      />
      <NInput
        :value="filter.username ?? ''"
        placeholder="用户名片段"
        clearable
        style="width: 180px"
        @update:value="(v) => onUpdate('username', v)"
        @keydown.enter="emit('search')"
      />
      <NDatePicker
        :value="filter.rangeTs ?? null"
        type="datetimerange"
        clearable
        style="width: 380px"
        placeholder="时间范围"
        @update:value="(v) => onUpdate('rangeTs', v as any)"
      />

      <slot name="extra" />

      <NButton type="primary" :loading="loading" @click="emit('search')">
        搜索
      </NButton>
      <NButton @click="emit('reset')">重置</NButton>
      <NButton v-if="showExport" :loading="exporting" @click="emit('export')">
        导出 CSV
      </NButton>
      <NButton
        v-if="showDelete"
        type="error"
        ghost
        :disabled="deleteDisabled"
        @click="emit('delete')"
      >
        批量删除
      </NButton>
    </NSpace>
  </div>
</template>
