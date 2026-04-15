<template>
  <div class="page table-page">
    <h1>公告管理</h1>
    <div class="toolbar">
      <button @click="startCreate">新增公告</button>
      <button class="secondary" @click="loadData">刷新</button>
    </div>
    <table>
      <thead>
        <tr>
          <th>ID</th>
          <th>标题</th>
          <th>内容</th>
          <th>操作</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="item in list" :key="item.id">
          <td>{{ item.id }}</td>
          <td>{{ item.title }}</td>
          <td>{{ item.content }}</td>
          <td class="actions">
            <button class="secondary" @click="editItem(item)">编辑</button>
            <button class="danger" @click="removeItem(item.id)">删除</button>
          </td>
        </tr>
      </tbody>
    </table>

    <div class="panel" style="margin-top: 16px;" v-if="form">
      <h2>{{ form.id ? '编辑公告' : '新增公告' }}</h2>
      <div class="form-grid">
        <label>
          标题
          <input v-model="form.title" />
        </label>
        <label>
          内容
          <textarea v-model="form.content"></textarea>
        </label>
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
import { createAnnouncement, deleteAnnouncement, getAnnouncementList, updateAnnouncement } from '../../api/admin';

const list = ref<any[]>([]);
const form = ref<any>(null);

async function loadData() {
  const { data } = await getAnnouncementList();
  list.value = data.data?.list || [];
}

function startCreate() {
  form.value = { title: '', content: '' };
}

function editItem(item: any) {
  form.value = { ...item };
}

async function saveItem() {
  if (!form.value) return;
  if (form.value.id) {
    await updateAnnouncement(form.value);
  } else {
    await createAnnouncement(form.value);
  }
  form.value = null;
  await loadData();
}

async function removeItem(id: number) {
  await deleteAnnouncement(id);
  await loadData();
}

onMounted(loadData);
</script>
