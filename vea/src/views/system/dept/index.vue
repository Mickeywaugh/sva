<template>
  <div class="app-container">

    <el-card shadow="never" class="table-card">
      <div class="toolbar">
        <div class="left-toolbar">
          <el-button-group>
            <el-button v-hasPerm="['sys:dept:add']" type="success" v-icon="'vea-o-plus'"
              @click="handleOpenDialog()">{{ t('common.create') }}</el-button>
            <el-button v-hasPerm="['sys:dept:delete']" type="danger" :disabled="selectIds.length === 0" v-icon="'vea-o-delete'"
              @click="handleDelete()">{{ t('common.delete') }}</el-button>
          </el-button-group>
        </div>
        <div class="right-toolbar">
          <el-form ref="queryFormRef" :model="tableData.params" :inline="true">
            <el-form-item :label="t('common.keywords')" prop="keywords">
              <el-input v-model="tableData.params.keywords" placeholder="部门名称" @keyup.enter="handleQuery" />
            </el-form-item>
            <el-form-item label="部门状态" prop="status">
              <el-select v-model="tableData.params.status" placeholder="全部" clearable class="!w-[100px]">
                <el-option :value="1" label="正常" />
                <el-option :value="0" label="禁用" />
              </el-select>
            </el-form-item>
            <el-form-item>
              <el-button-group>
                <el-button class="filter-item" type="primary" v-icon="'vea-search'" @click="handleQuery">{{ t('common.search') }}</el-button>
                <el-button v-icon="'vea-refresh'" @click="handleResetQuery">重置</el-button>
              </el-button-group>
            </el-form-item>
          </el-form>
        </div>
      </div>
      <el-table v-loading="loading" :data="tableData.list" row-key="id" default-expand-all
        :tree-props="{ children: 'children', hasChildren: 'hasChildren' }" @selection-change="handleSelectionChange">
        <el-table-column type="selection" width="55" align="center" />
        <el-table-column prop="name" label="部门名称" min-width="200" />
        <el-table-column prop="status" label="状态" width="100">
          <template #default="scope">
            <el-tag v-if="scope.row.status == 1" type="success">正常</el-tag>
            <el-tag v-else type="info">禁用</el-tag>
          </template>
        </el-table-column>

        <el-table-column prop="sort" label="排序" width="100" />

        <el-table-column label="操作" fixed="right" align="left">
          <template #default="scope">
            <el-button-group>
              <el-button v-hasPerm="['sys:dept:add']" type="primary" size="small" v-icon="'vea-o-plus'"
                @click.stop="handleOpenDialog(scope.row.id, undefined)">
                新增
              </el-button>
              <el-button v-hasPerm="['sys:dept:edit']" type="primary" size="small" v-icon="'vea-o-edit'"
                @click.stop="handleOpenDialog(scope.row.parentId, scope.row.id)">
                编辑
              </el-button>
              <el-button v-hasPerm="['sys:dept:delete']" type="danger" size="small" v-icon="'vea-o-delete'"
                @click.stop="handleDelete(scope.row.id)">
                删除
              </el-button>
            </el-button-group>
          </template>
        </el-table-column>
      </el-table>
      <pagination v-if="tableData.total > 0" v-model:total="tableData.total" v-model:page="tableData.params.pageNum"
        v-model:limit="tableData.params.pageSize" @pagination="handleQuery" />
    </el-card>

    <el-dialog v-model="dialog.visible" :title="dialog.title" width="600px" @closed="handleCloseDialog">
      <el-form ref="deptFormRef" :model="formData" :rules="rules" label-width="80px">
        <el-form-item label="上级部门" prop="parentId">
          <el-tree-select v-model="formData.parentId" placeholder="选择上级部门" :data="deptOptions" filterable check-strictly
            :render-after-expand="false" />
        </el-form-item>
        <el-form-item label="部门名称" prop="name">
          <el-input v-model="formData.name" placeholder="请输入部门名称" />
        </el-form-item>
        <el-form-item label="显示排序" prop="sort">
          <el-input-number v-model="formData.sort" controls-position="right" style="width: 100px" :min="0" />
        </el-form-item>
        <el-form-item label="部门状态">
          <el-switch v-model="formData.status" inline-prompt :active-text="t('common.enable')" :inactive-text="t('common.disable')"
            :active-value="1" :inactive-value="0" />
        </el-form-item>
      </el-form>

      <template #footer>
        <div class="dialog-footer">
          <el-button type="primary" @click="handleSubmit">{{ $t('common.submit') }}</el-button>
          <el-button @click="handleCloseDialog">{{ $t('common.cancel') }}</el-button>
        </div>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
  defineOptions({
    name: "SystemDept",
    inheritAttrs: false,
  });

  import DeptAPI, { DeptVO, DeptForm, DeptQuery } from "@/api/system/dept";

  const queryFormRef = ref(ElForm);
  const deptFormRef = ref(ElForm);

  const loading = ref(false);
  const selectIds = ref<number[]>([]);
  const t = useI18n().t;

  const tableData = reactive<PageResult<DeptVO>>({
    list: [],
    total: 0,
    params: {
      pageNum: 1,
      pageSize: 25,
    },
  });

  const dialog = reactive({
    title: "",
    visible: false,
  });

  const deptOptions = ref<OptionItem[]>();
  const formData = reactive<DeptForm>({
    status: 1,
    parentId: 0,
    sort: 1,
  });

  const rules = reactive({
    parentId: [{ required: true, message: "上级部门不能为空", trigger: "change" }],
    name: [{ required: true, message: "部门名称不能为空", trigger: "blur" }],
    sort: [{ required: true, message: "显示排序不能为空", trigger: "blur" }],
  });

  // 查询部门
  function handleQuery() {
    loading.value = true;
    DeptAPI.page(tableData.params).then((data: any) => {
      tableData.list = data.list ?? [];
      tableData.total = data.total ?? 0;
    }).finally(() => {
      loading.value = false;
    });
  }
  // 重置查询
  function handleResetQuery() {
    queryFormRef.value.resetFields();
    handleQuery();
  }

  // 处理选中项变化
  function handleSelectionChange(selection: any) {
    selectIds.value = selection.map((item: any) => item.id);
  }

  /**
   * 打开部门弹窗
   *
   * @param parentId 父部门ID
   * @param deptId 部门ID
   */
  async function handleOpenDialog(parentId?: number, deptId?: number) {
    // 加载部门下拉数据
    const data = await DeptAPI.getOptions();
    deptOptions.value = [
      {
        value: 0,
        label: "顶级部门",
        children: data,
      },
    ];

    dialog.visible = true;
    if (deptId) {
      dialog.title = "修改部门";
      DeptAPI.getFormData(deptId).then((data: any) => {
        Object.assign(formData, data);
      });
    } else {
      dialog.title = "新增部门";
      formData.parentId = parentId || 0;
    }
  }

  // 提交部门表单
  function handleSubmit() {
    deptFormRef.value.validate((valid: any) => {
      if (valid) {
        loading.value = true;
        const deptId = formData.id;
        if (deptId) {
          DeptAPI.update(deptId, formData)
            .then(() => {
              ElMessage.success("修改成功");
              handleCloseDialog();
              handleQuery();
            })
            .finally(() => (loading.value = false));
        } else {
          DeptAPI.add(formData)
            .then(() => {
              ElMessage.success("新增成功");
              handleCloseDialog();
              handleQuery();
            })
            .finally(() => (loading.value = false));
        }
      }
    });
  }

  // 删除部门
  function handleDelete(deptId?: number) {
    const deptIds = [deptId || selectIds.value].join(",");

    if (!deptIds) {
      ElMessage.warning("请勾选删除项");
      return;
    }

    ElMessageBox.confirm("确认删除已选中的数据项?", "警告", {
      confirmButtonText: "确定",
      cancelButtonText: "取消",
      type: "warning",
    }).then(
      () => {
        loading.value = true;
        DeptAPI.deleteByIds(deptIds)
          .then(() => {
            ElMessage.success("删除成功");
            handleResetQuery();
          })
          .finally(() => (loading.value = false));
      },
      () => {
        ElMessage.info("已取消删除");
      }
    );
  }

  // 重置表单
  function resetForm() {
    deptFormRef.value.resetFields();
    deptFormRef.value.clearValidate();

    formData.id = undefined;
    formData.parentId = 0;
    formData.status = 1;
    formData.sort = 1;
  }

  // 关闭弹窗
  function handleCloseDialog() {
    dialog.visible = false;
    resetForm();
  }

  onMounted(() => {
    handleQuery();
  });
</script>
