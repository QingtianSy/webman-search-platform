<script lang="ts" setup>
import { computed, onMounted, ref } from 'vue';

import {
  NAlert,
  NButton,
  NCard,
  NCode,
  NDataTable,
  NDescriptions,
  NDescriptionsItem,
  NEmpty,
  NModal,
  NRadio,
  NRadioGroup,
  NSpace,
  NSpin,
  NTabs,
  NTabPane,
  NTag,
  useMessage,
  type DataTableColumns,
} from 'naive-ui';

import {
  getDocMetaApi,
  type DocMeta,
} from '#/api/user/doc';
import {
  listApiKeysApi,
  setDefaultApiKeyApi,
  type ApiKeyApi,
} from '#/api/user/api-key';
import { maskKey } from '#/utils/mask';

const message = useMessage();

// 顶卡：API 密钥信息
const meta = ref<DocMeta>({
  api_base_url: '',
  header_name: 'x-api-secret',
});
const keys = ref<ApiKeyApi.ApiKeyItem[]>([]);
const defaultKeyId = ref<null | number>(null);
const metaLoading = ref(false);

const defaultKey = computed(() =>
  keys.value.find((k) => k.id === defaultKeyId.value) ?? keys.value[0] ?? null,
);

async function loadMeta() {
  metaLoading.value = true;
  try {
    const [m, list] = await Promise.all([
      getDocMetaApi(),
      listApiKeysApi({ page: 1, page_size: 100 }),
    ]);
    meta.value = m;
    keys.value = (list?.list ?? []).filter((k) => k.status === 1);
    // 默认 key：meta 优先 → localStorage → 第一条
    let defId: null | number = null;
    if (m.default_api_key) {
      const hit = keys.value.find((k) => k.api_key === m.default_api_key);
      if (hit) defId = hit.id;
    }
    if (!defId) {
      try {
        const saved = Number(localStorage.getItem('default_api_key_id'));
        if (saved && keys.value.some((k) => k.id === saved)) defId = saved;
      } catch {
        // ignore
      }
    }
    defaultKeyId.value = defId ?? keys.value[0]?.id ?? null;
  } finally {
    metaLoading.value = false;
  }
}

function copy(text: string) {
  if (!text) {
    message.warning('无可复制内容');
    return;
  }
  navigator.clipboard
    ?.writeText(text)
    .then(() => message.success('已复制'))
    .catch(() => message.error('复制失败'));
}

// 设置默认 key Modal
const setModalVisible = ref(false);
const setChoice = ref<null | number>(null);

function openSetModal() {
  setChoice.value = defaultKeyId.value;
  setModalVisible.value = true;
}

async function confirmSetDefault() {
  if (!setChoice.value) {
    setModalVisible.value = false;
    return;
  }
  try {
    await setDefaultApiKeyApi(setChoice.value);
    defaultKeyId.value = setChoice.value;
    message.success('已设为默认');
  } catch {
    message.error('设置失败');
  }
  setModalVisible.value = false;
}

// ===== Tab1 接口说明 =====
const endpointParams = [
  {
    name: 'question',
    type: 'string',
    required: '是',
    desc: '题干文本，建议 URL 编码',
  },
  {
    name: 'type',
    type: 'string',
    required: '否',
    desc: '题型：single/multiple/judgement/completion',
  },
  { name: 'options', type: 'string', required: '否', desc: '选项，逗号分隔' },
];
const endpointParamsCols: DataTableColumns<(typeof endpointParams)[number]> = [
  { title: '字段', key: 'name', width: 140 },
  { title: '类型', key: 'type', width: 100 },
  { title: '必填', key: 'required', width: 80 },
  { title: '说明', key: 'desc' },
];
const respFields = [
  { name: 'code', type: 'int', desc: '1 成功，其他错误码' },
  { name: 'msg', type: 'string', desc: '提示信息' },
  { name: 'data.answer', type: 'string', desc: '命中答案' },
  { name: 'data.from', type: 'string', desc: '数据来源' },
  { name: 'data.es_synced', type: 'bool', desc: '是否已同步到 ES（最终一致）' },
];
const respCols: DataTableColumns<(typeof respFields)[number]> = [
  { title: '字段', key: 'name', width: 160 },
  { title: '类型', key: 'type', width: 100 },
  { title: '说明', key: 'desc' },
];

// ===== Tab3 OCS 配置 =====
const ocsConfig = computed(() => {
  const url = `${meta.value.api_base_url}?title={title}&type={type}&options={options}`;
  return JSON.stringify(
    {
      name: '搜题平台',
      homepage: window.location.origin,
      url,
      method: 'get',
      contentType: 'json',
      data: {},
      headers: {
        [meta.value.header_name]: defaultKey.value?.api_key ?? '<your_api_secret>',
      },
      handler: "return (res.code === 1) ? [res.msg, res.data.answer] : [res.msg, undefined]",
    },
    null,
    2,
  );
});

