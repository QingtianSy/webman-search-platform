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
  NStep,
  NSteps,
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

// 列表级轮询：只要当前页存在 pending/running 的任务就保持 5s 刷新，终态则停
const LIST_POLL_MS = 5000;
let listPollTimer: null | ReturnType<typeof setInterval> = null;

function hasInflight(): boolean {
  return rows.value.some((r) => r.status === 0 || r.status === 1);
}

function stopListPoll() {
  if (listPollTimer) {
    clearInterval(listPollTimer);
    listPollTimer = null;
  }
}

function ensureListPoll() {
  if (hasInflight()) {
    if (!listPollTimer) {
      listPollTimer = setInterval(loadList, LIST_POLL_MS);
    }
  } else {
    stopListPoll();
  }
}

async function loadList() {
  loading.value = true;
  try {
    const data = await listCollectTasksApi({
      page: page.value,
      page_size: pageSize.value,
    });
    rows.value = data.list ?? [];
    total.value = data.total ?? 0;
    ensureListPoll();
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

// ---- 两步 Wizard ----
// step 0 = 账号认证 + 查询课程；step 1 = 选课程 + 提交
const submitOpen = ref(false);
const wizardStep = ref<0 | 1>(0);
const queryingCourses = ref(false);
const submitting = ref(false);
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

function resetWizard() {
  wizardStep.value = 0;
  coursesResult.value = null;
  selectedCourseIds.value = [];
  form.value = {
    account: '',
    password: '',
    collect_type: 'courses',
    course_ids: '',
    course_count: 0,
    school_name: '',
  };
}

function openWizard() {
  resetWizard();
  submitOpen.value = true;
}

function closeWizard() {
  if (submitting.value || queryingCourses.value) {
    message.warning('请等待当前操作完成');
    return;
  }
  submitOpen.value = false;
}

// Step 1 → Step 2
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
    message.success(`已试登成功，共 ${res.courseCount} 门课程`);
    wizardStep.value = 1;
  } catch {
    // 拦截器已 toast（账号错误 / 限流 / 第三方故障）
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

function backToStep1() {
  wizardStep.value = 0;
  // 保留账号密码，清查询结果（用户可能要改账号）
  coursesResult.value = null;
  selectedCourseIds.value = [];
}

// Step 2 → 提交
async function onSubmit() {
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
    resetWizard();
    loadList(); // 新任务刚下发，必然 pending，loadList 会自动启轮询
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
let detailPollTimer: null | ReturnType<typeof setInterval> = null;

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
    if (detail.value && [2, 3].includes(detail.value.status)) {
      stopDetailPoll();
    }
  } catch {
    // 单次失败不停轮询
  }
}

function startDetailPoll(taskNo: string) {
  stopDetailPoll();
  detailPollTimer = setInterval(() => fetchDetail(taskNo), 3000);
}

function stopDetailPoll() {
  if (detailPollTimer) {
    clearInterval(detailPollTimer);
    detailPollTimer = null;
  }
}

async function openDetail(row: UserCollectApi.TaskItem) {
  detailOpen.value = true;
  detailLoading.value = true;
  detail.value = null;
  try {
    await fetchDetail(row.task_no);
    const cur = detail.value as null | UserCollectApi.TaskDetail;
    if (cur && (cur.status === 0 || cur.status === 1)) {
      startDetailPoll(row.task_no);
    }
  } finally {
    detailLoading.value = false;
  }
}

function closeDetail() {
  detailOpen.value = false;
  stopDetailPoll();
  detail.value = null;
  loadList();
}

onUnmounted(() => {
  stopDetailPoll();
  stopListPoll();
});

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
        <NButton type="primary" @click="openWizard">新建采集任务</NButton>
      </template>

      <NAlert type="info" :show-icon="false" class="mb-3">
        采集任务由后台 CollectWorker 进程异步执行。列表在存在未完成任务时每
        5 秒自动刷新，全部进入终态后停止轮询。
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

    <!-- 两步 Wizard：Step1 账号认证 → Step2 选课提交 -->
    <NModal
      :show="submitOpen"
      preset="card"
      title="新建采集任务"
      style="width: 640px"
      :mask-closable="false"
      :close-on-esc="!submitting && !queryingCourses"
      :on-close="closeWizard"
      @update:show="(v) => !v && closeWizard()"
    >
      <NSteps :current="wizardStep + 1" size="small" class="mb-4">
        <NStep title="账号认证" description="试登并获取课程列表" />
        <NStep title="选择课程" description="设定采集范围并提交" />
      </NSteps>

      <!-- Step 1 -->
      <template v-if="wizardStep === 0">
        <NForm :model="form" label-placement="top">
          <NFormItem label="超星账号" required>
            <NInput
              v-model:value="form.account"
              placeholder="手机号"
              :disabled="queryingCourses"
            />
          </NFormItem>
          <NFormItem label="密码" required>
            <NInput
              v-model:value="form.password"
              type="password"
              show-password-on="click"
              placeholder="登录密码"
              :disabled="queryingCourses"
            />
          </NFormItem>
        </NForm>
        <NAlert type="warning" :show-icon="false" class="mb-2">
          试登仅用于校验账号并拉取课程列表，密码不会落库（会随提交任务转交 worker 执行一次性登录）。
        </NAlert>
      </template>

      <!-- Step 2 -->
      <template v-else>
        <div class="text-muted-foreground mb-3 text-xs">
          <template v-if="coursesResult">
            {{ coursesResult.userName }} · {{ coursesResult.schoolName }} · 共
            {{ coursesResult.courseCount }} 门课程
          </template>
        </div>
        <NForm :model="form" label-placement="top">
          <NFormItem label="采集类型" required>
            <NSelect
              v-model:value="form.collect_type"
              :options="typeOptions"
              :disabled="submitting"
            />
          </NFormItem>

          <NFormItem
            v-if="courseSelectOptions.length > 0"
            label="选择课程（可多选，留空=全量）"
          >
            <NSelect
              v-model:value="selectedCourseIds"
              multiple
              filterable
              :options="courseSelectOptions"
              :disabled="submitting"
              placeholder="不选则按 course_ids 输入"
            />
          </NFormItem>

          <NFormItem
            v-if="
              form.collect_type !== 'courses' &&
              selectedCourseIds.length === 0
            "
            label="课程 ID（逗号分隔）"
          >
            <NInput
              v-model:value="form.course_ids"
              placeholder="course_id1,course_id2"
              :disabled="submitting"
            />
          </NFormItem>

          <NFormItem label="学校名称（可选，用于地域代理策略）">
            <NInput
              v-model:value="form.school_name"
              placeholder="如：某某大学"
              :disabled="submitting"
            />
          </NFormItem>
        </NForm>
      </template>

      <template #footer>
        <NSpace justify="end">
          <template v-if="wizardStep === 0">
            <NButton :disabled="queryingCourses" @click="closeWizard">
              取消
            </NButton>
            <NButton
              type="primary"
              :loading="queryingCourses"
              :disabled="!form.account || !form.password"
              @click="onQueryCourses"
            >
              下一步：查询课程
            </NButton>
          </template>
          <template v-else>
            <NButton :disabled="submitting" @click="backToStep1">
              上一步
            </NButton>
            <NButton :disabled="submitting" @click="closeWizard">取消</NButton>
            <NButton type="primary" :loading="submitting" @click="onSubmit">
              提交任务
            </NButton>
          </template>
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
          任务未完成，详情每 3 秒自动刷新。关闭弹窗会停止轮询。
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
