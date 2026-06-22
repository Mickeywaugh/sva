<template>
  <div class="app-container">
    <el-breadcrumb class="flex-y-center m-2" :separator-icon="ArrowRight">
      <el-breadcrumb-item>
        <el-link @click="switchNode('dict')">字典列表</el-link>
      </el-breadcrumb-item>
      <el-breadcrumb-item>
        <el-link @click="switchNode('item', vueData.dict)" v-if="vueData.dict.id">
          {{ vueData.dict.dictCode }}
        </el-link>
      </el-breadcrumb-item>
    </el-breadcrumb>
    <el-card v-if="vueData.currentNode == 'dict'" shadow="never" class="table-card">
      <div class="toolbar">
        <div class="left-toolbar">
          <el-button-group>
            <el-button type="primary" v-icon="'vea-o-plus'" @click="openDialog('dict')">{{ $t('common.create') }}</el-button>
          </el-button-group>
        </div>
        <div class="right-toolbar">
          <el-form :inline="true">
            <el-form-item label="关键字" prop="keyword">
              <el-input v-model="vueData.tableData.dict.params.keyword" />
            </el-form-item>
            <el-form-item>
              <el-button-group>
                <el-button type="primary" v-icon="'vea-search'" vea-icon="" @click="handleQuery('dict')">{{ $t('common.search') }}</el-button>
              </el-button-group>
            </el-form-item>
          </el-form>
        </div>
      </div>
      <el-table v-loading="loading" highlight-current-row :data="vueData.tableData.dict.list" :fit="true" :stripe="true"
        :header-cell-style="{ textAlign: 'center' }" @selection-change="handleSelectionChange">
        <el-table-column type="selection" width="55" align="center" />
        <el-table-column label="字典名称" prop="name" />
        <el-table-column label="字典编码" prop="dictCode" />
        <el-table-column label="状态" prop="status" width="90">
          <template #default="{ row }">
            <el-switch v-model="row.status" :before-change="() => confirmChange('dict', row, { status: Number(!row.status) })" :active-value="1"
              :inactive-value="0" />
          </template>
        </el-table-column>
        <el-table-column label="操作" align="center">
          <template #default="scope">
            <el-button-group>
              <el-button type="primary" v-icon="'vea-o-edit'" size="small" @click="openDialog('dict', scope.row)">{{ $t('common.edit') }}</el-button>
              <el-button type="primary" v-icon="'vea-detail'" size="small" @click="switchNode('item', scope.row)">{{ $t('common.data') }} </el-button>
            </el-button-group>
          </template>
        </el-table-column>
      </el-table>
      <pagination v-if="vueData.tableData.dict.total > 0" v-model:total="vueData.tableData.dict.total"
        v-model:page="vueData.tableData.dict.params.pageNum" v-model:limit="vueData.tableData.dict.params.pageSize"
        @pagination="handleQuery('dict')" />
      <el-drawer v-model="vueData.formDialog.dict.visible" :title="vueData.formDialog.dict.title" width="500px" @close="closeDialog()"
        :close-on-click-modal="false" draggable destroy-on-close>
        <el-form ref="dictFormRef" :model="vueData.formDialog.dict.formData" :rules="dictFormRules" label-width="auto">
          <el-form-item label="字典名称" prop="name">
            <el-input v-model="vueData.formDialog.dict.formData.name" placeholder="请输入字典名称" />
          </el-form-item>
          <el-form-item label="字典编码" prop="dictCode">
            <el-input v-model="vueData.formDialog.dict.formData.dictCode" placeholder="请输入字典编码" />
          </el-form-item>
          <el-form-item label="数值" prop="isNumber">
            <el-switch v-model="vueData.formDialog.dict.formData.isNumber" :active-value="1" :inactive-value="0" inline-prompt active-text="Y"
              inactive-text="N" />
          </el-form-item>
          <el-form-item label="状态">
            <el-switch v-model="vueData.formDialog.dict.formData.status" :active-value="1" :inactive-value="0" inline-prompt active-text="Y"
              inactive-text="N" />
          </el-form-item>
          <el-form-item label="备注">
            <el-input v-model="vueData.formDialog.dict.formData.remark" type="textarea" placeholder="请输入备注" />
          </el-form-item>
        </el-form>
        <template #footer>
          <div class="dialog-footer">
            <el-button type="primary" @click="handleSubmit('dict')">{{ $t('common.submit') }}</el-button>
            <el-button @click="closeDialog()">{{ $t('common.cancel') }}</el-button>
          </div>
        </template>
      </el-drawer>
    </el-card>

    <!-- 字典数据节点 -->
    <el-card v-if="vueData.currentNode == 'item'" shadow="never" class="table-card">
      <div class="toolbar">
        <div class="left-toolbar">
          <el-button-group>
            <el-button type="primary" v-icon="'vea-o-plus'" @click="openDialog('item')">{{ $t('common.create') }}</el-button>
          </el-button-group>
        </div>
        <div class="right-toolbar">
        </div>
      </div>
      <el-table v-loading="loading" highlight-current-row :data="vueData.tableData.item.list" :fit="true" :stripe="true"
        :header-cell-style="{ textAlign: 'center' }" @selection-change="handleSelectionChange">
        <el-table-column type="selection" width="55" align="center" />
        <el-table-column type="index" label="ID" width="72" align="center" />
        <el-table-column label="字典项标签" prop="label" />
        <el-table-column label="字典项值" prop="value" />
        <el-table-column label="排序" prop="sort" />
        <el-table-column label="默认" prop="isDefault" width="90">
          <template #default="{ row }">
            <el-switch v-model="row.isDefault" :before-change="() => confirmChange('item', row, { isDefault: Number(!row.isDefault) })"
              :active-value="1" :inactive-value="0" />
          </template>
        </el-table-column>
        <el-table-column label="状态" prop="status" width="90">
          <template #default="{ row }">
            <el-switch v-model="row.status" :before-change="() => confirmChange('item', row, { status: Number(!row.status) })" :active-value="1"
              :inactive-value="0" />
          </template>
        </el-table-column>
        <el-table-column label="操作" align="center">
          <template #default="scope">
            <el-button-group>
              <el-button type="primary" v-icon="'vea-o-edit'" size="small" @click="openDialog('item', scope.row)">{{ $t('common.edit') }}</el-button>
              <el-button type="primary" v-icon="'vea-o-delete'" size="small" @click="handleDelete('item', scope.row)">{{ $t('common.delete') }}</el-button>
            </el-button-group>
          </template>
        </el-table-column>
      </el-table>
      <pagination v-if="vueData.tableData.item.total > 0" v-model:total="vueData.tableData.item.total"
        v-model:page="vueData.tableData.item.params.pageNum" v-model:limit="vueData.tableData.item.params.pageSize"
        @pagination="handleQuery('item')" />

      <el-drawer v-model="vueData.formDialog.item.visible" :title="vueData.formDialog.item.title" width="500px" @close="closeDialog()"
        :close-on-click-modal="false" draggable destroy-on-close>
        <el-form ref="itemFormRef" :model="vueData.formDialog.item.formData" :rules="itemFormRules" label-width="auto">
          <el-form-item label="数据标签" prop="label">
            <el-input v-model="vueData.formDialog.item.formData.label" placeholder="请输入数据标签" />
          </el-form-item>
          <el-form-item label="数据值" prop="value">
            <el-input v-model="vueData.formDialog.item.formData.value" placeholder="请输入数据值" />
          </el-form-item>
          <el-form-item label="状态">
            <el-switch v-model="vueData.formDialog.item.formData.status" :active-value="1" :inactive-value="0" inline-prompt active-text="Y"
              inactive-text="N" />
          </el-form-item>
          <el-form-item label="默认值">
            <el-switch v-model="vueData.formDialog.item.formData.isDefault" :active-value="1" :inactive-value="0" inline-prompt active-text="Y"
              inactive-text="N" />
          </el-form-item>
          <el-form-item label="排序">
            <el-input-number v-model="vueData.formDialog.item.formData.sort" controls-position="right" />
          </el-form-item>
          <el-form-item>
            <template #label>
              <div class="flex-y-center">
                标签类型
                <el-tooltip>
                  <template #content>回显样式，为空时则显示 '文本'</template>
                  <el-icon class="ml-1 cursor-pointer">
                    <QuestionFilled />
                  </el-icon>
                </el-tooltip>
              </div>
            </template>
            <el-select v-model="vueData.formDialog.item.formData.tagType" placeholder="请选择标签类型" clearable @clear="vueData.item.tagType = ''">
              <template #label="{ value }">
                <el-tag v-if="value" :type="value">
                  {{ vueData.formDialog.item.formData.label ? vueData.formDialog.item.formData.label : "字典标签" }}
                </el-tag>
              </template>
              <!-- <el-option label="默认文本" value="" /> -->
              <el-option v-for="type in tagType" :key="type" :label="type" :value="type as string">
                <div flex-y-center gap-10px>
                  <el-tag :type="type">{{ vueData.formDialog.item.formData.label ?? "字典标签" }}</el-tag>
                  <span>{{ type }}</span>
                </div>
              </el-option>
            </el-select>
          </el-form-item>
        </el-form>
        <template #footer>
          <div class="dialog-footer">
            <el-button type="primary" @click="handleSubmit('item')">提交</el-button>
            <el-button @click="closeDialog()">取消</el-button>
          </div>
        </template>
      </el-drawer>
    </el-card>

  </div>
