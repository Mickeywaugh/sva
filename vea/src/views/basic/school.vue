<template>
  <div class="app-container">
    <el-breadcrumb class="flex-y-center m-2" :separator-icon="ArrowRight">
      <el-breadcrumb-item>
        <el-link @click="switchNode('academy')">学校分页</el-link>
      </el-breadcrumb-item>
      <el-breadcrumb-item>
        <el-link @click="switchNode('campus', vueData.academy)" v-if="vueData.academy?.id">
          {{ vueData.academy.name }}
        </el-link>
      </el-breadcrumb-item>
    </el-breadcrumb>


    <el-card v-if="vueData.currentNode == 'academy'" shadow="never" class="table-card">
      <div class="toolbar" style="padding: 7px">
        <div class="left-toolbar">
          <el-button-group>
            <el-button type="primary" v-icon="'ga-o-plus'" @click="openDialog('academy')">新增</el-button>
          </el-button-group>
        </div>
        <div class="right-toolbar">
          <el-form :inline="true">
            <el-form-item label="关键字">
              <el-input v-model="vueData.tableData.academy.params.keyword" placeholder="请输入关键字" />
            </el-form-item>
            <el-form-item>
              <el-button type="primary" v-icon="'ga-search'" @click="handleQuery('academy')">查询</el-button>
            </el-form-item>
          </el-form>
        </div>
      </div>
      <el-table v-loading="loading" highlight-current-row :data="vueData.tableData.academy.list" :fit="true" :stripe="true"
        :header-cell-style="{ textAlign: 'center' }">
        <el-table-column type="index" label="ID" width="72" align="center" />
        <el-table-column label="编码" prop="code" />
        <el-table-column label="名称" prop="name" />
        <el-table-column label="办学水平" prop="level" width="100" />
        <el-table-column label="主管部门" prop="setupDept" />
        <el-table-column label="类型" prop="type" />
        <el-table-column label="地图位置数据" prop="geoRawData">
          <template #default="scope">
            <el-popover :width="800" trigger="click">
              <template #reference>
                <el-button type="primary" v-icon="'ga-o-view'" size="small">查看</el-button>
              </template>
              <div>
                {{ scope.row.geoRawData }}
              </div>
            </el-popover>
          </template>
        </el-table-column>
        <el-table-column label="地图数据状态" prop="geoStatus">
          <template v-slot="{ row }">
            <StatusTag v-model="row.geoStatus" :map="BooleanStatusMap"></statustag>
          </template>
        </el-table-column>
        <el-table-column label="状态" prop="status" width="90">
          <template v-slot="{ row }">
            <el-switch v-model="row.status" :before-change="() => confirmChange('academy', row as Academy, { status: Number(!row.status) })"
              :active-value="1" :inactive-value="0" />
          </template>
        </el-table-column>
        <el-table-column label="操作">
          <template v-slot="{ row }">
            <el-button-group>
              <el-button type="primary" v-icon="'ga-o-edit'" @click="openDialog('academy', row as Academy)">编辑</el-button>
              <el-button type="primary" v-if="row.campusCount > 0" v-icon="'ga-academy'"
                @click="switchNode('campus', row as Academy)">校区</el-button>
            </el-button-group>
          </template>
        </el-table-column>
      </el-table>
      <pagination v-if="vueData.tableData.academy.total > 0" v-model:total="vueData.tableData.academy.total"
        v-model:page="vueData.tableData.academy.params.pageNum" v-model:limit="vueData.tableData.academy.params.pageSize"
        @pagination="handleQuery('academy')" />
      <el-drawer v-model="vueData.formDialog.academy.visible" :title="vueData.formDialog.academy.title" width="500px"
        @close="closeDialog('academy')" :close-on-click-modal="false" draggable destroy-on-close>
        <el-form ref="academyFormRef" :model="vueData.formDialog.academy.formData" :rules="academyFormRules" label-width="auto">
          <el-form-item label="代码" prop="code">
            <el-input :disabled="vueData.formDialog.academy.formData.id > 0" v-model="vueData.formDialog.academy.formData.code" />
          </el-form-item>
          <el-form-item label="名称" prop="name">
            <el-input :disabled="vueData.formDialog.academy.formData.id > 0" v-model="vueData.formDialog.academy.formData.name" />
          </el-form-item>
          <el-form-item label="办学层次" prop="level">
            <el-radio-group v-model="vueData.formDialog.academy.formData.level" fill="#12B7D8">
              <el-radio-button label="本科" value="本科" />
              <el-radio-button label="专科" value="专科" />
            </el-radio-group>
          </el-form-item>
          <el-form-item label="主管部门" prop="setupDept">
            <el-input v-model="vueData.formDialog.academy.formData.setupDept" />
          </el-form-item>
          <el-form-item label="学校类型" prop="type">
            <el-radio-group v-model="vueData.formDialog.academy.formData.type">
              <el-radio-button label="公办" value="公办" />
              <el-radio-button label="民办" value="民办" />
              <el-radio-button label="中外合作办学" value="中外合作办学" />
            </el-radio-group>
          </el-form-item>
          <el-form-item label="GEO原始数据" prop="geoRawData">
            <el-input type="textarea" :rows="8" v-model="vueData.formDialog.academy.formData.geoRawData" />
          </el-form-item>
          <el-form-item label="GEO数据" prop="geoData">
            <el-input type="textarea" :rows="6" v-model="vueData.formDialog.academy.formData.geoData" />
          </el-form-item>
          <el-form-item label="GEO数据状态" prop="geoStatus">
            <el-switch v-model="vueData.formDialog.academy.formData.geoStatus" :active-value="1" :inactive-value="0" />
          </el-form-item>
          <el-form-item label="状态" prop="status">
            <el-switch v-model="vueData.formDialog.academy.formData.status" :active-value="1" :inactive-value="0" />
          </el-form-item>
        </el-form>
        <template #footer>
          <div class="dialog-footer">
            <el-button type="primary" @click="handleSubmit('academy')">提交</el-button>
            <el-button @click="closeDialog()">取消</el-button>
          </div>
        </template>
      </el-drawer>
    </el-card>

    <el-card v-if="vueData.currentNode == 'campus'" shadow="never" class="table-card">
      <div class="toolbar" style="padding: 7px">
        <div class="left-toolbar">
          <el-button-group>
            <el-button type="primary" v-icon="'ga-o-plus'" @click="openDialog('campus')">新增</el-button>
          </el-button-group>
        </div>
        <div class="right-toolbar">
        </div>
      </div>
      <el-table v-loading="loading" highlight-current-row :data="vueData.tableData.campus.list" :fit="true" border
        :header-cell-style="{ textAlign: 'center' }">
        <el-table-column type="index" label="ID" width="72" align="center" />
        <el-table-column label="编码" prop="academyId" width="100" />
        <el-table-column label="名称" prop="name" />
        <el-table-column label="邮编" prop="districtCode" width="100" />
        <el-table-column label="地址" prop="address" />
        <el-table-column label="电话" prop="tel" />
        <el-table-column label="lat" prop="lat" width="150" />
        <el-table-column label="lng" prop="lng" width="150" />
        <el-table-column label="状态" prop="status" width="90">
          <template v-slot="{ row }">
            <el-switch v-model="row.status" :before-change="() => confirmChange('campus', row as Campus, { status: Number(!row.status) })"
              :active-value="1" :inactive-value="0" />
          </template>
        </el-table-column>
        <el-table-column label="操作">
          <template v-slot="{ row }">
            <el-button-group>
              <el-button type="primary" v-icon="'ga-o-edit'" @click="openDialog('campus', row as Campus)">编辑</el-button>
            </el-button-group>
          </template>
        </el-table-column>
      </el-table>
      <pagination v-if="vueData.tableData.campus.total > 0" v-model:total="vueData.tableData.campus.total"
        v-model:page="vueData.tableData.campus.params.pageNum" v-model:limit="vueData.tableData.campus.params.pageSize"
        @pagination="handleQuery('campus')" />

      <el-drawer v-model="vueData.formDialog.campus.visible" :title="vueData.formDialog.campus.title" width="500px"
        @close="closeDialog('campus')" :close-on-click-modal="false" draggable destroy-on-close>
        <el-form ref="campusFormRef" :model="vueData.formDialog.campus.formData" :rules="campusFormRules" label-width="auto">
          <el-form-item label="名称" prop="name">
            <el-input v-model="vueData.formDialog.campus.formData.name" />
          </el-form-item>
          <el-form-item label="邮政编码" prop="districtCode">
            <el-input v-model="vueData.formDialog.campus.formData.districtCode" />
          </el-form-item>
          <el-form-item label="地址" prop="address">
            <el-input v-model="vueData.formDialog.campus.formData.address" />
          </el-form-item>
          <el-form-item label="电话" prop="tel">
            <el-input v-model="vueData.formDialog.campus.formData.tel" />
          </el-form-item>
          <el-form-item label="经度" prop="lng">
            <el-input v-model="vueData.formDialog.campus.formData.lng" />
          </el-form-item>
          <el-form-item label="纬度" prop="lat">
            <el-input v-model="vueData.formDialog.campus.formData.lat" />
          </el-form-item>
          <el-form-item label="状态" prop="status">
            <el-switch v-model="vueData.formDialog.campus.formData.status" :active-value="1" :inactive-value="0" />
          </el-form-item>
        </el-form>
        <template #footer>
          <div class="dialog-footer">
            <el-button type="primary" @click="handleSubmit('campus')">提交</el-button>
            <el-button @click="closeDialog()">取消</el-button>
          </div>
        </template>
      </el-drawer>
    </el-card>

  </div>
