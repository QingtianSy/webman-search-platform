<script lang="ts" setup>
import { computed, onMounted, reactive, ref } from 'vue';

import { useUserStore } from '@vben/stores';

import {
  NAlert,
  NAvatar,
  NButton,
  NCard,
  NDataTable,
  NDivider,
  NForm,
  NFormItem,
  NInput,
  NSpace,
  NSpin,
  NTabs,
  NTabPane,
  NTag,
  NUpload,
  useMessage,
  type FormInst,
  type UploadCustomRequestOptions,
} from 'naive-ui';

import {
  changePasswordApi,
  getMyRawProfileApi,
  invalidateOtherSessionsApi,
  updateProfileApi,
  uploadAvatarApi,
} from '#/api/core/user';
import { listLoginLogsApi, type UserLogApi } from '#/api/user/log';

const userStore = useUserStore();
const message = useMessage();

const info = computed(() => userStore.userInfo ?? null);

// 基本资料
const profileForm = reactive({
  nickname: '',
  email: '',
  mobile: '',
  avatar: '',
});
const profileSaving = ref(false);
/** 头像：后端未实现上传接口时禁掉控件，保留展示 */
const avatarUploading = ref(false);
const avatarUploadSupported = ref(true);

async function syncProfile() {
  // 基础字段先从 store 拿，立即显示
  profileForm.nickname = info.value?.realName ?? info.value?.username ?? '';
  profileForm.avatar = info.value?.avatar ?? '';
  // email/mobile/avatar 不在 Vben UserInfo 里，单独拉一次原始 payload
  try {
    const raw = await getMyRawProfileApi();
    profileForm.nickname = raw.nickname || raw.username || profileForm.nickname;
    profileForm.email = raw.email ?? '';
    profileForm.mobile = raw.mobile ?? '';
    profileForm.avatar = raw.avatar ?? profileForm.avatar;
  } catch {
    // 失败不影响改密 / 会话 Tab，保持空串
  }
}

/** NUpload custom-request：上传图片 → 拿 url → 立即 updateProfile 落库 → 刷新 store */
async function onAvatarUpload(options: UploadCustomRequestOptions) {
  const { file, onFinish, onError } = options;
  const raw = (file.file as File | null) ?? null;
  if (!raw) {
    onError();
    return;
  }
  // 前端做一层大小/类型校验，避免无效请求
  if (!/^image\/(jpe?g|png|webp|gif)$/i.test(raw.type)) {
    message.warning('仅支持 jpg/png/webp/gif 图片');
    onError();
    return;
  }
  if (raw.size > 2 * 1024 * 1024) {
    message.warning('头像不得超过 2MB');
    onError();
    return;
  }
  avatarUploading.value = true;
  try {
    const { url } = await uploadAvatarApi(raw);
    profileForm.avatar = url;
    // 落库：同步 nickname/email/mobile 避免后端覆盖；avatar 单独传一次更安全
    await updateProfileApi({ avatar: url });
    // 同步到全局 store，菜单右上角头像立即更新
    if (userStore.userInfo) {
      userStore.setUserInfo({ ...userStore.userInfo, avatar: url });
    }
    message.success('头像已更新');
    onFinish();
  } catch {
    avatarUploadSupported.value = false;
    message.error('上传失败（后端暂未实现 /auth/upload-avatar）');
    onError();
  } finally {
    avatarUploading.value = false;
  }
}

async function saveProfile() {
  profileSaving.value = true;
  try {
    await updateProfileApi({
      nickname: profileForm.nickname,
      email: profileForm.email,
      mobile: profileForm.mobile,
      avatar: profileForm.avatar || undefined,
    });
    message.success('资料已更新');
  } catch {
    message.error('保存失败（后端可能未实现）');
  } finally {
    profileSaving.value = false;
  }
}

// 修改密码
const pwdRef = ref<FormInst | null>(null);
const pwdForm = reactive({
  old_password: '',
  new_password: '',
  confirm: '',
});
const pwdSaving = ref(false);
const pwdRules = {
  old_password: { required: true, message: '请输入当前密码', trigger: 'blur' },
  new_password: [
    { required: true, message: '请输入新密码', trigger: 'blur' },
    { min: 8, message: '新密码至少 8 个字符', trigger: 'blur' },
  ],
  confirm: [
    { required: true, message: '请再次输入新密码', trigger: 'blur' },
    {
      validator: (_r: unknown, value: string) =>
        value === pwdForm.new_password || new Error('两次输入不一致'),
      trigger: ['blur', 'input'],
    },
  ],
};

async function submitPwd() {
  try {
    await pwdRef.value?.validate();
  } catch {
    return;
  }
  pwdSaving.value = true;
  try {
    await changePasswordApi({
      old_password: pwdForm.old_password,
      new_password: pwdForm.new_password,
    });
    message.success('密码已更新，请重新登录');
    pwdForm.old_password = '';
    pwdForm.new_password = '';
    pwdForm.confirm = '';
  } catch {
    message.error('修改失败，请检查当前密码');
  } finally {
    pwdSaving.value = false;
  }
}

// 会话管理
const loginLogs = ref<UserLogApi.LoginLog[]>([]);
const logsLoading = ref(false);
const logoutOthersSupported = ref(true);

async function loadLogs() {
  logsLoading.value = true;
  try {
    const r = await listLoginLogsApi({ page: 1, page_size: 10 });
    loginLogs.value = r.list ?? [];
  } finally {
    logsLoading.value = false;
  }
}

