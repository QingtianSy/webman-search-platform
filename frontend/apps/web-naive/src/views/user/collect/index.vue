<script lang="ts" setup>
import type { DataTableColumns } from 'naive-ui';

import { computed, h, onMounted, onUnmounted, ref } from 'vue';

import {
  NAlert,
  NButton,
  NCard,
  NDataTable,
  NDescriptions,
  NDescriptionsItem,
  NForm,
  NFormItem,
  NInput,
  NModal,
  NSelect,
  NSpace,
  NTag,
  useMessage,
} from 'naive-ui';

import {
  getCollectTaskApi,
  listCollectTasksApi,
  queryCoursesApi,
  submitCollectApi,
  type UserCollectApi,
} from '#/api/user/collect';

const message = useMessage();

// ---- 任务列表 ----
const loading = ref(false);
const rows = ref<UserCollectApi.TaskItem[]>([]);
const total = ref(0);
const page = ref(1);
const pageSize = ref(20);

async function loadList() {
  loading.value = true;
  try {
    const data = await listCollectTasksApi({
      page: page.value,
      page_size: pageSize.value,
    });
    rows.value = data.list ?? [];
    total.value = data.total ?? 0;
  } catch {
    message.error('任务列表加载失败');
  } finally {
    loading.value = false;
  }
}

function onPageChange(p: number) {
  page.value = p;
  loadList();
}
function onPageSizeChange(ps: number) {
  pageSize.value = ps;
  page.value = 1;
  loadList();
}

// ---- 提交表单 ----
const submitOpen = ref(false);
const submitting = ref(false);
const queryingCourses = ref(false);
const coursesResult = ref<null | UserCollectApi.QueryCoursesResult>(null);
const selectedCourseIds = ref<string[]>([]);

const form = ref<UserCollectApi.SubmitParams>({
  account: '',
  password: '',
  collect_type: 'courses',
  course_ids: '',
  course_count: 0,
  school_name: '',
});

const typeOptions = [
  { label: '全量课程（courses）', value: 'courses' },
  { label: '单门课程（course）', value: 'course' },
  { label: '章节（chapter）', value: 'chapter' },
  { label: '考试（exam）', value: 'exam' },
  { label: '作业（homework）', value: 'homework' },
];

function resetForm() {
  form.value = {
    account: '',
    password: '',
    collect_type: 'courses',
    course_ids: '',
    course_count: 0,
    school_name: '',
  };
  coursesResult.value = null;
  selectedCourseIds.value = [];
}

async function onQueryCourses() {
  if (!form.value.account || !form.value.password) {
    message.warning('请先填写账号和密码');
    return;
  }
  queryingCourses.value = true;
  try {
    const res = await queryCoursesApi({
      account: form.value.account,
      password: form.value.password,
    });
    coursesResult.value = res;
    if (res.schoolName) {
      form.value.school_name = res.schoolName;
    }
    message.success(`查询到 ${res.courseCount} 门课程`);
  } catch {
    // 拦截器已 toast
  } finally {
    queryingCourses.value = false;
  }
}

const courseSelectOptions = computed(() =>
  (coursesResult.value?.courses ?? []).map((c) => ({
    label: `${c.courseName ?? '(未命名)'} · ${c.courseId ?? ''}`,
    value: String(c.courseId ?? c.clazzId ?? ''),
  })),
);

async function onSubmit() {
  if (!form.value.account || !form.value.password) {
    message.warning('请填写账号和密码');
    return;
  }
  // 非全量类型至少要指定 ids
  if (
    form.value.collect_type !== 'courses' &&
    selectedCourseIds.value.length === 0 &&
    !form.value.course_ids
  ) {
    message.warning('请选择或填写要采集的课程 ID');
    return;
  }
  submitting.value = true;
  try {
    const payload: UserCollectApi.SubmitParams = {
      ...form.value,
      course_ids:
        selectedCourseIds.value.length > 0
          ? selectedCourseIds.value.join(',')
          : form.value.course_ids,
      course_count:
        selectedCourseIds.value.length > 0
          ? selectedCourseIds.value.length
          : form.value.course_count,
    };
    const res = await submitCollectApi(payload);
    message.success(`任务已提交：${res.task_no}`);
    submitOpen.value = false;
    resetForm();
    loadList();
  } catch {
    // ignored
  } finally {
    submitting.value = false;
  }
}

