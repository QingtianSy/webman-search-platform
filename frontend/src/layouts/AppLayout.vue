<template>
  <div class="layout-shell">
    <aside class="sidebar">
      <h2>菜单</h2>
      <ul>
        <li v-for="item in menus" :key="item.path">
          <RouterLink :to="item.path">{{ item.name }}</RouterLink>
        </li>
      </ul>
    </aside>
    <main class="content">
      <header class="topbar">
        <strong>{{ user?.nickname || user?.username || '未登录' }}</strong>
        <button @click="logout">退出</button>
      </header>
      <section class="page-container">
        <router-view />
      </section>
    </main>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../stores/auth';

const authStore = useAuthStore();
const router = useRouter();
const user = computed(() => authStore.user);
const menus = computed(() => authStore.menus.length ? authStore.menus : [
  { name: '工作台', path: '/dashboard' },
  { name: '题目列表', path: '/admin/question' },
  { name: '搜题日志', path: '/logs/search' },
  { name: 'API Key', path: '/user/api-keys' },
  { name: '钱包套餐', path: '/user/billing' },
  { name: '文档中心', path: '/user/docs' },
  { name: '采集任务', path: '/user/collect' },
]);

function logout() {
  authStore.logout();
  router.push('/login');
}
</script>
