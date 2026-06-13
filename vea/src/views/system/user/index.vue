<!-- 用户管理 -->
<template>
  <div class="app-container">
    <!-- 用户列表 -->
    <el-card shadow="never" class="table-card">
      <div class="toolbar">
        <div class="left-toolbar">
          <el-button-group>
            <el-button v-hasPerm="['sys:user:add']" type="success" v-icon="'vea-o-plus'" @click="handleOpenDialog()">
              {{ t('common.add') }}
            </el-button>
            <el-button v-hasPerm="'sys:user:delete'" type="danger" v-icon="'vea-o-delete'" :disabled="selectIds.length === 0"
              @click="handleDelete()">
              {{ t('common.delete') }}
            </el-button>
            <el-button v-hasPerm="'sys:user:import'" v-icon="'vea-cloud-upload'" @click="handleOpenImportDialog">
              {{ t('common.import') }}
            </el-button>
            <el-button v-hasPerm="'sys:user:export'" v-icon="'vea-cloud-download'" @click="handleExport">
              {{ t('common.export') }}
            </el-button>
          </el-button-group>
        </div>
        <div class="right-toolbar">
          <el-form ref="queryFormRef" :model="userTableData.params" :inline="true">
            <el-form-item :label="t('sys.dept.node')" prop="dept">
              <el-tree-select v-model="userTableData.params.dept" :placeholder="$t('common.please.select', { s: $t('sys.dept.node') })
                " :data="deptOptions" filterable check-strictly clearable :render-after-expand="false" style="width: 180px;" />
            </el-form-item>
            <el-form-item :label="t('common.keywords')" prop="keywords">
              <el-input v-model="userTableData.params.keywords" placeholder="用户名/昵称/手机号" clearable style="width: 200px"
                @keyup.enter="handleQuery" />
            </el-form-item>
            <el-form-item :label="t('common.status')" prop="status">
              <el-select v-model="userTableData.params.status" placeholder="全部" clearable class="!w-[100px]">
                <el-option :label="t('common.enable')" :value="1" />
                <el-option :label="t('common.disable')" :value="0" />
              </el-select>
            </el-form-item>
            <el-form-item :label="t('common.createTime')">
              <el-date-picker v-model="userTableData.params.createTime" :editable="false" class="!w-[240px]" type="daterange"
                range-separator="~" start-placeholder="开始时间" end-placeholder="截止时间" value-format="YYYY-MM-DD" />
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

      <el-table v-loading="loading" :data="userTableData.list" @selection-change="handleSelectionChange">
        <el-table-column type="selection" width="50" align="center" />
        <el-table-column label="Username" prop="username" />
        <el-table-column label="Nick Name" width="150" align="center" prop="nickname" />
        <el-table-column label="Sex" width="100" align="center">
          <template #default="{ row }">
            <!-- 性别字典翻译 -->
            <DictLabel v-model="row.gender" code="gender" />
          </template>
        </el-table-column>
        <el-table-column :label="t('sys.dept.node')" width="120" align="center" prop="deptName" />
        <el-table-column :label="t('sys.role.node')" align="center" prop="rolesNames" />
        <el-table-column :label="t('sys.user.mobile')" align="center" prop="mobile" width="120" />
        <el-table-column :label="t('common.status')" align="center" prop="status" width="90">
          <template #default="{ row }">
            <el-switch v-model="row.status" :active-value="1" :inactive-value="0" inline-prompt :active-text="$t('common.enable')"
              :inactive-text="$t('common.disable')" :before-change="() => handleFieldChange(row, { status: Number(!row.status) })" />
          </template>
        </el-table-column>
        <el-table-column :label="t('common.createTime')" align="center" prop="createTime" width="180" />
        <el-table-column :label="t('common.operation')" fixed="right">
          <template #default="{ row }">
            <el-button v-hasPerm="'sys:user:reset_pwd'" type="primary" v-icon="'vea-o-refresh'" size="small"
              @click="handleResetPassword(row as SysUserItem)">
              {{ t('login.resetPassword') }}
            </el-button>
            <el-button v-hasPerm="'sys:user:edit'" type="primary" v-icon="'vea-o-edit'" size="small" @click="handleOpenDialog(row.id)">
              {{ t('common.edit') }}
            </el-button>
            <el-button v-hasPerm="'sys:user:delete'" type="danger" v-icon="'vea-o-delete'" size="small" @click="handleDelete(row.id)">
              {{ t('common.delete') }}
            </el-button>
          </template>
        </el-table-column>
      </el-table>

      <pagination v-if="userTableData.total > 0" v-model:total="userTableData.total" v-model:page="userTableData.params.pageNum"
        v-model:limit="userTableData.params.pageSize" @pagination="handleQuery" />
    </el-card>

    <!-- 用户表单 -->
    <el-drawer v-model="dialog.visible" :title="dialog.title" append-to-body @close="handleCloseDialog">
      <el-form ref="userFormRef" :model="formData" :rules="rules" label-width="90px">
        <el-form-item :label="$t('sys.user.name')" prop="username">
          <el-input v-model="formData.username" :readonly="!!formData.id"
            :placeholder="$t('common.please.input', { s: $t('sys.user.name') })" />
        </el-form-item>

        <el-form-item :label="$t('sys.user.nickName')" prop="nickname">
          <el-input v-model="formData.nickname" :placeholder="$t('common.please.input', { s: $t('sys.user.nickName') })
            " />
        </el-form-item>

        <el-form-item :label="$t('sys.user.gender')" prop="gender">
          <Dict v-model="formData.gender" type="radio" code="gender" />
        </el-form-item>

        <el-form-item :label="$t('sys.role.node')" prop="roleIds">
          <el-select v-model="formData.roleIds" multiple :placeholder="$t('common.please.select')">
            <el-option v-for="item in roleOptions" :key="item.value" :label="item.label" :value="item.value" />
          </el-select>
        </el-form-item>

        <el-form-item :label="$t('sys.dept.node')" prop="dept">
          <el-tree-select v-model="formData.dept" :placeholder="$t('common.please.select', { s: $t('sys.dept.node') })
            " :data="deptOptions" filterable check-strictly :render-after-expand="false" />
        </el-form-item>

        <el-form-item :label="$t('sys.user.mobile')" prop="mobile">
          <el-input v-model="formData.mobile" :placeholder="$t('common.please.input', { s: $t('sys.user.mobile') })
            " maxlength="11" />
        </el-form-item>

        <el-form-item :label="$t('sys.user.email')" prop="email">
          <el-input v-model="formData.email" :placeholder="$t('common.please.input', { s: $t('sys.user.email') })
            " maxlength="50" />
        </el-form-item>

        <el-form-item :label="$t('common.status')" prop="status">
          <el-switch v-model="formData.status" :active-value="1" :inactive-value="0" inline-prompt :active-text="$t('common.enable')"
            :inactive-text="$t('common.disable')" />
        </el-form-item>
      </el-form>

      <template #footer>
        <div class="dialog-footer">
          <el-button type="primary" @click="handleSubmit">确 定</el-button>
          <el-button @click="handleCloseDialog">取 消</el-button>
        </div>
      </template>
    </el-drawer>

    <!-- 用户导入 -->
    <UserImport v-model="importDialogVisible" @import-success="handleQuery()" />
  </div>
