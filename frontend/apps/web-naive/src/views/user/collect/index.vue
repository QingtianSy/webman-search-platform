<script lang="ts" setup>
import type { DataTableColumns } from 'naive-ui';

import { computed, h, onMounted, onUnmounted, ref, watch } from 'vue';

import {
  NAlert,
  NButton,
  NCard,
  NCheckbox,
  NDataTable,
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

// ---- 提交弹窗（单页布局） ----
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

const TYPE_OPTIONS = [
  { label: '整号采集', value: 'courses' as const },
  { label: '单课程采集', value: 'course' as const },
  { label: '章节测试', value: 'chapter' as const },
  { label: '作业', value: 'homework' as const },
  { label: '考试', value: 'exam' as const },
];

const TYPE_LABEL: Record<string, string> = {
  chapter: '章节测试',
  course: '单课程采集',
  courses: '整号采集',
  exam: '考试',
  homework: '作业',
};

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
  if (isWholeAccount.value) return;
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

watch(
  () => form.value.collect_type,
  () => {
    selectedCourseIds.value = [];
  },
);

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
    // 课程快照：保留所选课程的 id+name，详情页"查看课程"渲染列表用
    const snapshot = courses.value
      .filter((c) => ids.includes(courseKey(c)))
      .map((c) => ({
        courseId: courseKey(c),
        courseName: c.courseName ?? '',
      }));
    const payload: UserCollectApi.SubmitParams = {
      ...form.value,
      course_ids: ids.join(','),
      course_count: ids.length,
      courses_snapshot: JSON.stringify(snapshot),
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

// ---- 课程列表弹窗 ----
const coursesOpen = ref(false);
const coursesLoading = ref(false);
// 当前查看的任务（控制行状态映射）
const viewingTask = ref<null | UserCollectApi.TaskDetail>(null);
// 解析后的课程行：序号 + 课程名 + 题目数 + 状态
interface CourseRow {
  index: number;
  courseId: string;
  courseName: string;
  questionCount: number | string;
  status: '采集中' | '失败' | '已完成' | '待采集';
}
const courseRows = ref<CourseRow[]>([]);
let coursesPollTimer: null | ReturnType<typeof setInterval> = null;

function statusForCourse(taskStatus: number): CourseRow['status'] {
  switch (taskStatus) {
    case 0: {
      return '待采集';
    }
    case 1: {
      return '采集中';
    }
    case 2: {
      return '已完成';
    }
    case 3: {
      return '失败';
    }
    default: {
      return '待采集';
    }
  }
}

function buildCourseRows(detail: UserCollectApi.TaskDetail): CourseRow[] {
  const status = statusForCourse(detail.status);
  // 优先用快照（含 courseName）；没有快照退回 course_ids 字符串
  const raw = detail.courses_snapshot ?? '';
  if (raw) {
    try {
      const parsed = JSON.parse(raw) as Array<{
        courseId?: string;
        courseName?: string;
      }>;
      if (Array.isArray(parsed) && parsed.length > 0) {
        return parsed.map((c, i) => ({
          courseId: String(c.courseId ?? ''),
          courseName: c.courseName ?? '(未命名)',
          index: i + 1,
          // 后端目前只能给到任务级总数，按课程数均摊作为参考；status=0/1 时未知
          questionCount: detail.status === 2 ? '-' : '',
          status,
        }));
      }
    } catch {
      // 落到 course_ids fallback
    }
  }
  const ids = (detail.course_ids ?? '').split(',').filter(Boolean);
  return ids.map((id, i) => ({
    courseId: id,
    courseName: `课程 ${id}`,
    index: i + 1,
    questionCount: '',
    status,
  }));
}

async function fetchCoursesDetail(taskNo: string) {
  try {
    const res = await getCollectTaskApi(taskNo);
    viewingTask.value = res;
    courseRows.value = buildCourseRows(res);
    if (res && [2, 3].includes(res.status)) {
      stopCoursesPoll();
    }
  } catch {
    // 单次失败不停轮询
  }
}

function startCoursesPoll(taskNo: string) {
  stopCoursesPoll();
  coursesPollTimer = setInterval(() => fetchCoursesDetail(taskNo), 3000);
}

function stopCoursesPoll() {
  if (coursesPollTimer) {
    clearInterval(coursesPollTimer);
    coursesPollTimer = null;
  }
}

async function openCourses(row: UserCollectApi.TaskItem) {
  coursesOpen.value = true;
  coursesLoading.value = true;
  viewingTask.value = null;
  courseRows.value = [];
  try {
    await fetchCoursesDetail(row.task_no);
    const cur = viewingTask.value as null | UserCollectApi.TaskDetail;
    if (cur && (cur.status === 0 || cur.status === 1)) {
      startCoursesPoll(row.task_no);
    }
  } finally {
    coursesLoading.value = false;
  }
}

function closeCourses() {
  coursesOpen.value = false;
  stopCoursesPoll();
  viewingTask.value = null;
  courseRows.value = [];
  loadList();
}

onUnmounted(() => {
  stopCoursesPoll();
  stopListPoll();
});

// ---- 主表 ----
function statusTag(status: number) {
  switch (status) {
    case 0: {
      return h(NTag, { type: 'default', size: 'small' }, () => '排队中');
    }
    case 1: {
      return h(NTag, { type: 'info', size: 'small' }, () => '采集中');
    }
    case 2: {
      return h(NTag, { type: 'success', size: 'small' }, () => '采集成功');
    }
    case 3: {
      return h(NTag, { type: 'error', size: 'small' }, () => '采集失败');
    }
    default: {
      return h(NTag, { size: 'small' }, () => '未知');
    }
  }
}

const columns: DataTableColumns<UserCollectApi.TaskItem> = [
  { type: 'selection', width: 40 },
  {
    title: '序号',
    key: '_index',
    width: 60,
    render: (_row, idx) => (page.value - 1) * pageSize.value + idx + 1,
  },
  { title: '账号', key: 'account_phone', width: 140 },
  {
    title: '采集类型',
    key: 'collect_type',
    width: 110,
    render: (row) => TYPE_LABEL[row.collect_type] ?? row.collect_type,
  },
  { title: '课程数量', key: 'course_count', width: 100, align: 'center' },
  { title: '题目数量', key: 'question_count', width: 100, align: 'center' },
  {
    title: '状态',
    key: 'status',
    width: 110,
    align: 'center',
    render: (row) => statusTag(row.status),
  },
  {
    title: '错误信息',
    key: 'error_message',
    minWidth: 180,
    ellipsis: { tooltip: true },
    render: (row) => row.error_message || '-',
  },
  { title: '创建时间', key: 'created_at', width: 180 },
  {
    title: '操作',
    key: 'actions',
    width: 110,
    align: 'center',
    render: (row) =>
      h(
        NButton,
        {
          size: 'small',
          text: true,
          type: 'primary',
          onClick: () => openCourses(row),
        },
        () => '👁 查看课程',
      ),
  },
];

const courseColumns: DataTableColumns<CourseRow> = [
  { title: '序号', key: 'index', width: 70, align: 'center' },
  { title: '课程名称', key: 'courseName', minWidth: 240 },
  {
    title: '题目数量',
    key: 'questionCount',
    width: 100,
    align: 'center',
    render: (row) => row.questionCount || '-',
  },
  {
    title: '状态',
    key: 'status',
    width: 100,
    align: 'center',
    render: (row) => {
      const map = {
        采集中: 'info',
        待采集: 'default',
        已完成: 'success',
        失败: 'error',
      } as const;
      return h(
        NTag,
        { size: 'small', type: map[row.status] ?? 'default' },
        () => row.status,
      );
    },
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

        <NFormItem v-if="coursesResult" label="采集类型" required>
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

        <NFormItem v-if="coursesResult" label="课程选择">
          <div class="w-full">
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

    <!-- 查看课程列表 -->
    <NModal
      :show="coursesOpen"
      preset="card"
      title="课程列表"
      style="width: 880px"
      :on-close="closeCourses"
      @update:show="(v) => !v && closeCourses()"
    >
      <div
        v-if="coursesLoading && courseRows.length === 0"
        class="text-muted-foreground py-4"
      >
        加载中...
      </div>
      <template v-else>
        <NDataTable
          :columns="courseColumns"
          :data="courseRows"
          :row-key="(row: CourseRow) => row.courseId || String(row.index)"
          :pagination="false"
          size="small"
          :bordered="true"
          :max-height="420"
        />
        <NAlert
          v-if="
            viewingTask && (viewingTask.status === 0 || viewingTask.status === 1)
          "
          type="info"
          :show-icon="false"
          class="mt-3"
        >
          任务未完成，列表每 3 秒自动刷新。关闭弹窗会停止轮询。
        </NAlert>
        <NAlert
          v-else-if="viewingTask && viewingTask.status === 3 && viewingTask.error_message"
          type="error"
          :show-icon="false"
          class="mt-3"
        >
          {{ viewingTask.error_message }}
        </NAlert>
      </template>

      <template #footer>
        <NSpace justify="end">
          <NButton @click="closeCourses">关闭</NButton>
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
