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
import { filterMenusByPermissions, normalizeMenus } from '../utils/menu';
import type { AppMenuItem } from '../types/menu';

const authStore = useAuthStore();
const router = useRouter();
const user = computed(() => authStore.user);
const roles = computed(() => authStore.roles);

const fallbackMenus: AppMenuItem[] = [
  { name: '工作台', path: '/dashboard', permission_code: 'portal.access' },
  { name: '题目列表', path: '/admin/question', permission_code: 'question.manage' },
  { name: '公告管理', path: '/admin/announcements', permission_code: 'admin.access' },
  { name: '系统配置', path: '/admin/system-config', permission_code: 'system.config' },
  { name: '文档管理', path: '/admin/docs', permission_code: 'admin.access' },
  { name: '采集管理', path: '/admin/collect', permission_code: 'admin.access' },
  { name: '搜题日志', path: '/logs/search', permission_code: 'search.query' },
  { name: 'API Key', path: '/user/api-keys', permission_code: 'portal.access' },
  { name: '钱包套餐', path: '/user/billing', permission_code: 'portal.access' },
  { name: '文档中心', path: '/user/docs', permission_code: 'portal.access' },
  { name: '采集任务', path: '/user/collect', permission_code: 'portal.access' },
];

const menus = computed(() => {
  const source = authStore.menus.length ? normalizeMenus(authStore.menus) : fallbackMenus;
  return filterMenusByPermissions(source, authStore.permissions);
});

function logout() {
  authStore.logout();
  router.push('/login');
}
</script>
