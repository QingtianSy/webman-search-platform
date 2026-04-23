<script lang="ts" setup>
import { computed } from 'vue';

import { Fallback } from '@vben/common-ui';
import { preferences } from '@vben/preferences';
import { useUserStore } from '@vben/stores';

import { $t } from '#/locales';

defineOptions({ name: 'Fallback403' });

const userStore = useUserStore();

// 按 default_portal 决定"返回首页"跳哪：admin 用户回管理台，其它回用户端 dashboard
const homePath = computed(() => {
  const portal = (userStore.userInfo as any)?.default_portal;
  if (portal === 'admin') return '/admin/dashboard';
  if (portal === 'user') return '/user/dashboard';
  return preferences.app.defaultHomePath || '/';
});
</script>

<template>
  <Fallback
    status="403"
    :title="$t('page.fallback.forbiddenTitle')"
    :description="$t('page.fallback.forbiddenDesc')"
    :home-path="homePath"
  />
</template>
