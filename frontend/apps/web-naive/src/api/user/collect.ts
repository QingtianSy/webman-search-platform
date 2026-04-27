import { requestClient } from '#/api/request';

/**
 * 用户端采集任务。对齐后端 [backend/app/controller/user/CollectController.php](../../../../../../backend/app/controller/user/CollectController.php)。
 *   - GET  /user/collect/task/list?page=&page_size=
 *   - GET  /user/collect/task/detail?task_no=
 *   - POST /user/collect/query-courses   body: {account, password}  限流 10/60s
 *   - POST /user/collect/submit-collect  body: {account, password, collect_type, course_ids?, course_count?, school_name?}  限流 10/60s
 *
 * 后端 collect_tasks.status：
 *   0 = pending（新建）
 *   1 = running（worker 已认领）
 *   2 = success
 *   3 = fail
 * 前端详情页在 status ∈ {0,1} 时做轮询。
 */
export namespace UserCollectApi {
  export type TaskStatus = 0 | 1 | 2 | 3;

  export interface TaskItem {
    id: number;
    task_no: string;
    user_id: number;
    account_id: number;
    account_phone: string;
    collect_type: string;
    course_count: number;
    question_count: number;
    success_count: number;
    fail_count: number;
    status: TaskStatus;
    error_message?: string | null;
    runner_script?: string | null;
    next_script?: string | null;
    created_at: string;
  }

  export interface TaskPage {
    list: TaskItem[];
    total: number;
    page: number;
    page_size: number;
  }

  export interface TaskDetail extends TaskItem {
    account_phone: string;
    course_ids: string;
    /** 提交时课程快照 JSON 字符串：[{courseId, courseName}]。无快照时为空串。 */
    courses_snapshot?: null | string;
    /** 按 course_id 聚合的题目数；Mongo 不可用时为 {} */
    course_stats?: Record<string, number>;
    updated_at: string;
  }

  /** POST /collect/query-courses 响应。超星账号试登 + 课程列表。 */
  export interface QueryCoursesResult {
    userName: string;
    schoolName: string;
    courseCount: number;
    courses: Array<{
      courseId?: string;
      courseName?: string;
      clazzId?: string;
      [k: string]: any;
    }>;
  }

  export interface SubmitParams {
    account: string;
    password: string;
    /** courses | course | chapter | exam | homework */
    collect_type: 'chapter' | 'course' | 'courses' | 'exam' | 'homework';
    /** 逗号分隔的 course/clazz id 串；courses 全量采集时可空 */
    course_ids?: string;
    course_count?: number;
    school_name?: string;
    /** 课程快照 JSON 字符串 [{courseId, courseName}]，详情页"查看课程"用 */
    courses_snapshot?: string;
  }

  export interface SubmitResult {
    task_no: string;
  }
}

export async function listCollectTasksApi(params?: {
  page?: number;
  page_size?: number;
}) {
  return requestClient.get<UserCollectApi.TaskPage>('/user/collect/task/list', {
    params,
  });
}

export async function getCollectTaskApi(taskNo: string) {
  return requestClient.get<UserCollectApi.TaskDetail>(
    '/user/collect/task/detail',
    { params: { task_no: taskNo } },
  );
}

export async function queryCoursesApi(data: {
  account: string;
  password: string;
}) {
  return requestClient.post<UserCollectApi.QueryCoursesResult>(
    '/user/collect/query-courses',
    data,
  );
}

export async function submitCollectApi(data: UserCollectApi.SubmitParams) {
  return requestClient.post<UserCollectApi.SubmitResult>(
    '/user/collect/submit-collect',
    data,
  );
}
