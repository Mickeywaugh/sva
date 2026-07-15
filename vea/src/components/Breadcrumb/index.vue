<template>
  <el-breadcrumb class="flex-y-center">
    <el-breadcrumb-item v-for="(item, index) in breadcrumbs" :key="item.path">
      <!-- 末级或不可跳转的节点显示为纯文本，其余可点击 -->
      <span class="color-gray-400">
        {{ translateRouteTitle(item.meta.title ?? "") }}
      </span>
    </el-breadcrumb-item>
  </el-breadcrumb>
</template>

<script setup lang="ts">
  import type { RouteLocationMatched } from "vue-router";
  import { translateRouteTitle } from "@/lang/utils";

  type BreadcrumbRoute = {
    path: string;
    name?: RouteLocationMatched["name"];
    redirect?: string;
    meta: RouteLocationMatched["meta"];
  };
  const currentRoute = useRoute();
  const breadcrumbs = ref<BreadcrumbRoute[]>([]);

  // 生成面包屑：取路由 matched 中有标题的层级，
  // 用 meta.breadcrumb = false 可以隐藏某一级
  function getBreadcrumb() {
    const matched: BreadcrumbRoute[] = currentRoute.matched
      .filter((item) => item.meta && item.meta.title)
      .map(({ path, name, redirect, meta }) => ({
        path,
        name,
        redirect: typeof redirect === "string" ? redirect : undefined,
        meta,
      }));

    breadcrumbs.value = matched.filter((item) => {
      return item.meta && item.meta.title && item.meta.breadcrumb !== false;
    });
  }

  // 路由变化就重算面包屑，但 /redirect/ 这类中转路由跳过
  watch(
    () => currentRoute.path,
    (path) => {
      if (path.startsWith("/redirect/")) {
        return;
      }
      getBreadcrumb();
    }
  );

  onBeforeMount(() => {
    getBreadcrumb();
  });
</script>

<style lang="scss" scoped>
// 覆盖 element-plus 的样式
.el-breadcrumb__inner,
.el-breadcrumb__inner a {
  font-weight: 400 !important;
}
</style>
