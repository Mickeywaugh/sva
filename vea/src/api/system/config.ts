import request from "@/utils/request";

const CONFIG_BASE_URL = "/api/v1/system/config";

const ConfigAPI = {
  /** 获取系统配置分页数据 */
  getPage(queryParams?: PageQueryParams) {
    return request<any, PageResult<ConfigItem>>({
      url: `${CONFIG_BASE_URL}/page`,
      method: "post",
      data: queryParams,
    });
  },
  /**
   * 获取系统配置表单数据
   *
   * @param id ConfigID
   * @returns Config表单数据
   */
  get(id: number) {
    return request<any, ConfigForm>({
      url: `${CONFIG_BASE_URL}/${id}`,
      method: "get",
    });
  },

  /**
   * 更新系统配置
   *
   * @param id ConfigID
   * @param data Config表单数据
   */
  set(id: number, data: ConfigForm) {
    return request({
      url: `${CONFIG_BASE_URL}/${id}`,
      method: "post",
      data,
    });
  },

  /**
   * 删除系统配置
   *
   * @param id 系统配置ID
   */
  delete(id: number) {
    return request({
      url: `${CONFIG_BASE_URL}/${id}`,
      method: "delete",
    });
  },

  refreshCache() {
    return request({
      url: `${CONFIG_BASE_URL}/refresh`,
      method: "PUT",
    });
  },
};

export default ConfigAPI;

/** 系统配置表单对象 */
export interface ConfigForm {
  /** 主键 */
  id: number;
  /** 配置名称 */
  configName?: string;
  /** 配置键 */
  configKey?: string;
  /** 配置值 */
  configValue?: string;
  /** 描述、备注 */
  remark?: string;
}

/** 系统配置分页对象 */
export interface ConfigItem {
  /** 主键 */
  id?: number;
  /** 配置名称 */
  configName?: string;
  /** 配置键 */
  configKey?: string;
  /** 配置值 */
  configValue?: string;
  /** 描述、备注 */
  remark?: string;
}
