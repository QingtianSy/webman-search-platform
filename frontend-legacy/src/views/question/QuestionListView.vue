<template>
  <div class="page table-page">
    <h1>题目列表</h1>
    <div class="toolbar">
      <input v-model="keyword" placeholder="输入题目关键词" />
      <button @click="loadData">查询</button>
      <button class="secondary" @click="openEdit(null)">新增</button>
      <button class="danger" @click="handleBatchDelete">批量删除</button>
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
          <th>操作</th>
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
          <td class="actions">
            <button @click="showDetail(item.question_id)">详情</button>
            <button class="secondary" @click="openEdit(item)">编辑</button>
            <button class="danger" @click="handleDelete(item.question_id)">删除</button>
          </td>
        </tr>
      </tbody>
    </table>

    <div class="panel-grid" style="margin-top: 16px;" v-if="selectedDetail || editing">
      <div class="panel" v-if="selectedDetail">
        <h2>题目详情</h2>
        <pre>{{ JSON.stringify(selectedDetail, null, 2) }}</pre>
      </div>
      <div class="panel" v-if="editing">
        <h2>{{ editing.question_id ? '编辑题目' : '新增题目' }}</h2>
        <div class="form-grid">
          <label>
            题目
            <input v-model="editing.stem" />
          </label>
          <div class="actions">
            <button @click="handleSave">保存</button>
            <button class="secondary" @click="editing = null">取消</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue';
import { deleteQuestion, getQuestionDetail, getQuestionList, updateQuestion } from '../../api/admin';

const keyword = ref('');
const list = ref<any[]>([]);
const selectedDetail = ref<any>(null);
const editing = ref<any>(null);

async function loadData() {
  try {
    const { data } = await getQuestionList({ stem: keyword.value });
    list.value = data.data?.list || [];
  } catch (error) {
    console.error(error);
  }
}

async function showDetail(id: number) {
  try {
    const { data } = await getQuestionDetail(id);
    selectedDetail.value = data.data || null;
  } catch (error) {
    console.error(error);
  }
}

function openEdit(item: any) {
  editing.value = item ? { ...item } : { question_id: 0, stem: '' };
}

async function handleSave() {
  if (!editing.value) return;
  try {
    await updateQuestion({ id: editing.value.question_id, stem: editing.value.stem });
    await loadData();
    editing.value = null;
  } catch (error) {
    console.error(error);
  }
}

async function handleDelete(id: number) {
  try {
    await deleteQuestion(id);
    await loadData();
  } catch (error) {
    console.error(error);
  }
}

function handleBatchDelete() {
  alert('批量删除骨架已预留，后续接后端批量接口');
}

onMounted(loadData);
</script>
