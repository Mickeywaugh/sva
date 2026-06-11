<template>
  <div class="app-container">
    <el-breadcrumb class="flex-y-center m-2" :separator-icon="ArrowRight">
      <el-breadcrumb-item>
        <el-link @click="switchRegion('province')">全国</el-link>
      </el-breadcrumb-item>
      <el-breadcrumb-item>
        <el-link @click="switchRegion('city', vueData.province)" v-if="vueData.province?.id">
          {{ vueData.province.fullName }}
        </el-link>
      </el-breadcrumb-item>
      <el-breadcrumb-item v-if="vueData.city?.id">
        <el-link @click="switchRegion('district', vueData.city)">{{ vueData.city.fullName }}</el-link>
      </el-breadcrumb-item>
    </el-breadcrumb>
    <!-- 省列表 -->
    <el-card v-if="vueData.currentNode == 'province'" shadow="never" class="table-card">
      <div class="toolbar" style="padding: 7px">
        <div class="left-toolbar"></div>
        <div class="right-toolbar">

        </div>
      </div>
      <el-table v-loading="loading" highlight-current-row :data="tableData.province.list" :stripe="true"
        :header-cell-style="{ textAlign: 'center' }">
        <el-table-column type="index" label="ID" width="72" align="center" />
        <el-table-column label="编码" prop="id" />
        <el-table-column label="名称" prop="name" />
        <el-table-column label="全称" prop="fullName" />
        <el-table-column label="拼音" prop="pinyin" />
        <el-table-column label="lat" prop="lat" />
        <el-table-column label="lng" prop="lng" />
        <el-table-column label="状态" prop="status">
          <template #default="{ row }">
            <el-switch v-model="row.status"
              :before-change="() => handleSwitch('province', row as Region, { status: Number(!row.status) })"
              :active-value="1" :inactive-value="0" />
          </template>
        </el-table-column>
        <el-table-column fixed="right" label="操作">
          <template #default="{ row }">
            <el-button-group>
              <el-button type="success" v-if="row.subCount > 0" v-icon="'ga-branches'"
                @click="switchRegion('city', row as Region)">下属城市</el-button>
            </el-button-group>
          </template>
        </el-table-column>
      </el-table>
      <pagination v-if="tableData.province.total > 0" v-model:total="tableData.province.total"
        v-model:page="tableData.province.params.pageNum" v-model:limit="tableData.province.params.pageSize"
        @pagination="getRegions('province')" />
    </el-card>

    <!-- 市列表 -->
    <el-card v-if="vueData.currentNode == 'city'" shadow="never" class="table-card">
      <div class="toolbar" style="padding: 7px">
        <div class="left-toolbar"></div>
        <div class="right-toolbar">

        </div>
      </div>
      <el-table v-loading="loading" highlight-current-row :data="tableData.city.list" :stripe="true"
        :header-cell-style="{ textAlign: 'center' }">
        <el-table-column type="index" label="ID" width="72" align="center" />
        <el-table-column label="编码" prop="id" />
        <el-table-column label="名称" prop="name" />
        <el-table-column label="全称" prop="fullName" />
        <el-table-column label="拼音" prop="pinyin" />
        <el-table-column label="lat" prop="lat" />
        <el-table-column label="lng" prop="lng" />
        <el-table-column label="状态" prop="status">
          <template #default="{ row }">
            <el-switch v-model="row.status"
              :before-change="() => handleSwitch('city', row as Region, { status: Number(!row.status) })"
              :active-value="1" :inactive-value="0" />
          </template>
        </el-table-column>
        <el-table-column fixed="right" label="操作">
          <template #default="{ row }">
            <el-button-group>
              <el-button type="success" v-if="row.subCount > 0" v-icon="'ga-branches'"
                @click="switchRegion('district', row as Region)">下属区县</el-button>
            </el-button-group>
          </template>
        </el-table-column>
      </el-table>
      <pagination v-if="tableData.city.total > 0" v-model:total="tableData.city.total" v-model:page="tableData.city.params.pageNum"
        v-model:limit="tableData.city.params.pageSize" @pagination="getRegions('city')" />
    </el-card>

    <!-- 区列表 -->
    <el-card v-if="vueData.currentNode == 'district'" shadow="never" class="table-card">
      <div class="toolbar" style="padding: 7px">
        <div class="left-toolbar"></div>
        <div class="right-toolbar"></div>
      </div>
      <el-table v-loading="loading" highlight-current-row :data="tableData.district.list" :stripe="true" border
        :header-cell-style="{ textAlign: 'center' }">
        <el-table-column type="index" label="ID" width="72" align="center" />
        <el-table-column label="编码" prop="id" />
        <el-table-column label="名称" prop="name" />
        <el-table-column label="全称" prop="fullName" />
        <el-table-column label="拼音" prop="pinyin" />
        <el-table-column label="lat" prop="lat" />
        <el-table-column label="lng" prop="lng" />
        <el-table-column label="状态" prop="status" width="90">
          <template #default="{ row }">
            <el-switch v-model="row.status"
              :before-change="() => handleSwitch('district', row as Region, { status: Number(!row.status) })"
              :active-value="1" :inactive-value="0" />
          </template>
        </el-table-column>
      </el-table>
      <pagination v-if="tableData.district.total > 0" v-model:total="tableData.district.total"
        v-model:page="tableData.district.params.pageNum" v-model:limit="tableData.district.params.pageSize"
        @pagination="getRegions('district')" />
    </el-card>
  </div>
