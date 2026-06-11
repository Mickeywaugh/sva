<template>
  <div class="dashboard-container">
    <el-row :gutter="10" class="mt-0">
      <el-col :xs="24" :sm="12" :lg="8" class="mb-2" v-for="item in stcsCmps" :key="item.name">
        <component :is="chartComponent(item.name)" max-height="360px" :attrs="item.attrs" />
      </el-col>
    </el-row>
  </div>
</template>

<script setup lang="ts">

  defineOptions({
    name: "Dashboard",
    inheritAttrs: false,
  });

  const { t } = useI18n();

  // 组件列表
  const stcsCmps = ref([
    { name: "system", attrs: { title: t("sys.node") } }

  ]);

  const chartComponent = (item: string) => {
    return defineAsyncComponent(() => import(`./components/${item}.vue`));
  };

  onMounted(() => {

  });

</script>

<style lang="scss" scoped>
  .dashboard-container {
    position: relative;
    padding: 10px 15px;

    .data-box {
      display: flex;
      justify-content: space-between;
      padding: 20px;
      font-weight: bold;
      color: var(--el-text-color-regular);
      background: var(--el-bg-color-overlay);
      border-color: var(--el-border-color);
      box-shadow: var(--el-box-shadow-dark);
    }
  }
</style>