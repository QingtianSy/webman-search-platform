<template>
  <div class="page panel-grid">
    <div class="panel table-page">
      <h1>采集任务</h1>
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
            <th>执行脚本</th>
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
            <td>{{ item.runner_script }}</td>
            <td><button class="secondary" @click="loadDetail(item.task_no)">详情</button></td>
          </tr>
        </tbody>
      </table>
    </div>
    <div class="panel">
      <h2>任务详情</h2>
      <pre>{{ detailText }}</pre>
    </div>
  </div>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue';
import { getCollectTaskDetail, getCollectTasks } from '../../api/user';

const list = ref<any[]>([]);
const detailText = ref('请选择一条任务查看详情');

async function loadData() {
  try {
    const { data } = await getCollectTasks();
    list.value = data.data?.list || [];
  } catch (error) {
    console.error(error);
  }
}

async function loadDetail(taskNo: string) {
  const { data } = await getCollectTaskDetail(taskNo);
  detailText.value = JSON.stringify(data, null, 2);
}

onMounted(loadData);
</script>