// ---- 详情 + 轮询 ----
const detailOpen = ref(false);
const detailLoading = ref(false);
const detail = ref<null | UserCollectApi.TaskDetail>(null);
let pollTimer: null | ReturnType<typeof setInterval> = null;

function statusTag(status: number) {
  switch (status) {
    case 0: {
      return h(NTag, { type: 'default', size: 'small' }, () => '排队中');
    }
    case 1: {
      return h(NTag, { type: 'info', size: 'small' }, () => '执行中');
    }
    case 2: {
      return h(NTag, { type: 'success', size: 'small' }, () => '成功');
    }
    case 3: {
      return h(NTag, { type: 'error', size: 'small' }, () => '失败');
    }
    default: {
      return h(NTag, { size: 'small' }, () => '未知');
    }
  }
}

async function fetchDetail(taskNo: string) {
  try {
    detail.value = await getCollectTaskApi(taskNo);
    // 终态（成功/失败）停止轮询，避免空转
    if (detail.value && [2, 3].includes(detail.value.status)) {
      stopPoll();
    }
  } catch {
    // 单次失败不停轮询（后端可能临时抖动），管理员可关闭弹窗手动重试
  }
}

function startPoll(taskNo: string) {
  stopPoll();
  // 3s 间隔足够跟进 worker 推进节奏，对后端压力小
  pollTimer = setInterval(() => fetchDetail(taskNo), 3000);
}

function stopPoll() {
  if (pollTimer) {
    clearInterval(pollTimer);
    pollTimer = null;
  }
}

async function openDetail(row: UserCollectApi.TaskItem) {
  detailOpen.value = true;
  detailLoading.value = true;
  detail.value = null;
  try {
    await fetchDetail(row.task_no);
    // 未完成态才起轮询
    if (detail.value && [0, 1].includes(detail.value.status)) {
      startPoll(row.task_no);
    }
  } finally {
    detailLoading.value = false;
  }
}

function closeDetail() {
  detailOpen.value = false;
  stopPoll();
  detail.value = null;
  // 关闭后刷新列表，拉回可能变更的 status/counts
  loadList();
}

onUnmounted(stopPoll);

// ---- 表格 ----
const columns: DataTableColumns<UserCollectApi.TaskItem> = [
  { title: '任务号', key: 'task_no', width: 220 },
  { title: '类型', key: 'collect_type', width: 100 },
  { title: '课程数', key: 'course_count', width: 80 },
  { title: '题目数', key: 'question_count', width: 80 },
  { title: '成功', key: 'success_count', width: 70 },
  { title: '失败', key: 'fail_count', width: 70 },
  {
    title: '状态',
    key: 'status',
    width: 100,
    render: (row) => statusTag(row.status),
  },
  { title: '创建时间', key: 'created_at', width: 180 },
  {
    title: '操作',
    key: 'actions',
    width: 80,
    render: (row) =>
      h(
        NButton,
        { size: 'small', onClick: () => openDetail(row) },
        () => '详情',
      ),
  },
];

onMounted(loadList);
</script>

