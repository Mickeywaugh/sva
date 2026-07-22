<!-- 系统配置 -->
<template>
  <div class="app-container">
    <el-card shadow="never" class="table-card">
      <div class="toolbar">
        <div class="left-toolbar">
          <el-button-group class="inline flex">
            <el-button :disabled="loading" type="success" v-icon="'bt-o-sync'" @click="handleSync">{{ t('common.sync') }}</el-button>
            <el-button :disabled="loading" type="primary" v-icon="'bt-testing'" @click="handleAutotest">{{ t('sys.api.autoTest') }}</el-button>
          </el-button-group>
        </div>
        <div class="right-toolbar">
          <el-form ref="queryFormRef" :model="pageData.params" :inline="true">
            <el-form-item label="模块" prop="module">
              <sys-api-module-select v-model="pageData.params.module" style="width: 200px;"></sys-api-module-select>
            </el-form-item>
            <el-form-item :label="t('common.keywords')" prop="keywords">
              <el-input v-model="pageData.params.keywords" placeholder="Keywords" clearable @keyup.enter="handleQuery" />
            </el-form-item>
            <el-form-item>
              <el-button-group>
                <el-button type="primary" v-icon="'bt-search'" @click="handleQuery">{{ t('common.search') }}</el-button>
                <el-button v-icon="'bt-refresh'" @click="handleResetQuery">{{ t('common.reset') }}</el-button>
              </el-button-group>
            </el-form-item>
          </el-form>
        </div>
      </div>

      <el-table ref="dataTableRef" v-loading="loading" :data="pageData.list" border highlight-current-row fit>
        <el-table-column type="index" label="No." width="60" />
        <el-table-column label="模块" prop="module" width="96" />
        <el-table-column label="名称" prop="name" min-width="120" />
        <el-table-column label="路由" prop="path" min-width="140" />
        <el-table-column label="请求方法" prop="method" width="80">
          <template #default="{ row }">
            <el-tag :type="row.withJwt ? 'success' : 'info'">{{ row.method }}</el-tag>
          </template>
        </el-table-column>
        <el-table-column label="请求参数" prop="queryParams" width="80">
          <template #default="{ row }">
            <el-popover v-if="row.queryParams" :width="480" trigger="click">
              <template #reference>
                <el-button v-icon="'bt-eye-open'"></el-button>
              </template>
              <slot>{{ JSON.stringify(row.queryParams) }}</slot>
            </el-popover>
          </template>
        </el-table-column>
        <el-table-column label="路由参数" prop="routeParams" width="80">
          <template #default="{ row }">
            <el-popover v-if="row.routeParams" :width="480" trigger="click">
              <template #reference>
                <el-button v-icon="'bt-eye-open'"></el-button>
              </template>
              <slot>{{ JSON.stringify(row.routeParams) }}</slot>
            </el-popover>
          </template>
        </el-table-column>
        <el-table-column label="Body参数" prop="bodyParams" width="80">
          <template #default="{ row }">
            <el-popover v-if="row.bodyParams" :width="480" trigger="click">
              <template #reference>
                <el-button v-icon="'bt-eye-open'"></el-button>
              </template>
              <slot>{{ JSON.stringify(row.bodyParams) }}</slot>
            </el-popover>
          </template>
        </el-table-column>
        <el-table-column label="最近结果" prop="result" width="96">
          <template #default="{ row }">
            <status-tag v-model="row.result" :map="SysApiResultMap"></status-tag>
          </template>
        </el-table-column>
        <el-table-column label="响应码" prop="responseCode" width="80" />
        <el-table-column label="响应内容" prop="responseContext" width="80">
          <template #default="{ row }">
            <el-popover v-if="row.responseContext" :width="720" trigger="click">
              <template #reference>
                <el-button v-icon="'bt-eye-open'"></el-button>
              </template>
              <slot>{{ row.responseContext }}</slot>
            </el-popover>
          </template>
        </el-table-column>
        <el-table-column label="启用" prop="disabled" width="96">
          <template v-slot="{ row }">
            <el-switch v-model="row.disabled" :active-value="0" :inactive-value="1" inline-prompt :active-text="t('common.yes')"
              :inactive-text="t('common.no')" :before-change="() => handleDisabled(row)" />
          </template>
        </el-table-column>
        <el-table-column label="更新" prop="updateTime" width="140" />
        <el-table-column fixed="right" label="操作" min-width="220">
          <template #default="{ row }">
            <el-button-group>
              <el-button type="danger" size="small" v-icon="'bt-o-delete'" @click="handleDelete(row.id)">
                {{ t('common.delete') }}
              </el-button>
              <el-button type="primary" size="small" v-icon="'bt-o-edit'" @click="openDialog(row)">
                {{ t('common.edit') }}
              </el-button>
              <el-button :disabled="row.disabled ? true : false" type="success" size="small" v-icon="'bt-o-test'" @click="handleTest(row)">
              测试
              </el-button>
            </el-button-group>
          </template>
        </el-table-column>
      </el-table>

      <pagination v-if="pageData.total > 0" v-model:total="pageData.total" v-model:page="pageData.params.pageNum"
        v-model:limit="pageData.params.pageSize" @pagination="handleQuery" />
    </el-card>

    <el-dialog v-model="dialogData.visible" :title="dialogData.title" width="500px" @close="closeDialog()" :close-on-click-modal="false"
      draggable destroy-on-close>
      <el-form ref="SysApiFormRef" :model="dialogData.formData" :rules="dialogData.formRules" label-width="auto">
        <el-form-item label="模块" prop="module">
          <sys-api-module-select disabled v-model="dialogData.formData.module" style="width: 100%;" />
        </el-form-item>
        <el-form-item label="名称" prop="name">
          <el-input disabled v-model="dialogData.formData.name" placeholder="接口名称" />
        </el-form-item>
        <el-form-item label="请求方法" prop="method">
          <el-select disabled v-model="dialogData.formData.method" placeholder="请选择请求方法" style="width: 100%;">
            <el-option label="GET" value="GET" />
            <el-option label="POST" value="POST" />
            <el-option label="PUT" value="PUT" />
            <el-option label="DELETE" value="DELETE" />
            <el-option label="PATCH" value="PATCH" />
          </el-select>
        </el-form-item>
        <el-form-item label="路由" prop="path">
          <el-input disabled v-model="dialogData.formData.path" placeholder="接口路径" />
        </el-form-item>
        <el-form-item label="路由参数" prop="routeParams">
          <json-editor v-model="dialogData.formData.routeParams" :rows="3" />
        </el-form-item>
        <el-form-item label="Query参数" prop="queryParams">
          <json-editor v-model="dialogData.formData.queryParams" :rows="3" />
        </el-form-item>
        <el-form-item label="Body请求参数" prop="bodyParams">
          <json-editor v-model="dialogData.formData.bodyParams" :rows="8" />
        </el-form-item>
        <el-form-item label="需要JWT" prop="withJwt">
          <el-switch v-model="dialogData.formData.withJwt" :active-value="1" :inactive-value="0" />
        </el-form-item>
      </el-form>
      <template #footer>
        <div class="dialog-footer">
          <el-button type="primary" @click="handleSubmit()">提交</el-button>
          <el-button @click="closeDialog()">取消</el-button>
        </div>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
  defineOptions({
    name: "SysApi"
  });

  import SysApi, { SysApiForm, SysApiItem, SysApiMsg, SysApiResultMap } from "@/api/system/api";
  import SysApiModuleSelect from "@/components/SelectCmps/sysApiModuleSelect.vue";

  const queryFormRef = ref();
  const SysApiFormRef = ref();

  const t = useI18n().t;
  const loading = ref(false);
  const moduleOptions = ref<OptionItem[]>([]);
  // 系统配置表格数据
  const pageData = reactive<PageResult<SysApiItem>>({
    list: [],
    total: 0,
    params: {
      pageNum: 1,
      pageSize: 25,
      module: undefined,
      keywords: ""
    }
  });

  const dialogData = reactive({
    visible: false,
    title: "",
    formData: { id: 0 } as SysApiForm,
    formRules: {
      name: [{ required: true, message: "请输入名称", trigger: ["change"] }],
      path: [{ required: true, message: "请输入路径", trigger: ["change"] }],
      method: [{ required: true, message: "请选择请求方法", trigger: ["change"] }],
      withJwt: [{ required: true, message: "请选择是否需要JWT", trigger: ["change"] }],
    }
  });

  // 查询系统配置
  const handleQuery = async () => {
    loading.value = true;
    await SysApi.page(pageData.params)
      .then((data: PageResult<SysApiItem>) => {
        Object.assign(pageData, data);
      }).catch((error) => {
        console.error(error);
      })
      .finally(() => {
        loading.value = false;
      });
  }

  // 重置查询
  const handleResetQuery = () => {
    queryFormRef.value.resetFields();
    pageData.params.pageNum = 1;
    pageData.params.keywords = "";
    pageData.params.module = undefined;
    handleQuery();
  }

  // 删除系统api
  const handleDelete = (id: number) => {
    ElMessageBox.confirm("确认删除该系统接口项?", "警告", {
      confirmButtonText: "确定",
      cancelButtonText: "取消",
      type: "warning",
    }).then(async () => {
      loading.value = true;
      await SysApi.delete(id)
        .then(() => {
          ElMessage.success("删除成功");
          // 删除pageData.list中id为id的项
          pageData.list = pageData.list.filter(item => item.id !== id);
        })
        .finally(() => (loading.value = false));
    });
  }

  const handleSync = async () => {
    loading.value = true;
    await SysApi.sync()
      .then(() => {
        ElMessage.success("同步成功");
        handleQuery();
      })
      .finally(() => (loading.value = false));
  }

  const openDialog = async (row: SysApiItem) => {
    dialogData.visible = true;
    const { createTime, updateTime, responseCode, responseContext, result, ...formData } = row;
    dialogData.formData = formData;
  }

  const closeDialog = () => {
    dialogData.visible = false;
  }

  const handleSubmit = () => {
    SysApiFormRef.value.validate().then(() => {
      loading.value = true;
      SysApi.set(dialogData.formData)
        .then((data: any) => {
          ElMessage.success("保存成功");
          if (dialogData.formData.id > 0) {
            //更新pageData.list中id为data.id的项
            const index = pageData.list.findIndex(item => item.id === data.id);
            if (index !== -1) {
              pageData.list[index] = data;
            }
          } else {
            // 新增数据
            pageData.list.unshift(data);
          }
          closeDialog();
        })
        .catch((error) => {
          ElMessage.error(error.message);
        }).finally(() => {
          loading.value = false;
        });
    })
  }

  const handleDisabled = async (row: SysApiItem): Promise<boolean> => {
    if (!row.id) return false;
    await ElMessageBox.confirm(`确定更改吗?`, "提示", {
      confirmButtonText: "确定",
      cancelButtonText: "取消",
      type: "warning",
    }).then(async () => {
      loading.value = true;
      const updateData = { id: row.id, disabled: row.disabled ? 0 : 1 }
      await SysApi.set(updateData).then((data: any) => {
        Object.assign(row, data);
        ElMessage.success("修改成功");
        return true;
      }).catch((error) => {
        ElMessage.error(error.message);
      }).finally(() => {
        loading.value = false;
      });
      return false;
    }).catch(() => {
      return false;
    });
    return false;
  }

  const handleTest = async (row: SysApiItem) => {
    loading.value = true;
    await SysApi.test(row.id).then((data) => {
      Object.assign(row, data);
    }).catch((error) => {
      console.error(error);
    }).finally(() => {
      loading.value = false;
    });
  }

  const handleAutotest = async () => {
    loading.value = true;
    let count = 0;
    const total = pageData.list.length;

    SysApi.autoTestStream(
      // 每收到一个接口测试结果
      (sysApi: SysApiMsg) => {
        count++;
        const idx = pageData.list.findIndex(item => item.id === sysApi.id);
        if (idx !== -1) {
          pageData.list[idx].result = sysApi.result;
          pageData.list[idx].responseCode = sysApi.responseCode;
          pageData.list[idx].responseContext = sysApi.responseContext;
        }
      },
      // 全部完成
      () => {
        loading.value = false;
        ElMessage.success(`自动测试完成: ${count} / ${total}`);
      },
      // 出错
      (err: Error) => {
        loading.value = false;
        ElMessage.error(`自动测试中断: ${err.message}`);
      }
    );
  }

  onBeforeMount(() => {
    SysApi.getModuleOptions()
      .then((data: any) => {
        moduleOptions.value = data;
      });
  });

  onMounted(() => {
    handleQuery();
  });
</script>
