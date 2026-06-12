import { RouteRecordRaw } from "vue-router";

export const PublicRoutes: RouteRecordRaw[] = [
  {
    path: "/public/:id",
    name: "publicIndex",
    component: () => import("@/views/public/index.vue"),
    meta: {
      title: "公共页面",
      icon: "vea-doc",
    },
  },
];
