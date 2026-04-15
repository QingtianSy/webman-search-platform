<template>
  <div class="page login-page">
    <h1>统一登录</h1>
    <p>登录后根据 roles / permissions / menus / default_portal 决定默认进入位置。</p>
    <div class="form-card">
      <label>
        账号
        <input v-model="form.username" placeholder="请输入账号" />
      </label>
      <label>
        密码
        <input v-model="form.password" type="password" placeholder="请输入密码" />
      </label>
      <button @click="handleLogin">登录</button>
      <pre>{{ result }}</pre>
    </div>
  </div>
</template>

<script setup lang="ts">
import { reactive, ref } from 'vue';
import { useRouter } from 'vue-router';
import { authLogin } from '../../api/auth';
import { useAuthStore } from '../../stores/auth';

const router = useRouter();
const authStore = useAuthStore();
const form = reactive({ username: 'demo_user', password: '123456' });
const result = ref('');

async function handleLogin() {
  try {
    const { data } = await authLogin(form);
    authStore.setAuthPayload(data.data);
    result.value = JSON.stringify(data, null, 2);
    if (data.data.default_portal === 'admin') {
      router.push('/admin/question');
    } else {
      router.push('/dashboard');
    }
  } catch (error: any) {
    result.value = String(error);
  }
}
</script>
