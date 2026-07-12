<!-- 系统配置 -->
<template>
  <div class="app-container">
    <el-card shadow="never" class="table-card">
      <div class="toolbar">
        <div class="left-toolbar">
          <el-button-group>
            <el-button v-hasPerm="['sys:config:add']" type="success" v-icon="'vea-o-plus'" @click="handleOpenDialog()">
              {{ t('common.add') }}
            </el-button>
            <el-button v-hasPerm="['sys:config:refresh']" color="#626aef" v-icon="'vea-o-refresh'" @click="handleRefreshCache">
              {{ t('common.refresh') }}
            </el-button>
          </el-button-group>
        </div>
        <div class="right-toolbar">
          <el-form ref="queryFormRef" :model="pageData.params" :inline="true">
            <el-form-item :label="t('common.keywords')" prop="keywords">
              <el-input v-model="pageData.params.keywords" placeholder="请输入配置键\配置名称" clearable @keyup.enter="handleQuery" />
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

      <el-table ref="dataTableRef" v-loading="loading" :data="pageData.list" highlight-current-row @selection-change="handleSelectionChange">
        <el-table-column type="index" label="Idx" width="60" />
        <el-table-column key="configName" :label="t('common.name')" prop="configName" min-width="100" />
        <el-table-column key="configKey" label="Key" prop="configKey" min-width="100" />
        <el-table-column key="configValue" label="Value" prop="configValue" min-width="100" />
        <el-table-column key="remark" label="Remark" prop="remark" min-width="100" />
        <el-table-column fixed="right" label="Operations" width="220">
          <template #default="scope">
            <el-button-group>
              <el-button v-hasPerm="['sys:config:update']" type="primary" size="small" v-icon="'vea-o-edit'"
                @click="handleOpenDialog(scope.row)">
                {{ t('common.edit') }}
              </el-button>
              <el-button v-hasPerm="['sys:config:delete']" type="danger" size="small" v-icon="'vea-o-delete'"
                @click="handleDelete(scope.row.id)">
                {{ t('common.delete') }}
              </el-button>
            </el-button-group>
          </template>
        </el-table-column>
      </el-table>

      <pagination v-if="pageData.total > 0" v-model:total="pageData.total" v-model:page="pageData.params.pageNum"
        v-model:limit="pageData.params.pageSize" @pagination="handleQuery" />
    </el-card>

    <!-- 系统配置表单弹窗 -->
    <el-dialog v-model="dialog.visible" :title="dialog.title" width="500px" @close="handleCloseDialog">
      <el-form ref="dataFormRef" :model="dialog.formData" :rules="rules" label-suffix=":" label-width="100px">
        <el-form-item label="配置名称" prop="configName">
          <el-input v-model="dialog.formData.configName" placeholder="请输入配置名称" :maxlength="50" />
        </el-form-item>
        <el-form-item label="配置键" prop="configKey">
          <el-input v-model="dialog.formData.configKey" placeholder="请输入配置键" :maxlength="50" />
        </el-form-item>
        <el-form-item label="配置值" prop="configValue">
          <el-input v-model="dialog.formData.configValue" placeholder="请输入配置值" :maxlength="100" />
        </el-form-item>
        <el-form-item label="描述" prop="remark">
          <el-input v-model="dialog.formData.remark" :rows="4" :maxlength="100" show-word-limit type="textarea" placeholder="请输入描述" />
        </el-form-item>
      </el-form>
      <template #footer>
        <div class="dialog-footer">
          <el-button type="primary" @click="handleSubmit">{{ t('common.confirm') }}</el-button>
          <el-button @click="handleCloseDialog">{{ t('common.cancel') }}</el-button>
        </div>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
  defineOptions({
    name: "SystemConfig",
    inheritAttrs: false,
  });

  import ConfigAPI, { ConfigItem, ConfigForm } from "@/api/system/config";

  const queryFormRef = ref();
  const dataFormRef = ref();

  const t = useI18n().t;
  const loading = ref(false);
  const selectIds = ref<number[]>([]);

  // 系统配置表格数据
  const pageData = reactive<PageResult<ConfigItem>>({
    list: [] as ConfigItem[],
    total: 0,
    params: {
      pageNum: 1,
      pageSize: 25,
      keywords: ""
    }
  });

  const dialog = reactive({
    title: "",
    visible: false,
    formData: {
      id: 0,
      configName: "",
      configKey: "",
      configValue: "",
      remark: "",
    } as ConfigForm
  });

  const rules = reactive({
    configName: [{ required: true, message: "请输入系统配置名称", trigger: "blur" }],
    configKey: [{ required: true, message: "请输入系统配置编码", trigger: "blur" }],
    configValue: [{ required: true, message: "请输入系统配置值", trigger: "blur" }],
  });

  // 查询系统配置
  const handleQuery = () => {
    loading.value = true;
    ConfigAPI.getPage(pageData.params)
      .then((data: PageResult<ConfigItem>) => {
        Object.assign(pageData, data);
      })
      .finally(() => {
        loading.value = false;
      });
  }

  // 重置查询
  const handleResetQuery = () => {
    queryFormRef.value.resetFields();
    pageData.params.pageNum = 1;
    handleQuery();
  }

  // 行复选框选中项变化
  const handleSelectionChange = (selection: any) => {
    selectIds.value = selection.map((item: any) => item.id);
  }

  // 新增加或修改时打开系统配置弹窗
  const handleOpenDialog = (data?: ConfigItem) => {
    dialog.visible = true;
    dialog.title = data ? "修改系统配置" : "新增系统配置";
    if (data !== undefined) {
      Object.assign(dialog.formData, data);
    } else {
      resetForm();
    }
  }

  // 刷新缓存(防抖)
  const handleRefreshCache = useDebounceFn(() => {
    ConfigAPI.refreshCache().then(() => {
      ElMessage.success("刷新成功");
    });
  }, 1000);

  // 系统配置表单提交
  const handleSubmit = () => {
    dataFormRef.value.validate((valid: any) => {
      if (valid) {
        loading.value = true;
        ConfigAPI.set(dialog.formData.id, dialog.formData)
          .then((data) => {
            ElMessage.success("操作成功");
            handleCloseDialog();
            handleResetQuery();
          })
          .finally(() => (loading.value = false));
      }
    });
  }
  // 重置表单
  const resetForm = () => {
    dataFormRef.value.resetFields();
    dataFormRef.value.clearValidate();
    dialog.formData.id = 0;
  }

  // 关闭系统配置弹窗
  const handleCloseDialog = () => {
    dialog.visible = false;
    resetForm();
  }

  // 删除系统配置
  const handleDelete = (id: number) => {
    ElMessageBox.confirm("确认删除该项配置?", "警告", {
      confirmButtonText: "确定",
      cancelButtonText: "取消",
      type: "warning",
    }).then(() => {
      loading.value = true;
      ConfigAPI.delete(id)
        .then(() => {
          ElMessage.success("删除成功");
          handleResetQuery();
        })
        .finally(() => (loading.value = false));
    });
  }

  onMounted(() => {
    handleQuery();
  });
</script>
