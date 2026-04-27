<script lang="ts" setup>
import type { DataTableColumns } from 'naive-ui';

import { computed, h, onMounted, onUnmounted, ref, watch } from 'vue';

import {
  NAlert,
  NButton,
  NCard,
  NCheckbox,
  NDataTable,
  NDescriptions,
  NDescriptionsItem,
  NForm,
  NFormItem,
  NInput,
  NModal,
  NRadio,
  NRadioGroup,
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

// ---- 提交弹窗（单页布局，按截图设计） ----
const submitOpen = ref(false);
const queryingCourses = ref(false);
const submitting = ref(false);
const coursesResult = ref<null | UserCollectApi.QueryCoursesResult>(null);
const selectedCourseIds = ref<string[]>([]);

const form = ref<UserCollectApi.SubmitParams>({
  account: '',
  password: '',
  collect_type: 'course',
  course_ids: '',
  course_count: 0,
  school_name: '',
});

// 采集类型按截图：整号 / 单课程 / 章节测试 / 作业 / 考试
const TYPE_OPTIONS = [
  { label: '整号采集', value: 'courses' as const },
  { label: '单课程采集', value: 'course' as const },
  { label: '章节测试', value: 'chapter' as const },
  { label: '作业', value: 'homework' as const },
  { label: '考试', value: 'exam' as const },
];

const isWholeAccount = computed(() => form.value.collect_type === 'courses');

const courses = computed(() => coursesResult.value?.courses ?? []);
const totalCount = computed(() => courses.value.length);
const selectedCount = computed(() =>
  isWholeAccount.value ? totalCount.value : selectedCourseIds.value.length,
);
const allChecked = computed(
  () => totalCount.value > 0 && selectedCount.value === totalCount.value,
);
const someChecked = computed(
  () => selectedCount.value > 0 && selectedCount.value < totalCount.value,
);

function courseKey(c: UserCollectApi.QueryCoursesResult['courses'][number]) {
  return String(c.courseId ?? c.clazzId ?? '');
}

function toggleCourse(id: string, checked: boolean) {
  if (isWholeAccount.value) return; // 整号锁定
  if (checked) {
    if (!selectedCourseIds.value.includes(id))
      selectedCourseIds.value.push(id);
  } else {
    selectedCourseIds.value = selectedCourseIds.value.filter((x) => x !== id);
  }
}

function toggleAll(checked: boolean) {
  if (isWholeAccount.value) return;
  selectedCourseIds.value = checked
    ? courses.value.map((c) => courseKey(c)).filter(Boolean)
    : [];
}

// 切到整号 → 清掉手选；切回其它 → 也清空，让用户重新挑
watch(
  () => form.value.collect_type,
  () => {
    selectedCourseIds.value = [];
  },
);

// 账号/密码改了，已查询的结果失效
watch([() => form.value.account, () => form.value.password], () => {
  if (coursesResult.value) {
    coursesResult.value = null;
    selectedCourseIds.value = [];
  }
});

function resetWizard() {
  coursesResult.value = null;
  selectedCourseIds.value = [];
  form.value = {
    account: '',
    password: '',
    collect_type: 'course',
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
    selectedCourseIds.value = [];
    if (res.schoolName) form.value.school_name = res.schoolName;
    message.success(`已试登成功，共 ${res.courseCount} 门课程`);
  } catch {
    // 拦截器已 toast
  } finally {
    queryingCourses.value = false;
  }
}

async function onSubmit() {
  if (!coursesResult.value) {
    message.warning('请先点「查询课程」');
    return;
  }
  if (!isWholeAccount.value && selectedCourseIds.value.length === 0) {
    message.warning('请勾选至少一门课程');
    return;
  }
  submitting.value = true;
  try {
    const ids = isWholeAccount.value
      ? courses.value.map((c) => courseKey(c)).filter(Boolean)
      : selectedCourseIds.value;
    const payload: UserCollectApi.SubmitParams = {
      ...form.value,
      course_ids: ids.join(','),
      course_count: ids.length,
    };
    const res = await submitCollectApi(payload);
    message.success(`任务已提交：${res.task_no}`);
    submitOpen.value = false;
    resetWizard();
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

    <!-- 提交采集任务（单页布局） -->
    <NModal
      :show="submitOpen"
      preset="card"
      title="提交采集任务"
      style="width: 720px"
      :mask-closable="false"
      :close-on-esc="!submitting && !queryingCourses"
      :on-close="closeWizard"
      @update:show="(v) => !v && closeWizard()"
    >
      <NForm :model="form" label-placement="left" label-width="70">
        <div class="grid grid-cols-2 gap-x-4">
          <NFormItem label="账号" required>
            <NInput
              v-model:value="form.account"
              placeholder="手机号"
              :disabled="queryingCourses || submitting"
            />
          </NFormItem>
          <NFormItem label="密码" required>
            <NInput
              v-model:value="form.password"
              type="password"
              show-password-on="click"
              placeholder="登录密码"
              :disabled="queryingCourses || submitting"
            />
          </NFormItem>
        </div>

        <div class="mb-4">
          <NButton
            type="primary"
            :loading="queryingCourses"
            :disabled="!form.account || !form.password || submitting"
            @click="onQueryCourses"
          >
            查询课程
          </NButton>
          <span
            v-if="coursesResult"
            class="text-muted-foreground ml-3 text-xs"
          >
            {{ coursesResult.userName }} ·
            {{ coursesResult.schoolName || '未知学校' }} · 共
            {{ coursesResult.courseCount }} 门
          </span>
        </div>

        <NFormItem label="采集类型" required>
          <NRadioGroup v-model:value="form.collect_type" :disabled="submitting">
            <NRadio
              v-for="opt in TYPE_OPTIONS"
              :key="opt.value"
              :value="opt.value"
            >
              {{ opt.label }}
            </NRadio>
          </NRadioGroup>
        </NFormItem>

        <NFormItem label="课程选择">
          <div class="w-full">
            <div v-if="!coursesResult" class="text-muted-foreground text-xs">
              请先点「查询课程」
            </div>
            <template v-else>
              <div class="mb-2 flex items-center gap-3">
                <NCheckbox
                  v-if="!isWholeAccount"
                  :checked="allChecked"
                  :indeterminate="someChecked"
                  :disabled="submitting"
                  @update:checked="toggleAll"
                >
                  全选
                </NCheckbox>
                <span
                  v-if="isWholeAccount"
                  class="text-muted-foreground text-xs"
                >
                  整号采集将采集所有课程，已自动选中全部课程
                </span>
                <span v-else class="text-muted-foreground text-xs">
                  已选: {{ selectedCount }} / {{ totalCount }}
                </span>
              </div>
              <div
                class="course-list"
                :class="{ 'course-list--locked': isWholeAccount }"
              >
                <div
                  v-for="c in courses"
                  :key="courseKey(c)"
                  class="course-item"
                >
                  <NCheckbox
                    :checked="
                      isWholeAccount ||
                      selectedCourseIds.includes(courseKey(c))
                    "
                    :disabled="isWholeAccount || submitting"
                    @update:checked="(v: boolean) => toggleCourse(courseKey(c), v)"
                  >
                    <span class="course-name">{{
                      c.courseName ?? '(未命名)'
                    }}</span>
                    <span
                      v-if="c.teacherName"
                      class="course-meta text-muted-foreground"
                    >
                      {{ c.teacherName }}
                    </span>
                  </NCheckbox>
                </div>
              </div>
            </template>
          </div>
        </NFormItem>
      </NForm>

      <template #footer>
        <NSpace justify="end">
          <NButton :disabled="submitting" @click="closeWizard">取消</NButton>
          <NButton
            type="primary"
            :loading="submitting"
            :disabled="!coursesResult"
            @click="onSubmit"
          >
            提交采集
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
          <NDescriptionsItem label="类型">{{
            detail.collect_type
          }}</NDescriptionsItem>
          <NDescriptionsItem label="账号">{{
            detail.account_phone
          }}</NDescriptionsItem>
          <NDescriptionsItem label="课程数">{{
            detail.course_count
          }}</NDescriptionsItem>
          <NDescriptionsItem label="题目数">{{
            detail.question_count
          }}</NDescriptionsItem>
          <NDescriptionsItem label="成功/失败">
            {{ detail.success_count }} / {{ detail.fail_count }}
          </NDescriptionsItem>
          <NDescriptionsItem v-if="detail.course_ids" label="课程 IDs" :span="2">
            <span class="break-all text-xs">{{ detail.course_ids }}</span>
          </NDescriptionsItem>
          <NDescriptionsItem
            v-if="detail.error_message"
            label="错误信息"
            :span="2"
          >
            <span class="text-error break-all text-xs">
              {{ detail.error_message }}
            </span>
          </NDescriptionsItem>
          <NDescriptionsItem label="创建时间">{{
            detail.created_at
          }}</NDescriptionsItem>
          <NDescriptionsItem label="更新时间">{{
            detail.updated_at
          }}</NDescriptionsItem>
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

<style scoped>
.course-list {
  max-height: 320px;
  overflow-y: auto;
  padding: 8px 12px;
  border: 1px solid var(--n-border-color, #e5e7eb);
  border-radius: 4px;
}
.course-list--locked {
  background: rgba(24, 160, 88, 0.06);
  border-color: rgba(24, 160, 88, 0.4);
}
.course-item {
  padding: 6px 0;
  border-bottom: 1px dashed var(--n-divider-color, #f0f0f0);
}
.course-item:last-child {
  border-bottom: none;
}
.course-name {
  font-weight: 500;
  margin-right: 8px;
}
.course-meta {
  font-size: 12px;
  margin-left: 4px;
}
</style>