async function logoutOthers() {
  try {
    await invalidateOtherSessionsApi();
    message.success('已注销其他会话');
  } catch {
    logoutOthersSupported.value = false;
    message.warning('后端暂未实现会话失效接口');
  }
}

const logColumns = [
  {
    title: '结果',
    key: 'status',
    width: 80,
    render: (row: UserLogApi.LoginLog) =>
      row.status === 1 ? '成功' : '失败',
  },
  { title: 'IP', key: 'ip', width: 140 },
  { title: 'UA', key: 'user_agent', ellipsis: { tooltip: true } },
  { title: '时间', key: 'created_at', width: 180 },
];

onMounted(() => {
  syncProfile();
  loadLogs();
});
</script>

<template>
  <div class="p-6">
    <NCard :bordered="false" size="small">
      <NTabs type="line" animated default-value="profile">
        <NTabPane name="profile" tab="基本资料">
          <NAlert type="info" :show-icon="false" class="mb-3">
            昵称/邮箱/手机号可供找回密码和重要通知使用，请保证准确。
          </NAlert>

          <!-- 头像上传 -->
          <div class="avatar-row mb-4">
            <NAvatar
              :size="72"
              :src="profileForm.avatar || undefined"
              round
              fallback-src="/assets/dashboard-mascot.svg"
            >
              {{ (profileForm.nickname || info?.username || '?').slice(0, 1) }}
            </NAvatar>
            <div class="avatar-action">
              <NUpload
                :show-file-list="false"
                accept="image/jpeg,image/png,image/webp,image/gif"
                :custom-request="onAvatarUpload"
                :disabled="!avatarUploadSupported || avatarUploading"
              >
                <NButton size="small" :loading="avatarUploading">
                  {{ profileForm.avatar ? '更换头像' : '上传头像' }}
                </NButton>
              </NUpload>
              <div class="avatar-hint">JPG / PNG / WEBP，≤ 2MB</div>
              <div v-if="!avatarUploadSupported" class="avatar-hint text-warn">
                后端暂未实现上传接口
              </div>
            </div>
          </div>

          <NForm label-placement="left" label-width="100" style="max-width: 520px">
            <NFormItem label="账号">
              <NInput :value="info?.username ?? ''" disabled />
            </NFormItem>
            <NFormItem label="昵称">
              <NInput
                v-model:value="profileForm.nickname"
                placeholder="昵称"
              />
            </NFormItem>
            <NFormItem label="邮箱">
              <NInput
                v-model:value="profileForm.email"
                placeholder="name@example.com"
              />
            </NFormItem>
            <NFormItem label="手机号">
              <NInput
                v-model:value="profileForm.mobile"
                placeholder="手机号"
              />
            </NFormItem>
            <div>
              <NButton
                type="primary"
                :loading="profileSaving"
                @click="saveProfile"
              >
                保存
              </NButton>
            </div>
          </NForm>
        </NTabPane>

        <NTabPane name="password" tab="修改密码">
          <NForm
            ref="pwdRef"
            :model="pwdForm"
            :rules="pwdRules"
            label-placement="left"
            label-width="110"
            style="max-width: 520px"
          >
            <NFormItem label="当前密码" path="old_password">
              <NInput
                v-model:value="pwdForm.old_password"
                type="password"
                show-password-on="click"
              />
            </NFormItem>
            <NFormItem label="新密码" path="new_password">
              <NInput
                v-model:value="pwdForm.new_password"
                type="password"
                show-password-on="click"
              />
            </NFormItem>
            <NFormItem label="确认新密码" path="confirm">
              <NInput
                v-model:value="pwdForm.confirm"
                type="password"
                show-password-on="click"
              />
            </NFormItem>
            <div>
              <NButton type="primary" :loading="pwdSaving" @click="submitPwd">
                更新密码
              </NButton>
            </div>
          </NForm>
        </NTabPane>

        <NTabPane name="session" tab="会话管理">
          <NSpace class="mb-3">
            <NButton
              type="warning"
              ghost
              :disabled="!logoutOthersSupported"
              @click="logoutOthers"
            >
              注销其他会话
            </NButton>
            <NTag v-if="!logoutOthersSupported" size="small" type="default">
              后端暂未实现
            </NTag>
          </NSpace>
          <NDivider />
          <div class="section-title">最近登录记录（Top 10）</div>
          <NSpin :show="logsLoading">
            <NDataTable
              :columns="logColumns"
              :data="loginLogs"
              :row-key="(r: UserLogApi.LoginLog) => r.id"
              size="small"
              :bordered="false"
            />
          </NSpin>
        </NTabPane>
      </NTabs>
    </NCard>
  </div>
</template>

<style scoped>
.section-title {
  font-size: 13px;
  font-weight: 600;
  color: #2080f0;
  margin: 8px 0;
  padding-left: 6px;
  border-left: 3px solid #2080f0;
}
.mb-3 {
  margin-bottom: 12px;
}
.mb-4 {
  margin-bottom: 16px;
}
.avatar-row {
  display: flex;
  align-items: center;
  gap: 16px;
}
.avatar-action {
  display: flex;
  flex-direction: column;
  gap: 4px;
}
.avatar-hint {
  font-size: 12px;
  color: #999;
}
.avatar-hint.text-warn {
  color: #f0a020;
}
</style>