// ===== Tab4 请求示例 =====
const exampleTab = ref<'curl' | 'js' | 'php' | 'python'>('curl');

const curlExample = computed(
  () => `curl -X GET "${meta.value.api_base_url}?question=hello" \\
  -H "${meta.value.header_name}: ${defaultKey.value?.api_key ?? '<api_secret>'}"`,
);

const pyExample = computed(
  () => `import requests

headers = {"${meta.value.header_name}": "${defaultKey.value?.api_key ?? '<api_secret>'}"}
resp = requests.get("${meta.value.api_base_url}", params={"question": "hello"}, headers=headers, timeout=5)
print(resp.json())`,
);

const jsExample = computed(
  () => `fetch("${meta.value.api_base_url}?question=hello", {
  headers: { "${meta.value.header_name}": "${defaultKey.value?.api_key ?? '<api_secret>'}" }
}).then(r => r.json()).then(console.log);`,
);

const phpExample = computed(
  () => `<?php
$ch = curl_init("${meta.value.api_base_url}?question=hello");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ["${meta.value.header_name}: ${defaultKey.value?.api_key ?? '<api_secret>'}"]);
echo curl_exec($ch);`,
);

onMounted(loadMeta);
</script>

<template>
  <div class="doc-page p-6">
    <!-- 顶卡：API 密钥 -->
    <NCard :bordered="false" size="small" class="mb-4">
      <NSpin :show="metaLoading">
        <NAlert type="warning" :show-icon="false" class="mb-3">
          请妥善保管您的 API 密钥，切勿泄漏或提交到代码仓库。
        </NAlert>

        <div class="grid gap-2">
          <div class="row">
            <span class="row-label">API 密钥：</span>
            <span class="row-value mono">
              {{ defaultKey?.api_key ? maskKey(defaultKey.api_key) : '暂未生成' }}
            </span>
            <NButton
              size="small"
              type="primary"
              ghost
              :disabled="!defaultKey?.api_key"
              @click="copy(defaultKey?.api_key ?? '')"
            >
              复制
            </NButton>
            <NButton size="small" @click="openSetModal">设置</NButton>
          </div>
          <div class="row">
            <span class="row-label">接口地址：</span>
            <span class="row-value mono">{{ meta.api_base_url }}</span>
            <NButton
              size="small"
              type="primary"
              ghost
              @click="copy(meta.api_base_url)"
            >
              复制
            </NButton>
          </div>
          <div class="row">
            <span class="row-label">鉴权头名：</span>
            <span class="row-value mono">{{ meta.header_name }}</span>
          </div>
        </div>
      </NSpin>
    </NCard>

    <!-- 底卡：接口文档 Tabs -->
    <NCard :bordered="false" size="small" title="接口文档">
      <NTabs type="line" animated default-value="intro">
        <!-- Tab1 接口说明 -->
        <NTabPane name="intro" tab="接口说明">
          <NDescriptions
            label-placement="left"
            :column="1"
            bordered
            size="small"
          >
            <NDescriptionsItem label="请求方法">
              <NTag type="success" size="small">GET</NTag>
            </NDescriptionsItem>
            <NDescriptionsItem label="接口地址">
              <span class="mono">{{ meta.api_base_url }}</span>
            </NDescriptionsItem>
            <NDescriptionsItem label="Content-Type">
              application/json
            </NDescriptionsItem>
            <NDescriptionsItem label="鉴权方式">
              Header <code>{{ meta.header_name }}</code>
            </NDescriptionsItem>
          </NDescriptions>

          <div class="sub-title">请求参数</div>
          <NDataTable
            :columns="endpointParamsCols"
            :data="endpointParams"
            :bordered="true"
            size="small"
          />

          <div class="sub-title">响应结构</div>
          <NDataTable
            :columns="respCols"
            :data="respFields"
            :bordered="true"
            size="small"
          />
        </NTabPane>

        <!-- Tab2 参数详解 -->
        <NTabPane name="params" tab="参数详解">
          <div class="sub-title">题型 type 枚举</div>
          <ul class="param-list">
            <li><code>single</code> — 单选题</li>
            <li><code>multiple</code> — 多选题</li>
            <li><code>judgement</code> — 判断题</li>
            <li><code>completion</code> — 填空题</li>
          </ul>
          <div class="sub-title">错误码</div>
          <ul class="param-list">
            <li><code>40002</code> — 鉴权失败</li>
            <li><code>40006</code> — 余额/配额不足</li>
            <li><code>40404</code> — 题目未命中</li>
            <li><code>50001</code> — 上游接口异常</li>
          </ul>
          <div class="sub-title">示例响应</div>
          <NCode
            language="json"
            :code="`{\n  \&quot;code\&quot;: 1,\n  \&quot;msg\&quot;: \&quot;success\&quot;,\n  \&quot;data\&quot;: {\n    \&quot;answer\&quot;: \&quot;A\&quot;,\n    \&quot;from\&quot;: \&quot;cache\&quot;,\n    \&quot;es_synced\&quot;: true\n  }\n}`"
            show-line-numbers
          />
        </NTabPane>

        <!-- Tab3 OCS 配置 -->
        <NTabPane name="ocs" tab="OCS 配置">
          <NAlert type="info" :show-icon="false" class="mb-3">
            粘贴到 OCS 自定义题库即可使用。已自动注入您的默认密钥。
          </NAlert>
          <NSpace>
            <NButton type="primary" size="small" @click="copy(ocsConfig)">
              一键复制配置
            </NButton>
          </NSpace>
          <div class="mt-2">
            <NCode language="json" :code="ocsConfig" show-line-numbers />
          </div>
        </NTabPane>

        <!-- Tab4 请求示例 -->
        <NTabPane name="example" tab="请求示例">
          <NTabs v-model:value="exampleTab" type="segment" size="small">
            <NTabPane name="curl" tab="cURL">
              <NButton size="tiny" class="copy-btn" @click="copy(curlExample)">
                复制
              </NButton>
              <NCode language="bash" :code="curlExample" show-line-numbers />
            </NTabPane>
            <NTabPane name="python" tab="Python">
              <NButton size="tiny" class="copy-btn" @click="copy(pyExample)">
                复制
              </NButton>
              <NCode language="python" :code="pyExample" show-line-numbers />
            </NTabPane>
            <NTabPane name="js" tab="JavaScript">
              <NButton size="tiny" class="copy-btn" @click="copy(jsExample)">
                复制
              </NButton>
              <NCode language="javascript" :code="jsExample" show-line-numbers />
            </NTabPane>
            <NTabPane name="php" tab="PHP">
              <NButton size="tiny" class="copy-btn" @click="copy(phpExample)">
                复制
              </NButton>
              <NCode language="php" :code="phpExample" show-line-numbers />
            </NTabPane>
          </NTabs>
        </NTabPane>
      </NTabs>
    </NCard>

    <!-- 设置默认密钥 Modal -->
    <NModal
      v-model:show="setModalVisible"
      preset="card"
      title="选择默认密钥"
      style="width: 480px"
    >
      <NEmpty v-if="keys.length === 0" description="暂无启用中的 API Key" />
      <NRadioGroup v-else v-model:value="setChoice">
        <NSpace vertical>
          <NRadio v-for="k in keys" :key="k.id" :value="k.id">
            {{ k.app_name }}
            <span class="mono ml-2 text-xs text-gray-400">
              {{ maskKey(k.api_key, 12, 4) }}
            </span>
          </NRadio>
        </NSpace>
      </NRadioGroup>
      <template #footer>
        <NSpace justify="end">
          <NButton @click="setModalVisible = false">取消</NButton>
          <NButton type="primary" @click="confirmSetDefault">确认</NButton>
        </NSpace>
      </template>
    </NModal>
  </div>
