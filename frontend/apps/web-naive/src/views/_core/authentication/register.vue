<script lang="ts" setup>
import type { VbenFormSchema } from '@vben/common-ui';
import type { Recordable } from '@vben/types';

import { computed, h, ref } from 'vue';
import { useRouter } from 'vue-router';

import { AuthenticationRegister, z } from '@vben/common-ui';
import { $t } from '@vben/locales';
import { preferences } from '@vben/preferences';
import { useAccessStore, useUserStore } from '@vben/stores';

import { notification } from '#/adapter/naive';
import { registerApi } from '#/api';

defineOptions({ name: 'Register' });

const loading = ref(false);
const router = useRouter();
const accessStore = useAccessStore();
const userStore = useUserStore();

const formSchema = computed((): VbenFormSchema[] => {
  return [
    {
      component: 'VbenInput',
      componentProps: {
        placeholder: $t('authentication.usernameTip'),
      },
      fieldName: 'username',
      label: $t('authentication.username'),
      rules: z.string().min(1, { message: $t('authentication.usernameTip') }),
    },
    {
      component: 'VbenInputPassword',
      componentProps: {
        passwordStrength: true,
        placeholder: $t('authentication.password'),
      },
      fieldName: 'password',
      label: $t('authentication.password'),
      renderComponentContent() {
        return {
          strengthText: () => $t('authentication.passwordStrength'),
        };
      },
      rules: z.string().min(1, { message: $t('authentication.passwordTip') }),
    },
    {
      component: 'VbenInputPassword',
      componentProps: {
        placeholder: $t('authentication.confirmPassword'),
      },
      dependencies: {
        rules(values) {
          const { password } = values;
          return z
            .string({ required_error: $t('authentication.passwordTip') })
            .min(1, { message: $t('authentication.passwordTip') })
            .refine((value) => value === password, {
              message: $t('authentication.confirmPasswordTip'),
            });
        },
        triggerFields: ['password'],
      },
      fieldName: 'confirmPassword',
      label: $t('authentication.confirmPassword'),
    },
    {
      component: 'VbenCheckbox',
      fieldName: 'agreePolicy',
      renderComponentContent: () => ({
        default: () =>
          h('span', [
            $t('authentication.agree'),
            h(
              'a',
              {
                class: 'vben-link ml-1',
                href: '',
              },
              `${$t('authentication.privacyPolicy')} & ${$t('authentication.terms')}`,
            ),
          ]),
      }),
      rules: z.boolean().refine((value) => !!value, {
        message: $t('authentication.agreeTip'),
      }),
    },
  ];
});

async function handleSubmit(value: Recordable<any>) {
  // Vben 原模板此处是空函数，这里补接 /auth/register。
  // 成功后直接把后端返回的 token + userInfo + permissions 灌进 store，免去"注册完再要求登录一次"。
  loading.value = true;
  try {
    const { accessToken, userInfo, permissions } = await registerApi({
      username: String(value.username ?? '').trim(),
      password: String(value.password ?? ''),
    });
    if (!accessToken) {
      return;
    }
    accessStore.setAccessToken(accessToken);
    userStore.setUserInfo(userInfo);
    accessStore.setAccessCodes(permissions);
    notification.success({
      content: $t('authentication.loginSuccess'),
      description: userInfo.realName,
      duration: 3000,
    });
    await router.push(userInfo.homePath || preferences.app.defaultHomePath);
  } finally {
    loading.value = false;
  }
}
</script>

<template>
  <AuthenticationRegister
    :form-schema="formSchema"
    :loading="loading"
    @submit="handleSubmit"
  />
</template>
