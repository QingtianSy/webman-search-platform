<template>
  <div class="page table-page">
    <h1>文档管理</h1>
    <div class="toolbar">
      <button @click="startCreate">新增文档</button>
      <button class="secondary" @click="loadData">刷新</button>
    </div>
    <table>
      <thead>
        <tr>
          <th>ID</th>
          <th>标题</th>
          <th>Slug</th>
          <th>摘要</th>
          <th>操作</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="item in list" :key="item.id">
          <td>{{ item.id }}</td>
          <td>{{ item.title }}</td>
          <td>{{ item.slug }}</td>
          <td>{{ item.summary }}</td>
          <td class="actions">
            <button class="secondary" @click="editItem(item)">编辑</button>
            <button class="danger" @click="removeItem(item.id)">删除</button>
          </td>
        </tr>
      </tbody>
    </table>

    <div class="panel" style="margin-top: 16px;" v-if="form">
      <h2>{{ form.id ? '编辑文档' : '新增文档' }}</h2>
      <div class="form-grid">
        <label>标题<input v-model="form.title" /></label>
        <label>Slug<input v-model="form.slug" /></label>
        <label>摘要<textarea v-model="form.summary"></textarea></label>
        <label>正文<textarea v-model="form.content_md"></textarea></label>
        <div class="actions">
          <button @click="saveItem">保存</button>
          <button class="secondary" @click="form = null">取消</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue';
import { createDocArticle, deleteDocArticle, getDocArticleList, updateDocArticle } from '../../api/admin';

const list = ref<any[]>([]);
const form = ref<any>(null);

async function loadData() {
  const { data } = await getDocArticleList();
  list.value = data.data?.list || [];
}

function startCreate() {
  form.value = { title: '', slug: '', summary: '', content_md: '' };
}

function editItem(item: any) {
  form.value = { ...item };
}

async function saveItem() {
  if (!form.value) return;
  if (form.value.id) {
    await updateDocArticle(form.value);
  } else {
    await createDocArticle(form.value);
  }
  form.value = null;
  await loadData();
}

async function removeItem(id: number) {
  await deleteDocArticle(id);
  await loadData();
}

onMounted(loadData);
</script>
