<script lang="ts" setup>
import type { VbenFormSchema } from '@vben/common-ui';
import type { Recordable } from '@vben/types';

import { computed, h, ref } from 'vue';
import { useRouter } from 'vue-router';

import { AuthenticationRegister, z } from '@vben/common-ui';
import { $t as $tCore } from '@vben/locales';
import { preferences } from '@vben/preferences';
import { useAccessStore, useUserStore } from '@vben/stores';

import { notification } from '#/adapter/naive';
import { registerApi } from '#/api';
import { $t } from '#/locales';

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
        placeholder: $tCore('authentication.usernameTip'),
      },
      fieldName: 'username',
      label: $tCore('authentication.username'),
      rules: z
        .string()
        .min(3, { message: $tCore('authentication.usernameTip') })
        .max(50, { message: $tCore('authentication.usernameTip') }),
    },
    {
      component: 'VbenInput',
      componentProps: {
        placeholder: '昵称（可选）',
      },
      fieldName: 'nickname',
      label: '昵称',
      rules: z.string().max(50).optional(),
    },
    {
      component: 'VbenInputPassword',
      componentProps: {
        passwordStrength: true,
        placeholder: $tCore('authentication.password'),
      },
      fieldName: 'password',
      label: $tCore('authentication.password'),
      renderComponentContent() {
        return {
          strengthText: () => $tCore('authentication.passwordStrength'),
        };
      },
      rules: z
        .string()
        .min(6, { message: $tCore('authentication.passwordTip') }),
    },
    {
      component: 'VbenInputPassword',
      componentProps: {
        placeholder: $tCore('authentication.confirmPassword'),
      },
      dependencies: {
        rules(values) {
          const { password } = values;
          return z
            .string({ required_error: $tCore('authentication.passwordTip') })
            .min(1, { message: $tCore('authentication.passwordTip') })
            .refine((value) => value === password, {
              message: $tCore('authentication.confirmPasswordTip'),
            });
        },
        triggerFields: ['password'],
      },
      fieldName: 'confirmPassword',
      label: $tCore('authentication.confirmPassword'),
    },
    {
      component: 'VbenCheckbox',
      fieldName: 'agreePolicy',
      renderComponentContent: () => ({
        default: () =>
          h('span', [
            $tCore('authentication.agree'),
            h(
              'a',
              {
                class: 'vben-link ml-1',
                href: '',
              },
              `${$tCore('authentication.privacyPolicy')} & ${$tCore('authentication.terms')}`,
            ),
          ]),
      }),
      rules: z.boolean().refine((value) => !!value, {
        message: $tCore('authentication.agreeTip'),
      }),
    },
  ];
});

async function handleSubmit(value: Recordable<any>) {
  // Vben 原模板此处是空函数，这里补接 /auth/register。
  // 成功后直接把后端返回的 token + userInfo + permissions 灌进 store，免去"注册完再要求登录一次"。
  loading.value = true;
  try {
    const nickname = String(value.nickname ?? '').trim();
    const { accessToken, userInfo, permissions } = await registerApi({
      username: String(value.username ?? '').trim(),
      password: String(value.password ?? ''),
      ...(nickname ? { nickname } : {}),
    });
    if (!accessToken) {
      return;
    }
    accessStore.setAccessToken(accessToken);
    userStore.setUserInfo(userInfo);
    accessStore.setAccessCodes(permissions);
    notification.success({
      content: $t('page.auth.registerSuccess'),
      description: `${$t('page.auth.registerSuccessDesc')}: ${userInfo.realName}`,
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