</template>

<script setup lang="ts">
  import { BasicRegionAPI, Region, RegionNode } from "@/api/basic/region";
  import { ArrowRight } from "@element-plus/icons-vue";

  defineOptions({
    name: "BasicRegionAdmin",
  });

  const t = useI18n().t;
  const loading = ref(false);

  const vueData = reactive({
    currentNode: "province",
    province: { id: undefined } as Region,
    city: { id: undefined } as Region,
    district: { id: undefined } as Region,
  });

  // 分页数据
  const tableData = reactive({
    province: {
      total: 0,
      list: [],
      params: {
        pageNum: 1,
        pageSize: 25,
        pId: undefined,
      },
    } as PageResult<Region>,
    city: {
      total: 0,
      list: [],
      params: {
        pageNum: 1,
        pageSize: 25,
        pId: vueData.province.id,
      },
    } as PageResult<Region>,
    district: {
      total: 0,
      list: [],
      params: {
        pageNum: 1,
        pageSize: 25,
        pId: vueData.city.id,
      },
    } as PageResult<Region>,
  });

  const getRegions = async (level: RegionNode) => {
    loading.value = true;
    try {
      const data = await BasicRegionAPI.page(level, tableData[level].params);
      tableData[level].list.splice(0);
      Object.assign(tableData[level], data);
    } catch (error: any) {
      ElMessage.error(error.message);
    } finally {
      loading.value = false;
    }
  };

  const switchRegion = (level: RegionNode, row?: Region) => {
    vueData.currentNode = level;
    if (level === "city") {
      vueData.province = row as Region;
      vueData.city = {} as Region;
    }
    if (level === "district") {
      vueData.city = row as Region;
    }
    if (level === "province") {
      vueData.province = {} as Region;
      vueData.city = {} as Region;
    }
    tableData[level].params.pId = row ? row.id : undefined;
    tableData[level].params.pageNum = 1;
    getRegions(level);
  };

  const handleSwitch = async (level: RegionNode, row: Region, data: any) => {

    if (!row.id) return false;
    return ElMessageBox.confirm(`确定更改吗?`, "提示", {
      confirmButtonText: "确定",
      cancelButtonText: "取消",
      type: "warning",
    }).then(async () => {
      loading.value = true;
      return BasicRegionAPI.set(level, row.id, data)
        .then(() => {
          ElMessage.success(t("common.succeed.update"));
          return true;
        })
        .catch((error) => {
          ElMessage.error(error.message || t("common.failed.update"));
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
    getRegions("province");
  });
</script>