<template>
  <div class="p-6">
    <NCard title="采集任务">
      <template #header-extra>
        <NButton type="primary" @click="submitOpen = true">
          新建采集任务
        </NButton>
      </template>

      <NAlert type="info" :show-icon="false" class="mb-3">
        采集任务由后台 CollectWorker 进程异步执行。提交后可在列表查看状态，执行中/排队中任务详情页会 3 秒轮询一次直至终态。
      </NAlert>

      <NDataTable
        remote
        :loading="loading"
        :columns="columns"
        :data="rows"
        :row-key="(row: UserCollectApi.TaskItem) => row.id"
        :pagination="{
          page,
          pageSize,
          itemCount: total,
          pageSizes: [10, 20, 50],
          showSizePicker: true,
          onChange: onPageChange,
          onUpdatePageSize: onPageSizeChange,
        }"
      />
    </NCard>

    <!-- 提交任务 -->
    <NModal
      v-model:show="submitOpen"
      preset="card"
      title="新建采集任务"
      style="width: 640px"
      :mask-closable="false"
    >
      <NForm :model="form" label-placement="top">
        <NFormItem label="超星账号" required>
          <NInput v-model:value="form.account" placeholder="手机号" />
        </NFormItem>
        <NFormItem label="密码" required>
          <NInput
            v-model:value="form.password"
            type="password"
            show-password-on="click"
            placeholder="登录密码"
          />
        </NFormItem>
        <NFormItem label="采集类型" required>
          <NSelect v-model:value="form.collect_type" :options="typeOptions" />
        </NFormItem>

        <div class="mb-3">
          <NButton
            size="small"
            :loading="queryingCourses"
            :disabled="!form.account || !form.password"
            @click="onQueryCourses"
          >
            试登 & 列出课程（可选）
          </NButton>
          <span
            v-if="coursesResult"
            class="text-muted-foreground ml-2 text-xs"
          >
            {{ coursesResult.userName }} · {{ coursesResult.schoolName }} · 共
            {{ coursesResult.courseCount }} 门
          </span>
        </div>

        <NFormItem
          v-if="coursesResult && courseSelectOptions.length > 0"
          label="选择课程（可多选，留空=全量）"
        >
          <NSelect
            v-model:value="selectedCourseIds"
            multiple
            filterable
            :options="courseSelectOptions"
            placeholder="不选则按 course_ids 输入"
          />
        </NFormItem>

        <NFormItem
          v-if="form.collect_type !== 'courses' && selectedCourseIds.length === 0"
          label="课程 ID（逗号分隔）"
        >
          <NInput
            v-model:value="form.course_ids"
            placeholder="course_id1,course_id2"
          />
        </NFormItem>

        <NFormItem label="学校名称（可选，用于地域代理策略）">
          <NInput v-model:value="form.school_name" placeholder="如：某某大学" />
        </NFormItem>
      </NForm>

      <template #footer>
        <NSpace justify="end">
          <NButton :disabled="submitting" @click="submitOpen = false">
            取消
          </NButton>
          <NButton type="primary" :loading="submitting" @click="onSubmit">
            提交
          </NButton>
        </NSpace>
      </template>
    </NModal>

    <!-- 详情 + 轮询 -->
    <NModal
      :show="detailOpen"
      preset="card"
      title="采集任务详情"
      style="width: 680px"
      :on-close="closeDetail"
      @update:show="(v) => !v && closeDetail()"
    >
      <div v-if="detailLoading && !detail" class="text-muted-foreground py-4">
        加载中...
      </div>
      <template v-else-if="detail">
        <NDescriptions bordered size="small" :column="2">
          <NDescriptionsItem label="任务号" :span="2">
            {{ detail.task_no }}
          </NDescriptionsItem>
          <NDescriptionsItem label="状态">
            <component :is="statusTag(detail.status)" />
          </NDescriptionsItem>
          <NDescriptionsItem label="类型">{{ detail.collect_type }}</NDescriptionsItem>
          <NDescriptionsItem label="账号">{{ detail.account_phone }}</NDescriptionsItem>
          <NDescriptionsItem label="课程数">{{ detail.course_count }}</NDescriptionsItem>
          <NDescriptionsItem label="题目数">{{ detail.question_count }}</NDescriptionsItem>
          <NDescriptionsItem label="成功/失败">
            {{ detail.success_count }} / {{ detail.fail_count }}
          </NDescriptionsItem>
          <NDescriptionsItem v-if="detail.course_ids" label="课程 IDs" :span="2">
            <span class="break-all text-xs">{{ detail.course_ids }}</span>
          </NDescriptionsItem>
          <NDescriptionsItem v-if="detail.error_message" label="错误信息" :span="2">
            <span class="text-error break-all text-xs">
              {{ detail.error_message }}
            </span>
          </NDescriptionsItem>
          <NDescriptionsItem label="创建时间">{{ detail.created_at }}</NDescriptionsItem>
          <NDescriptionsItem label="更新时间">{{ detail.updated_at }}</NDescriptionsItem>
        </NDescriptions>

        <NAlert
          v-if="[0, 1].includes(detail.status)"
          type="info"
          :show-icon="false"
          class="mt-3"
        >
          任务未完成，页面每 3 秒自动刷新。关闭弹窗会停止轮询。
        </NAlert>
      </template>

      <template #footer>
        <NSpace justify="end">
          <NButton @click="closeDetail">关闭</NButton>
        </NSpace>
      </template>
    </NModal>
  </div>
</template>
