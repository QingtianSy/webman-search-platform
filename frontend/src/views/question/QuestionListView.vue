<template>
  <div class="page table-page">
    <h1>题目列表</h1>
    <div class="toolbar">
      <input v-model="keyword" placeholder="输入题目关键词" />
      <button @click="loadData">查询</button>
      <button>新增</button>
      <button>导出</button>
    </div>

    <table>
      <thead>
        <tr>
          <th>ID</th>
          <th>题目</th>
          <th>答案</th>
          <th>题型</th>
          <th>来源</th>
          <th>状态</th>
          <th>创建时间</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="item in list" :key="item.question_id">
          <td>{{ item.question_id }}</td>
          <td>{{ item.stem }}</td>
          <td>{{ item.answer_text }}</td>
          <td>{{ item.type_name }}</td>
          <td>{{ item.source_name }}</td>
          <td>{{ item.status }}</td>
          <td>{{ item.created_at }}</td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue';
import { getQuestionList } from '../../api/business';

const keyword = ref('');
const list = ref<any[]>([]);

async function loadData() {
  try {
    const { data } = await getQuestionList({ stem: keyword.value });
    list.value = data.data?.list || [];
  } catch (error) {
    console.error(error);
  }
}

onMounted(loadData);
</script>
