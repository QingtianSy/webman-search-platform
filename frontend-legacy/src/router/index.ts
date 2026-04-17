import { createRouter, createWebHistory } from 'vue-router';
import AppLayout from '../layouts/AppLayout.vue';
import LoginView from '../views/auth/LoginView.vue';
import DashboardView from '../views/dashboard/DashboardView.vue';
import QuestionListView from '../views/question/QuestionListView.vue';
import SearchLogView from '../views/logs/SearchLogView.vue';
import ApiKeyListView from '../views/user/ApiKeyListView.vue';
import BillingView from '../views/user/BillingView.vue';
import DocCenterView from '../views/user/DocCenterView.vue';
import CollectTaskView from '../views/user/CollectTaskView.vue';
import AnnouncementManageView from '../views/admin/AnnouncementManageView.vue';
import SystemConfigView from '../views/admin/SystemConfigView.vue';
import DocManageView from '../views/admin/DocManageView.vue';
import CollectManageView from '../views/admin/CollectManageView.vue';
import UserManageView from '../views/admin/UserManageView.vue';
import RoleManageView from '../views/admin/RoleManageView.vue';
import PermissionManageView from '../views/admin/PermissionManageView.vue';
import MenuManageView from '../views/admin/MenuManageView.vue';
import PlanManageView from '../views/admin/PlanManageView.vue';

const router = createRouter({
  history: createWebHistory(),
  routes: [
    { path: '/login', name: 'login', component: LoginView },
    {
      path: '/',
      component: AppLayout,
      children: [
        { path: '', redirect: '/dashboard' },
        { path: 'dashboard', name: 'dashboard', component: DashboardView },
        { path: 'admin/question', name: 'question-list', component: QuestionListView },
        { path: 'admin/announcements', name: 'announcement-manage', component: AnnouncementManageView },
        { path: 'admin/system-config', name: 'system-config', component: SystemConfigView },
        { path: 'admin/docs', name: 'doc-manage', component: DocManageView },
        { path: 'admin/collect', name: 'collect-manage', component: CollectManageView },
        { path: 'admin/users', name: 'user-manage', component: UserManageView },
        { path: 'admin/roles', name: 'role-manage', component: RoleManageView },
        { path: 'admin/permissions', name: 'permission-manage', component: PermissionManageView },
        { path: 'admin/menus', name: 'menu-manage', component: MenuManageView },
        { path: 'admin/plans', name: 'plan-manage', component: PlanManageView },
        { path: 'logs/search', name: 'search-log', component: SearchLogView },
        { path: 'user/api-keys', name: 'api-keys', component: ApiKeyListView },
        { path: 'user/billing', name: 'billing', component: BillingView },
        { path: 'user/docs', name: 'docs', component: DocCenterView },
        { path: 'user/collect', name: 'collect', component: CollectTaskView },
      ],
    },
  ],
});

router.beforeEach((to) => {
  const token = localStorage.getItem('token');
  if (to.path !== '/login' && !token) {
    return '/login';
  }
  return true;
});

export default router;
