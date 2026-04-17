<template>
  <div class="page table-page">
    <h1>采集任务管理</h1>
    <div class="toolbar">
      <button class="secondary" @click="loadData">刷新</button>
    </div>
    <table>
      <thead>
        <tr>
          <th>任务号</th>
          <th>类型</th>
          <th>课程数</th>
          <th>题目数</th>
          <th>状态</th>
          <th>错误信息</th>
          <th>操作</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="item in list" :key="item.task_no">
          <td>{{ item.task_no }}</td>
          <td>{{ item.collect_type }}</td>
          <td>{{ item.course_count }}</td>
          <td>{{ item.question_count }}</td>
          <td>{{ item.status }}</td>
          <td>{{ item.error_message || '-' }}</td>
          <td class="actions">
            <button class="secondary" @click="handleRetry(item.task_no)">重试</button>
            <button class="danger" @click="handleStop(item.task_no)">停止</button>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue';
import { getCollectManageList, retryCollectTask, stopCollectTask } from '../../api/admin';

const list = ref<any[]>([]);

async function loadData() {
  const { data } = await getCollectManageList();
  list.value = data.data?.list || [];
}

async function handleRetry(taskNo: string) {
  await retryCollectTask(taskNo);
  await loadData();
}

async function handleStop(taskNo: string) {
  await stopCollectTask(taskNo);
  await loadData();
}

onMounted(loadData);
</script>
