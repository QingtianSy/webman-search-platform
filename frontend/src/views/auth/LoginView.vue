<template>
  <div class="page login-page">
    <h1>统一登录</h1>
    <p>一个登录页，登录后按角色与权限决定默认入口。</p>
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
import { authLogin } from '../../api/auth';

const form = reactive({ username: 'demo_user', password: '123456' });
const result = ref('');

async function handleLogin() {
  try {
    const { data } = await authLogin(form);
    localStorage.setItem('token', data.data.token);
    result.value = JSON.stringify(data, null, 2);
  } catch (error: any) {
    result.value = String(error);
  }
}
</script>
