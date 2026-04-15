<template>
  <div class="layout-shell">
    <aside class="sidebar">
      <h2>菜单</h2>
      <ul>
        <li v-for="item in normalizedMenus" :key="item.path">
          <RouterLink :to="item.path">{{ item.name }}</RouterLink>
        </li>
      </ul>
    </aside>
    <main class="content">
      <header class="topbar">
        <strong>{{ user?.nickname || user?.username || '未登录' }}</strong>
        <small>{{ roles.join(', ') }}</small>
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
const roles = computed(() => authStore.roles);
const normalizedMenus = computed(() => {
  if (authStore.menus.length) {
    return authStore.menus.map((item: any) => ({
      name: item.name,
      path: item.path.startsWith('/') ? item.path : '/' + item.path,
    }));
  }
  return [
    { name: '工作台', path: '/dashboard' },
    { name: '题目列表', path: '/admin/question' },
    { name: '公告管理', path: '/admin/announcements' },
    { name: '系统配置', path: '/admin/system-config' },
    { name: '文档管理', path: '/admin/docs' },
    { name: '采集管理', path: '/admin/collect' },
    { name: '搜题日志', path: '/logs/search' },
    { name: 'API Key', path: '/user/api-keys' },
    { name: '钱包套餐', path: '/user/billing' },
    { name: '文档中心', path: '/user/docs' },
    { name: '采集任务', path: '/user/collect' },
  ];
});

function logout() {
  authStore.logout();
  router.push('/login');
}
</script>