</template>

<script setup lang="ts">

  import { ArrowRight } from "@element-plus/icons-vue";
  import DictAPI, { Dict, DictForm, DictItem, DictItemForm, DictNode } from "@/api/system/dict";
  import { TagProps } from "element-plus";
  const t = useI18n().t;

  defineOptions({
    name: "SystemDict",
    inherititems: false,
  });

  const tagType: TagProps["type"][] = ["primary", "success", "info", "warning", "danger"];

  const loading = ref(false);
  const dictFormRef = ref();
  const itemFormRef = ref();
  const selectIds = ref([]);

  const vueData = reactive({
    currentNode: "dict" as DictNode,
    dict: { id: undefined } as DictForm,
    item: { id: undefined } as DictItemForm,
    tableData: {
      dict: {
        list: [],
        total: 0,
        params: {
          pageNum: 1,
          pageSize: 25,
          keyword: ""
        },
      } as PageResult<DictForm>,
      item: {
        list: [] as DictItemForm[],
        total: 0,
        params: {
          pageNum: 1,
          pageSize: 25,
          dictId: 0,
          keyword: ""
        },
      } as PageResult<DictItemForm>
    },
    formDialog: {
      dict: {
        visible: false,
        title: "添加字典",
        formData: { id: 0 } as DictForm
      },
      item: {
        visible: false,
        title: "添加数据",
        formData: { id: 0 } as DictItemForm
      }
    },
  });

  const dictFormRules = {
    code: [{ required: true, message: '代码不能为空', trigger: "blur" }],
    name: [{ required: true, message: '名称不能为空', trigger: "blur" }],
    dictId: [{ required: true, message: '学校ID不能为空', trigger: "blur" }],
    districtCode: [{ required: true, message: '邮编不能为空', trigger: "blur" }],
  }

  const itemFormRules = {
    name: [{ required: true, message: '名称不能为空', trigger: "blur" }],
    id: [{ required: true, message: 'ID不能为空', trigger: "blur" }],
    address: [{ required: true, message: '地址不能为空', trigger: "blur" }],
    lng: [{ required: true, message: '经度不能为空', trigger: "blur" }],
    lat: [{ required: true, message: '纬度不能为空', trigger: "blur" }],
  }

  function handleSelectionChange(selection: any) {
    selectIds.value = selection.map((item: any) => item.id);
  }

  const switchNode = (node: DictNode, dict?: Dict) => {
    vueData.currentNode = node;
    selectIds.value = [];
    if (node == "item" && dict) {
      Object.assign(vueData.dict, dict);
      if (dict.id) vueData.tableData.item.params.dictId = dict.id;
    } else {
      Object.assign(vueData.dict, { id: undefined });
    }
    handleQuery(node);
  }
  const handleQuery = async (node: DictNode) => {
    loading.value = true;
    await DictAPI.page(node, vueData.tableData[node].params)
      .then((data: any) => {
        vueData.tableData[node].list.splice(0);
        Object.assign(vueData.tableData[node], data);
      }).catch((error: any) => {
        ElMessage.error(error.message);
      })
      .finally(() => {
        loading.value = false;
      });
  };

  const openDialog = (node: DictNode, row?: Dict | DictItem) => {
    let operation = row ? "编辑" : "添加";
    let nodeName = node == "dict" ? "字典" : `${vueData.dict.name}(${vueData.dict.dictCode})数据`;
    vueData.formDialog[node].title = operation + nodeName;
    vueData.formDialog[node].visible = true;
    if (row) {
      // 去掉row中的updateTime和createTime字段
      const { createTime, updateTime, createBy, updateBy, ...formData } = row;
      Object.assign(vueData.formDialog[node].formData, formData);
    } else {
      if (node == "item") {
        Object.assign(vueData.formDialog.item.formData, {
          dictId: vueData.dict.id,
          id: 0,
          value: "",
          label: "",
          status: 1,
          sort: 0,
          isDefault: 0,
          tagType: "",
        } as DictItemForm);
      } else {
        Object.assign(vueData.formDialog.dict.formData, {
          id: 0,
          name: "",
          dictCode: "",
          status: 1,
          isNumber: 0,
          remark: "",
        } as DictForm);
      }
    }
  }
  const closeDialog = () => {
    vueData.formDialog.item.visible = false;
    vueData.formDialog.dict.visible = false;
  }

  const handleSubmit = async (node: DictNode) => {
    let formRef = node == "dict" ? dictFormRef : itemFormRef;
    formRef.value.validate().then(() => {
      loading.value = true;
      DictAPI.set(node, vueData.formDialog[node].formData.id ?? 0, vueData.formDialog[node].formData)
        .then(() => {
          ElMessage.success("保存成功");
          handleQuery(node);
          closeDialog();
        })
        .catch((error: any) => {
          ElMessage.error(error.message);
        }).finally(() => {
          loading.value = false;
        });
    })
  };


  /**
   * switch 切换前确认，返回 true 允许切换，返回 false 阻止切换
   */
  const confirmChange = async (node: DictNode, row: Dict | DictItem, data: Record<string, any>): Promise<boolean> => {
    if (!row.id) return false;
    return ElMessageBox.confirm(`确定更改吗?`, "提示", {
      confirmButtonText: "确定",
      cancelButtonText: "取消",
      type: "warning",
    }).then(async () => {
      loading.value = true;
      return DictAPI.set(node, row.id ?? 0, data)
        .then((data: any) => {
          Object.assign(row, data.data);
          ElMessage.success("修改成功");
          return true;
        })
        .catch((error: any) => {
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


  const handleDelete = (node: DictNode, row: Dict | DictItem) => {
    ElMessageBox.confirm(`确定删除吗?`, "提示", {
      confirmButtonText: "确定",
      cancelButtonText: "取消",
      type: "warning",
    }).then(async () => {
      loading.value = true;
      await DictAPI.delete(node, row.id ?? 0)
        .then(() => {
          ElMessage.success("删除成功");
          handleQuery(node);
        })
        .catch((error: any) => {
          ElMessage.error(error.message);
        })
        .finally(() => {
          loading.value = false;
        });
    })
  }

  const batchDelete = () => {
    if (selectIds.value.length == 0) {
      ElMessageBox.alert("请选择要删除的行");
      return;
    }
    ElMessageBox.confirm(`确定批量删除吗?`, "提示", {
      confirmButtonText: "确定",
      cancelButtonText: "取消",
      type: "warning",
    }).then(async () => {
      loading.value = true;
      await DictAPI.deleteByIds(vueData.currentNode, selectIds.value.join(","))
        .then(() => {
          ElMessage.success("删除成功");
          handleQuery(vueData.currentNode);
        }).catch((error: any) => {
          ElMessage.error(error.message);
        }).finally(() => {
          loading.value = false;
        });
    })
  }

  onMounted(() => {
    handleQuery("dict");
  });
</script>