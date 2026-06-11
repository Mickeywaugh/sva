import request from "@/utils/request";

const DICT_BASE_URL = "/api/v1/system/dict";

const DictAPI = {
  //---------------------------------------------------
  // 字典相关接口
  //---------------------------------------------------

  /**
   * 字典分页列表
   *
   * @param queryParams 查询参数
   * @returns 字典分页结果
   */
  page(node: DictNode, ParamsData: PageQueryParams) {
    return request<any, PageResult<Dict | DictItem[]>>({
      url: `${DICT_BASE_URL}/page/${node}`,
      method: "post",
      data: ParamsData,
    });
  },

  /**
   * 修改或新增
   * @param id 字典ID
   * @param data 字典表单数据
   */
  set(node: DictNode, id: number, data: DictForm) {
    return request({
      url: `${DICT_BASE_URL}/${node}/${id}`,
      method: "put",
      data,
    });
  },
  /**
   * 删除字典
     * @param ids 字典ID，多个以英文逗号(,)分隔
   */
  deleteByIds(node: DictNode, ids: string) {
    return request({
      url: `${DICT_BASE_URL}/batchDelete/${node}/${ids}`,
      method: "delete",
    });
  },

  /**
   * 获取字典项列表
   */
  getDictOptions(dictCode: string) {
    return request<any, OptionItem[]>({
      url: `${DICT_BASE_URL}/item/options/${dictCode}`,
      method: "get",
    });
  },
  /**
   * 删除字典项
   */
  delete(node: DictNode, id: number) {
    return request({
      url: `${DICT_BASE_URL}/${node}/${id}`,
      method: "delete",
    });
  },
};

export default DictAPI;

/**
 * 字典查询参数
 */
export interface DictPageQuery extends PageQueryParams {
  /**
   * 关键字(字典名称/编码)
   */
  keywords?: string;

  /**
   * 字典状态（1:启用，0:禁用）
   */
  status?: number;
}

/**
 * 字典分页对象
 */
export interface Dict extends DictForm {
  createTime?: string;
  updateTime?: string;
  createBy?: string;
  updateBy?: string;
}

/**
 * 字典
 */
export interface DictForm {
  /**
   * 字典ID
   */
  id?: number;
  /**
   * 字典名称
   */
  name?: string;
  /**
   * 字典编码
   */
  dictCode?: string;
  /**
   * 字典状态（1-启用，0-禁用）
   */
  status?: number;
  isNumber?: number;
  tagType?: "success" | "warning" | "info" | "primary" | "danger" | "";
  /**
   * 备注
   */
  remark?: string;
}

/**
 * 字典分页对象
 */
export interface DictItem extends DictItemForm {
  createTime?: string;
  createBy?: string;
  updateTime?: string;
  updateBy?: string;
}

/**
 * 字典
 */
export interface DictItemForm {
  /**
   * 字典ID
   */
  id?: number;
  /**
   * 字典编码
   */
  dictId?: number;
  /**
   * 字典数据值
   */
  value?: string;
  /**
   * 字典数据标签
   */
  label?: string;
  /**
   * 状态（1:启用，0:禁用)
   */
  status?: number;
  /**
   * 字典排序
   */
  sort?: number;

  isDefault?: number;
  /**
   * 标签类型
   */
  tagType?: "success" | "warning" | "info" | "primary" | "danger" | "";
}

/**
 * 字典项下拉选项
 */
export interface DictItemOption {
  value: number | string;
  label: string;
  tagType?: "" | "success" | "info" | "warning" | "danger";
  [key: string]: any;
}


export type DictNode = "dict" | "item";