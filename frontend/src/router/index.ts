import { createRouter, createWebHistory } from 'vue-router';
import LoginView from '../views/auth/LoginView.vue';
import DashboardView from '../views/dashboard/DashboardView.vue';
import QuestionListView from '../views/question/QuestionListView.vue';
import SearchLogView from '../views/logs/SearchLogView.vue';

const router = createRouter({
  history: createWebHistory(),
  routes: [
    { path: '/login', name: 'login', component: LoginView },
    { path: '/', redirect: '/dashboard' },
    { path: '/dashboard', name: 'dashboard', component: DashboardView },
    { path: '/admin/question', name: 'question-list', component: QuestionListView },
    { path: '/logs/search', name: 'search-log', component: SearchLogView },
  ],
});

export default router;
