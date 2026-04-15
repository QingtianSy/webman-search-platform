<template>
  <div class="page table-page">
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
        </tr>
      </tbody>
    </table>
  </div>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue';
import { getCollectTasks } from '../../api/user';

const list = ref<any[]>([]);

async function loadData() {
  try {
    const { data } = await getCollectTasks();
    list.value = data.data?.list || [];
  } catch (error) {
    console.error(error);
  }
}

onMounted(loadData);
</script>
