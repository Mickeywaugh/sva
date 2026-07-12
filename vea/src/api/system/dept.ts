import request from "@/utils/request";

const DEPT_BASE_URL = "/api/v1/system/dept";

const DeptAPI = {
  /**
   * 获取部门列表
   *
   * @param queryParams 查询参数（可选）
   * @returns 部门树形表格数据
   */
  page(queryParams?: PageQueryParams) {
    return request<any, DeptItem[]>({
      url: `${DEPT_BASE_URL}/page`,
      method: "post",
      data: queryParams,
    });
  },

  /** 获取部门下拉列表 */
  getOptions() {
    return request<any, OptionItem[]>({
      url: `${DEPT_BASE_URL}/options`,
      method: "get",
    });
  },

  /**
   * 获取部门表单数据
   *
   * @param id 部门ID
   * @returns 部门表单数据
   */
  get(id: number) {
    return request<any, DeptForm>({
      url: `${DEPT_BASE_URL}/${id}`,
      method: "get",
    });
  },
  /**
   * 新增/修改部门
   *
   * @param id 部门ID
   * @param data 部门表单数据
   * @returns 请求结果
   */
  set(id: number, data: DeptForm) {
    return request({
      url: `${DEPT_BASE_URL}/${id}`,
      method: "post",
      data,
    });
  },

  /**
   * 删除部门
   *
   * @param ids 部门ID，多个以英文逗号(,)分隔
   * @returns 请求结果
   */
  delete(id: number) {
    return request({
      url: `${DEPT_BASE_URL}/${id}`,
      method: "delete",
    });
  }
};

export default DeptAPI;

/** 部门类型 */
export interface DeptItem extends DeptForm {
  /** 子部门 */
  children?: DeptItem[];
  /** 创建时间 */
  createTime?: Date;
  /** 修改时间 */
  updateTime?: Date;
}

/** 部门表单类型 */
export interface DeptForm {
  /** 部门ID(新增不填) */
  id: number;
  /** 部门名称 */
  name?: string;
  /** 部门编号 */
  code?: string;
  /** 父部门ID */
  parentId: number;
  /** 排序 */
  sort?: number;
  /** 状态(1:启用；0：禁用) */
  status?: number;
}
