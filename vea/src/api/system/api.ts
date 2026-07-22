import { StatusTag } from "@/enums";
import request from "@/utils/request";
import { AuthStorage } from "@/utils/auth";

const BASE_URL = "/api/v1/system/api";

const SysApi = {
  /** 获取系统接口分页数据 */
  page(params?: PageQueryParams) {
    return request<any, PageResult<SysApiItem>>({
      url: `${BASE_URL}/page`,
      method: "post",
      data: params,
    });
  },
  /**
   * 新建和更新系统接口
   *
   * @param id 接口ID
   * @param data 接口表单数据
   */
  set(data: SysApiForm | any) {
    return request({
      url: `${BASE_URL}/${data.id}`,
      method: "post",
      data,
    });
  },
  test(id: number) {
    return request({
      url: `${BASE_URL}/test/${id}`,
      method: "get"
    });
  },

  /**
   * 删除系统接口
   *
   * @param id 接口ID
   */
  delete(id: number) {
    return request({
      url: `${BASE_URL}/${id}`,
      method: "delete",
    });
  },
  getModuleOptions() {
    return request<OptionItem[]>({
      url: `${BASE_URL}/moduleOptions`,
      method: "get",
    });
  },
  getOptions(module: string) {
    return request<OptionItem[]>({
      url: `${BASE_URL}/options/${module}`,
      method: "get",
    });
  },
  sync() {
    return request({
      url: `${BASE_URL}/sync`,
      method: "get",
    });
  },
  /**
   * 流式自动测试，每完成一个接口即回调 onProgress
   * @param onProgress 每行结果回调 (sysApi: SysApiMsg) => void
   * @param onDone     全部完成后回调
   * @param onError    出错回调
   */
  async autoTestStream(
    onProgress: (sysApi: SysApiMsg) => void,
    onDone?: () => void,
    onError?: (err: Error) => void
  ) {
    try {
      const token = AuthStorage.getAccessToken();
      const response = await fetch(`${BASE_URL}/autoTest?_t=${Date.now()}`, {
        headers: token ? { Authorization: `Bearer ${token}` } : {},
        cache: 'no-store',
      });
      if (!response.ok) throw new Error(`HTTP ${response.status}`);

      const reader = response.body!.getReader();
      const decoder = new TextDecoder();
      let buffer = '';

      while (true) {
        const { done, value } = await reader.read();
        if (done) break;

        buffer += decoder.decode(value, { stream: true });
        const lines = buffer.split('\n');
        // 最后一行可能不完整，留到下次
        buffer = lines.pop() || '';

        for (const line of lines) {
          if (line.trim()) {
            onProgress(JSON.parse(line) as SysApiMsg);
          }
        }
      }
      // 处理剩余的 buffer
      if (buffer.trim()) {
        onProgress(JSON.parse(buffer) as SysApiMsg);
      }
      onDone?.();
    } catch (e) {
      onError?.(e as Error);
    }
  }
};

export default SysApi;

/** 系统接口表单对象 */
export interface SysApiItem extends SysApiForm {
  result: number;
  responseCode: number;
  responseContext: string;
  createTime: string;
  updateTime: string;
}

export interface SysApiForm {
  id: number;
  module: string;
  name: string;
  path: string;
  method: string;
  withJwt: number;
  disabled: boolean;
  routeParams: Record<string, any>;
  queryParams: Record<string, any>;
  bodyParams: Record<string, any>;
}

export interface SysApiMsg {
  id: number;
  name: string;
  result: number;
  responseCode: number;
  responseContext: string;
}

export const SysApiResultMap = new Map<number, StatusTag>([
  [0, { type: 'danger', effect: "dark", label: 'Failed' }],
  [1, { type: 'success', effect: "dark", label: 'OK' }],
]);