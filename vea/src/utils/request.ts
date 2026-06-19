import axios, { type InternalAxiosRequestConfig, type AxiosResponse } from "axios";
import qs from "qs";
import { ApiCodeEnum } from "@/enums/api";
import { useUserStoreHook } from "@/stores/user";
import { usePermissionStoreHook } from "@/stores/permission";
import { AuthStorage, redirectToLogin } from "@/utils/auth";

// 记录已重试的请求，防止无限循环
const retriedConfigs = new WeakSet<InternalAxiosRequestConfig>();

// 全局 token 刷新锁，防止并发请求同时触发多次刷新
let refreshPromise: Promise<void> | null = null;

// HTTP 请求实例
const http = axios.create({
  baseURL: import.meta.env.VITE_APP_BASE_API,
  timeout: 50000,
  headers: { "Content-Type": "application/json;charset=utf-8" },
  paramsSerializer: (params: Record<string, any>) => qs.stringify(params, { arrayFormat: "repeat" }),
});

// 请求拦截器
http.interceptors.request.use(
  (config: InternalAxiosRequestConfig) => {
    const token = AuthStorage.getAccessToken();

    if (config.headers.Authorization === "no-auth") {
      delete config.headers.Authorization;
    } else if (token) {
      config.headers.Authorization = `Bearer ${token}`;
    }

    return config;
  },
  (error: any) => Promise.reject(error)
);

/**
 * 处理 401 token 过期（成功分支复用此逻辑）
 */
const handleAccessTokenInvalid = async (
  config: InternalAxiosRequestConfig,
  msg?: string
): Promise<never> => {
  // 已重试过，直接跳登录
  if (retriedConfigs.has(config)) {
    await redirectToLogin("登录已过期，请重新登录", true);
    return Promise.reject(new Error("Token Invalid"));
  }

  retriedConfigs.add(config);

  try {
    const userStore = useUserStoreHook();

    // 全局锁：确保多个并发 401 请求只触发一次 token 刷新
    if (!refreshPromise) {
      refreshPromise = userStore.refreshTokenOnce().finally(() => {
        refreshPromise = null;
      });
    }
    await refreshPromise;

    const token = AuthStorage.getAccessToken();
    if (token) {
      config.headers.set("Authorization", `Bearer ${token}`);
    }

    // 重新发起请求并透传结果
    return http(config) as unknown as Promise<never>;
  } catch {
    await redirectToLogin("登录已过期，请重新登录");
    return Promise.reject(new Error("Token refresh failed"));
  }
};

// 响应拦截器
http.interceptors.response.use(
  (response: AxiosResponse) => {
    const { responseType } = response.config;

    // 二进制数据直接返回
    if (responseType === "blob" || responseType === "arraybuffer") {
      return response;
    }

    const { code, data, msg } = response.data;

    if (code === ApiCodeEnum.SUCCESS) {
      return data;
    }

    // 成功分支中也可能收到 token 过期（HTTP 200 + code 401）
    if (code === ApiCodeEnum.ACCESS_TOKEN_INVALID) {
      return handleAccessTokenInvalid(response.config, msg);
    }

    ElMessage.error(msg || "系统出错");
    return Promise.reject(new Error(msg || "系统出错"));
  },

  async (error: any) => {
    const { config, response } = error;

    if (!response) {
      ElMessage.error("网络连接失败");
      return Promise.reject(error);
    }

    const { code, msg } = (response.data ?? {}) as ApiResponse;

    // Token 过期：尝试刷新 token 后自动重试一次
    if (code === ApiCodeEnum.ACCESS_TOKEN_INVALID) {
      return handleAccessTokenInvalid(config, msg);
    }

    // Refresh token 失效：无法续期，跳转登录
    if (code === ApiCodeEnum.REFRESH_TOKEN_INVALID) {
      await redirectToLogin("登录已过期，请重新登录", false);
      return Promise.reject(new Error("Token Invalid"));
    }

    // 权限不足：刷新权限快照后提示
    if (code === ApiCodeEnum.PERMISSION_DENIED) {
      const permissionStore = usePermissionStoreHook();
      await permissionStore.reloadPermissionSnapshotOnce();
      ElMessage.error(msg || "权限不足");
      return Promise.reject(new Error(msg || "权限不足"));
    }

    ElMessage.error(msg || "请求失败");
    return Promise.reject(new Error(msg || "请求失败"));
  }
);

export default http;
