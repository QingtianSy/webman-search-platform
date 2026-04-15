<template>
  <div class="page dashboard-page">
    <h1>工作台</h1>
    <p>统一用户体系下，按 roles / permissions / menus 动态展示内容。</p>

    <div class="cards">
      <div class="stat-card">
        <div class="label">账户余额</div>
        <div class="value">¥ {{ overview?.balance || '0.00' }}</div>
      </div>
      <div class="stat-card">
        <div class="label">当前套餐</div>
        <div class="value">{{ overview?.current_plan?.name || '无套餐' }}</div>
      </div>
      <div class="stat-card">
        <div class="label">剩余额度</div>
        <div class="value">{{ overview?.current_plan?.remain_quota ?? 0 }}</div>
      </div>
      <div class="stat-card">
        <div class="label">累计使用</div>
        <div class="value">{{ overview?.total_usage ?? 0 }}</div>
      </div>
    </div>

    <div class="panel">
      <h2>通知公告</h2>
      <ul>
        <li v-for="item in overview?.announcements || []" :key="item.id">
          <strong>{{ item.title }}</strong>
          <div>{{ item.content }}</div>
        </li>
      </ul>
    </div>
  </div>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue';
import { getDashboardOverview } from '../../api/business';

const overview = ref<any>(null);

onMounted(async () => {
  try {
    const { data } = await getDashboardOverview();
    overview.value = data.data || null;
  } catch (error) {
    console.error(error);
  }
});
</script>