</template>

<script setup lang="ts">
  import UserAPI, { UserForm, SysUserItem } from "@/api/system/user";

  import DeptAPI from "@/api/system/dept.js";
  import RoleAPI from "@/api/system/role.js";
  import Dict from "@/components/Dict/index.vue";
  import UserImport from "./components/UserImport.vue";
  import { downloadBolb } from "@/utils/function";

  defineOptions({
    name: "SystemUser",
    inheritAttrs: false,
  });

  const t = useI18n().t;
  const queryFormRef = ref(ElForm);
  const userFormRef = ref(ElForm);

  const userTableData = reactive<PageResult<SysUserItem>>({
    total: 0,
    list: [],
    params: {
      pageNum: 1,
      pageSize: 20,
      deptId: undefined,
      createTime: undefined,
    },
  });
  const loading = ref(false);

  const dialog = reactive({
    visible: false,
    title: "新增用户",
  });

  const formData = reactive<UserForm>({
    status: 1,
    username: "",
    nickname: "",
    dept: undefined,
    mobile: "",
    email: "",
    roleIds: [],
    gender: 1,
    avatar: ""
  });

  const rules = reactive({
    username: [{ required: true, message: t("common.notNull", { s: t("sys.user.name") }), trigger: "blur" }],
    nickname: [{ required: true, message: t("common.notNull", { s: t("sys.user.nickName") }), trigger: "blur" }],
    dept: [{ required: true, message: t("sys.dept.needName"), trigger: "blur" }],
    gender: [{ required: true, message: t("common.please.select", { s: t("sys.user.gender") }), trigger: "blur" }],
    roleIds: [{ required: true, message: t("common.please.select", { s: t("sys.role.node") }), trigger: "blur" }],
    email: [
      {
        pattern: /\w[-\w.+]*@([A-Za-z0-9][-A-Za-z0-9]+\.)+[A-Za-z]{2,14}/,
        message: "请输入正确的邮箱地址",
        trigger: "blur",
      },
    ],
    mobile: [
      {
        pattern: /^1[3|4|5|6|7|8|9][0-9]\d{8}$/,
        message: "请输入正确的手机号码",
        trigger: "blur",
      },
    ],
  });

  // 选中的用户ID
  const selectIds = ref<number[]>([]);
  // 部门下拉数据源
  const deptOptions = ref<OptionItem[]>();
  // 角色下拉数据源
  const roleOptions = ref<OptionItem[]>();
  // 导入弹窗显示状态
  const importDialogVisible = ref(false);

  // 查询
  function handleQuery() {
    loading.value = true;
    UserAPI.getPage(userTableData.params)
      .then((data) => {
        Object.assign(userTableData, data);
      })
      .finally(() => {
        loading.value = false;
      });
  }

  // 重置查询
  function handleResetQuery() {
    queryFormRef.value.resetFields();
    userTableData.params.pageNum = 1;
    userTableData.params.dept = undefined;
    userTableData.params.createTime = undefined;
    handleQuery();
  }

  // 选中项发生变化
  function handleSelectionChange(selection: any[]) {
    selectIds.value = selection.map((item) => item.id);
  }

  // 重置密码
  function handleResetPassword(row: SysUserItem) {
    ElMessageBox.prompt(
      "请输入用户【" + row.username + "】的新密码",
      t("login.resetPassword"),
      {
        confirmButtonText: t("common.confirm"),
        cancelButtonText: t("common.cancel"),
        inputValidator: (value: string) => {
          if (!value || value.length < 6) {
            return t("sys.user.passwordLength");
          }
          return true;
        },
        inputErrorMessage: t("sys.user.passwordLength"),
      }
    ).then(({ value }: { value: string }) => {
      UserAPI.resetPassword(row.id, value).then(() => {
        ElMessage.success(t("sys.user.passwordResetOk", { s: value }));
      });
    }).catch(() => {
      ElMessage.info(t("common.canceled"));
    });
  }

  /**
   * 打开弹窗
   *
   * @param id 用户ID
   */
  async function handleOpenDialog(id?: number) {
    dialog.visible = true;
    // 加载角色下拉数据源
    roleOptions.value = await RoleAPI.getOptions();
    // 加载部门下拉数据源
    deptOptions.value = await DeptAPI.getOptions();

    if (id) {
      dialog.title = t("common.edit");
      UserAPI.getFormData(id).then((data:any) => {
        Object.assign(formData, { ...data });
      });
    } else {
      dialog.title = t('common.add');
    }
  }

  // 关闭弹窗
  function handleCloseDialog() {
    dialog.visible = false;
    userFormRef.value.resetFields();
    userFormRef.value.clearValidate();

    formData.id = undefined;
    formData.status = 1;
  }

  // 提交用户表单（防抖）
  const handleSubmit = () => {
    userFormRef.value.validate((valid: boolean) => {
      if (valid) {
        const userId = formData.id;
        loading.value = true;
        if (userId) {
          UserAPI.update(userId, formData)
            .then(() => {
              ElMessage.success(t("common.succeed.update", { s: "" }));
            })
            .finally(() => {
              loading.value = false;
              handleCloseDialog();
              handleQuery();
            });
        } else {
          UserAPI.add(formData)
            .then(() => {
              ElMessage.success(t("common.succeed.create", { s: "" }));
            })
            .finally(() => {
              loading.value = false;
              handleCloseDialog();
              handleQuery();
            });
        }
      }
    });
  };

  /**
   * 删除用户
   *
   * @param id  用户ID
   */
  function handleDelete(id?: number) {
    const ids = id !== undefined ? [id] : selectIds.value;
    const userIds = ids.join(",");
    if (!userIds) {
      ElMessage.warning(t("common.please.selectDeleteItems"));
      return;
    }

    ElMessageBox.confirm(t("common.confirms.delete", { s: "" }), t("common.warning"), {
      confirmButtonText: t("common.confirm"),
      cancelButtonText: t("common.cancel"),
      type: "warning",
    }).then(
      () => {
        loading.value = true;
        UserAPI.deleteByIds(userIds)
          .then(() => {
            ElMessage.success(t("common.succeed.delete", { s: "" }));
            handleQuery();
          })
          .finally(() => (loading.value = false));
      },
      () => {
        ElMessage.info(t("common.canceled"));
      }
    );
  }

  const handleFieldChange = async (row: UserForm, data: any): Promise<boolean> => {
    if (!row.id) return false;
    return ElMessageBox.confirm("确定更改吗?", "提示", {
      confirmButtonText: "确定",
      cancelButtonText: "取消",
      type: "warning",
    }).then(async () => {
      loading.value = true;
      return UserAPI.update(row.id ?? 0, data)
        .then((response) => {
          Object.assign(row, response.data);
          ElMessage.success("操作成功");
          return true;
        })
        .catch((error) => {
          ElMessage.error(error.message);
          return false; // API 失败时阻止切换
        })
        .finally(() => {
          loading.value = false;
        });
    }).catch(() => {
      // 用户取消确认，阻止切换
      return false;
    });
  }

  // 打开导入弹窗
  function handleOpenImportDialog() {
    importDialogVisible.value = true;
  }

  // 导出用户
  function handleExport() {
    UserAPI.export(userTableData.params).then((response: any) => {
      const contentDisposition = response.headers["content-disposition"];
      const fileName = contentDisposition
        ? decodeURI(contentDisposition.split("filename=")[1])
        : "user_export.xlsx";
      downloadBolb(response.data, fileName);
    });
  }

  onBeforeMount(() => {
    DeptAPI.getOptions().then((data) => {
      deptOptions.value = data;
    });
  });
  onMounted(() => {
    handleQuery();
  });
</script>
