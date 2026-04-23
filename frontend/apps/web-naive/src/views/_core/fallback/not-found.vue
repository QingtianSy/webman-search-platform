<script lang="ts" setup>
import { computed } from 'vue';

import { Fallback } from '@vben/common-ui';
import { preferences } from '@vben/preferences';
import { useUserStore } from '@vben/stores';

import { $t } from '#/locales';

defineOptions({ name: 'Fallback404' });

const userStore = useUserStore();

const homePath = computed(() => {
  const portal = (userStore.userInfo as any)?.default_portal;
  if (portal === 'admin') return '/admin/dashboard';
  if (portal === 'user') return '/user/dashboard';
  return preferences.app.defaultHomePath || '/';
});
</script>

<template>
  <Fallback
    status="404"
    :title="$t('page.fallback.notFoundTitle')"
    :description="$t('page.fallback.notFoundDesc')"
    :home-path="homePath"
  />
</template>