</template>

<script setup lang="ts">
  import { Academy, BasicSchoolAPI, Campus, SchoolNode } from "@/api/basic/school";
  import { BooleanStatusMap } from "@/enums/app";
  import StatusTag from "@/components/StatusTag/index.vue";
  import { ArrowRight } from "@element-plus/icons-vue";

  defineOptions({
    name: "BasicSchoolAdmin",
  });

  const loading = ref(false);
  const academyFormRef = ref();
  const campusFormRef = ref();

  const vueData = reactive({
    currentNode: "academy" as SchoolNode,
    academy: { id: 0 } as Academy,
    campus: { id: 0 } as Campus,
    tableData: {
      academy: {
        list: [] as Academy[],
        total: 0,
        params: {
          pageNum: 1,
          pageSize: 25,
          keyword: ""
        },
      } as PageResult<Academy>,
      campus: {
        list: [] as Campus[],
        total: 0,
        params: {
          pageNum: 1,
          pageSize: 25,
          academyId: 0,
          keyword: ""
        },
      } as PageResult<Campus>
    },
    formDialog: {
      academy: {
        visible: false,
        title: "",
        formData: { id: 0 } as Academy
      },
      campus: {
        visible: false,
        title: "",
        formData: { id: 0 } as Campus
      }
    },
  });

  const academyFormRules = {
    code: [{ required: true, message: '代码不能为空', trigger: "blur" }],
    name: [{ required: true, message: '名称不能为空', trigger: "blur" }],
  }


  const campusFormRules = {
    name: [{ required: true, message: '名称不能为空', trigger: "blur" }],
    address: [{ required: true, message: '地址不能为空', trigger: "blur" }],
    lng: [{ required: true, message: '经度不能为空', trigger: "blur" }],
    lat: [{ required: true, message: '纬度不能为空', trigger: "blur" }],
  }

  const switchNode = (node: SchoolNode, academy?: Academy) => {
    vueData.currentNode = node;
    if (node === "campus" && academy) {
      vueData.academy = academy;
      vueData.tableData.campus.params.academyId = academy.id;
    } else {
      vueData.academy = { id: 0 } as Academy;
      vueData.tableData.campus.params.academyId = 0;
    }
    handleQuery(node);
  }
  const handleQuery = async (node: SchoolNode) => {
    loading.value = true;
    try {
      const data = await BasicSchoolAPI.page(node, vueData.tableData[node].params);
      vueData.tableData[node].list.splice(0);
      Object.assign(vueData.tableData[node], data);
    } catch (error: any) {
      ElMessage.error(error.message);
    } finally {
      loading.value = false;
    }
  };

  const openDialog = (node: SchoolNode, row?: Academy | Campus) => {
    const nodeName = node === "academy" ? "院校" : "校区";
    const operation = row ? "编辑" : "添加";
    vueData.formDialog[node].title = `${operation}${nodeName}`;
    vueData.formDialog[node].visible = true;
    if (row) {
      const { createTime, updateTime, createBy, updateBy, ...formData } = row as any;
      Object.assign(vueData.formDialog[node].formData, formData);
    } else {
      if (node === "campus") {
        Object.assign(vueData.formDialog.campus.formData, {
          academyId: vueData.academy.id,
          id: 0,
          name: "",
          districtCode: "",
          address: "",
          tel: "",
          lng: "",
          lat: "",
          status: 1,
        });
      } else {
        Object.assign(vueData.formDialog.academy.formData, {
          id: 0,
          code: "",
          name: "",
          level: "",
          setupDept: "",
          type: "",
          geoRawData: "",
          geoData: "",
          geoStatus: 0,
          status: 1,
        } as Academy);
      }
    }
  }

  const closeDialog = (node?: SchoolNode) => {
    if (node) {
      vueData.formDialog[node].visible = false;
    } else {
      vueData.formDialog.academy.visible = false;
      vueData.formDialog.campus.visible = false;
    }
  }

  const handleSubmit = async (node: SchoolNode) => {
    const formRef = node === "academy" ? academyFormRef : campusFormRef;
    try {
      await formRef.value.validate();
      loading.value = true;
      await BasicSchoolAPI.set(node, vueData.formDialog[node].formData.id ?? 0, vueData.formDialog[node].formData);
      ElMessage.success("保存成功");
      handleQuery("academy");
      closeDialog(node);
    } catch (error: any) {
      if (error !== "cancel") {
        ElMessage.error(error.message);
      }
    } finally {
      loading.value = false;
    }
  };


  /**
   * switch 切换前确认，返回 true 允许切换，返回 false 阻止切换
   */
  const confirmChange = async (node: SchoolNode, row: Academy | Campus, data: any): Promise<boolean> => {
    if (!row.id) return false;
    return ElMessageBox.confirm("确定更改吗?", "提示", {
      confirmButtonText: "确定",
      cancelButtonText: "取消",
      type: "warning",
    }).then(async () => {
      loading.value = true;
      return BasicSchoolAPI.set(node, row.id, data)
        .then((response) => {
          Object.assign(row, response.data);
          ElMessage.success("修改成功");
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

  onMounted(() => {
    handleQuery("academy");
  });
</script>