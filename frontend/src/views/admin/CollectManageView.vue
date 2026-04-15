<template>
  <div class="page table-page">
    <h1>采集任务管理</h1>
    <div class="toolbar">
      <button>刷新</button>
      <button>重试任务</button>
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
        </tr>
      </tbody>
    </table>
  </div>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue';
import { getCollectManageList } from '../../api/admin';

const list = ref<any[]>([]);

onMounted(async () => {
  try {
    const { data } = await getCollectManageList();
    list.value = data.data?.list || [];
  } catch (error) {
    console.error(error);
  }
});
</script>
