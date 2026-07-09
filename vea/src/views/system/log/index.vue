<template>
  <div class="app-container">
    <el-card shadow="never" class="table-card">
      <div class="toolbar">
        <div class="left-toolbar"></div>
        <div class="right-toolbar">
          <el-form ref="queryFormRef" :model="pageData.params" :inline="true">
            <el-form-item :label="t('common.keywords')" prop="keywords">
              <el-input v-model="pageData.params.keywords" placeholder="日志内容" clearable @keyup.enter="handleQuery" />
            </el-form-item>
            <el-form-item prop="createTime" label="操作时间">
              <el-date-picker v-model="pageData.params.createTime" :editable="false" class="!w-[240px]" type="daterange" range-separator="~"
                start-placeholder="开始时间" end-placeholder="截止时间" value-format="YYYY-MM-DD" />
            </el-form-item>
            <el-form-item>
              <el-button-group>
                <el-button type="primary" v-icon="'vea-search'" @click="handleQuery">{{ t('common.search') }}</el-button>
                <el-button v-icon="'vea-refresh'" @click="handleResetQuery">{{ t('common.reset') }}</el-button>
              </el-button-group>
            </el-form-item>
          </el-form>
        </div>
      </div>
      <el-table v-loading="loading" :data="pageData.list" highlight-current-row border>
        <el-table-column label="操作时间" prop="createTime" />
        <el-table-column label="操作人" prop="operator" />
        <el-table-column label="请求路由" prop="requestUri" />
        <el-table-column label="请求方法" prop="requestMethod" />
        <el-table-column label="请求参数" prop="requestParams" />
        <el-table-column label="响应码" prop="responseCode" />
        <el-table-column label="IP 地址" prop="ip" />
        <el-table-column label="地区" prop="region" />
        <el-table-column label="浏览器" prop="browser" />
        <el-table-column label="终端系统" prop="os" show-overflow-tooltip />
        <el-table-column label="执行时间(ms)" prop="executionTime" />
      </el-table>

      <pagination v-if="pageData.total > 0" v-model:total="pageData.total" v-model:page="pageData.params.pageNum"
        v-model:limit="pageData.params.pageSize" @pagination="handleQuery" />
    </el-card>
  </div>
</template>

<script setup lang="ts">
  defineOptions({
    name: "SystemLog",
    inheritAttrs: false,
  });

  import LogAPI, { SysLogItem } from "@/api/system/log";

  const queryFormRef = ref();

  const t = useI18n().t;
  const loading = ref(false);

  // 日志表格数据
  const pageData = reactive<PageResult<SysLogItem>>({
    list: [],
    total: 0,
    params: {
      pageNum: 1,
      pageSize: 25,
      keywords: "",
      createTime: undefined,
    }
  });

  /** 查询 */
  function handleQuery() {
    loading.value = true;
    LogAPI.getPage(pageData.params)
      .then((data: PageResult<SysLogItem>) => {
        Object.assign(pageData, data);
      })
      .finally(() => {
        loading.value = false;
      });
  }
  /** 重置查询 */
  function handleResetQuery() {
    queryFormRef.value.resetFields();
    pageData.params.pageNum = 1;
    pageData.params.createTime = undefined;
    handleQuery();
  }

  onMounted(() => {
    handleQuery();
  });
</script>
