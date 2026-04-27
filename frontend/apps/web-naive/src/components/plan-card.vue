<script lang="ts" setup>
import { computed, h } from 'vue';

import { NButton, NIcon, NTag } from 'naive-ui';

import { derivePlanType, type PlanApi } from '#/api/user/plan';

interface Props {
  plan: PlanApi.Plan;
  /** 按钮文案，默认「立即购买」。续费场景传「续费」。 */
  actionText?: string;
  /** 是否高亮（被选中/当前套餐） */
  active?: boolean;
  /** 禁用按钮 */
  disabled?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
  actionText: '立即购买',
  active: false,
  disabled: false,
});

const emit = defineEmits<{
  (e: 'action', plan: PlanApi.Plan): void;
}>();

const features = computed<string[]>(() => {
  if (Array.isArray(props.plan.features) && props.plan.features.length > 0) {
    return props.plan.features.slice(0, 5);
  }
  const f: string[] = [];
  if (props.plan.is_unlimited) f.push('API 调用：不限次');
  else if (props.plan.quota) f.push(`API 调用：${props.plan.quota} 次`);
  if (props.plan.duration) f.push(`有效期：${props.plan.duration} 天`);
  else if (derivePlanType(props.plan) === 'exhaustive') f.push('有效期：永久');
  f.push('技术支持：标准');
  return f.slice(0, 5);
});

const durationLabel = computed(() => {
  const t = derivePlanType(props.plan);
  if (t === 'exhaustive') return '永久';
  const d = Number(props.plan.duration);
  if (!Number.isFinite(d) || d <= 0) return '';
  if (d >= 30 && d % 30 === 0) return `${d / 30} 个月`;
  if (d >= 365 && d % 365 === 0) return `${d / 365} 年`;
  return `${d} 天`;
});

const hasOriginal = computed(() => {
  const o = Number(props.plan.original_price ?? 0);
  const p = Number(props.plan.price ?? 0);
  return o > 0 && o > p;
});

function onClick() {
  if (props.disabled) return;
  emit('action', props.plan);
}

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
  <div class="plan-card" :class="{ recommended: plan.is_recommended, active }">
    <span v-if="plan.is_recommended" class="ribbon">推荐</span>

    <div class="plan-name">{{ plan.name }}</div>
    <div v-if="plan.description" class="plan-desc">{{ plan.description }}</div>

    <div class="plan-price-row">
      <span class="plan-price">¥ {{ Number(plan.price).toFixed(2) }}</span>
      <span v-if="durationLabel" class="plan-duration">/ {{ durationLabel }}</span>
    </div>
    <div v-if="hasOriginal" class="plan-original">
      原价 ¥ {{ Number(plan.original_price).toFixed(2) }}
    </div>

    <ul class="plan-features">
      <li v-for="(f, i) in features" :key="i">
        <NIcon :component="CheckIcon" class="text-success" />
        <span>{{ f }}</span>
      </li>
    </ul>

    <NTag v-if="plan.sold_count" size="small" class="sold-tag" round>
      已售 {{ plan.sold_count }}
    </NTag>

    <NButton
      block
      :type="plan.is_recommended || active ? 'primary' : 'default'"
      :disabled="disabled"
      class="mt-3"
      @click="onClick"
    >
      {{ actionText }}
    </NButton>
  </div>
</template>

<style scoped>
.plan-card {
  position: relative;
  padding: 24px 20px;
  background: #fff;
  border: 1px solid #eee;
  border-radius: 8px;
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
.plan-card.active {
  border-color: #18a058;
  box-shadow: 0 0 0 2px rgba(24, 160, 88, 0.15);
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
.plan-desc {
  margin-top: 4px;
  text-align: center;
  font-size: 12px;
  color: #999;
  min-height: 16px;
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
.plan-original {
  text-align: center;
  font-size: 12px;
  color: #bbb;
  text-decoration: line-through;
  margin-top: 2px;
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
.sold-tag {
  margin-top: 8px;
}
</style>