</template>

<style scoped>
.doc-page {
  max-width: 1200px;
  margin: 0 auto;
}
.row {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 6px 0;
}
.row-label {
  width: 90px;
  color: #666;
  font-size: 13px;
  flex-shrink: 0;
}
.row-value {
  flex: 1;
  font-size: 13px;
  color: #333;
  word-break: break-all;
}
.mono {
  font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
}
.sub-title {
  margin: 16px 0 8px;
  font-size: 14px;
  font-weight: 600;
  color: #2080f0;
  padding-left: 6px;
  border-left: 3px solid #2080f0;
}
.param-list {
  padding-left: 24px;
  line-height: 1.9;
  font-size: 13px;
  color: #555;
}
.param-list code {
  background: #f5f7fa;
  padding: 1px 5px;
  border-radius: 3px;
}
.copy-btn {
  float: right;
  margin-bottom: 4px;
}
.grid {
  display: grid;
}
.gap-2 {
  gap: 8px;
}
.ml-2 {
  margin-left: 8px;
}
.text-xs {
  font-size: 12px;
}
.text-gray-400 {
  color: #999;
}
.mt-2 {
  margin-top: 8px;
}
.mb-3 {
  margin-bottom: 12px;
}
.mb-4 {
  margin-bottom: 16px;
}
</style>
