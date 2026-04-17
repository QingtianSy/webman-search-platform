<template>
  <div class="page table-page">
    <h1>搜题日志</h1>
    <div class="toolbar">
      <input v-model="keyword" placeholder="输入关键词筛选" />
      <button @click="loadData">查询</button>
    </div>

    <table>
      <thead>
        <tr>
          <th>题目</th>
          <th>选项</th>
          <th>答案</th>
          <th>题型</th>
          <th>来源</th>
          <th>状态</th>
          <th>创建时间</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="(item, index) in list" :key="index">
          <td>{{ item.keyword || '-' }}</td>
          <td>{{ item.options_text || '-' }}</td>
          <td>{{ item.answer_text || '-' }}</td>
          <td>{{ item.type_name || '-' }}</td>
          <td>{{ item.source_name || '-' }}</td>
          <td>{{ item.status ?? '-' }}</td>
          <td>{{ item.created_at || '-' }}</td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue';
import { getSearchLogs } from '../../api/business';

const keyword = ref('');
const list = ref<any[]>([]);

async function loadData() {
  try {
    const { data } = await getSearchLogs();
    const rows = data.data?.list || [];
    list.value = keyword.value
      ? rows.filter((item: any) => String(item.keyword || '').includes(keyword.value))
      : rows;
  } catch (error) {
    console.error(error);
  }
}

onMounted(loadData);
</script>
