import request from "@/utils/request";

const ROLE_BASE_URL = "/api/v1/system/roles";

const RoleAPI = {
  /** 获取角色分页数据 */
  getPage(postData?: PageQueryParams) {
    return request<any, PageResult<RoleItem>>({
      url: `${ROLE_BASE_URL}/page`,
      method: "post",
      data: postData,
    });
  },

  /** 获取角色下拉数据源 */
  getOptions() {
    return request<any, OptionItem[]>({
      url: `${ROLE_BASE_URL}/options`,
      method: "get",
    });
  },
  /**
   * 获取角色的菜单ID集合
   *
   * @param roleId 角色ID
   * @returns 角色的菜单ID集合
   */
  getRoleMenuIds(roleId: number) {
    return request<any, number[]>({
      url: `${ROLE_BASE_URL}/${roleId}/menuIds`,
      method: "get",
    });
  },

  /**
   * 分配菜单权限
   *
   * @param roleId 角色ID
   * @param data 菜单ID集合
   */
  updateRoleMenus(roleId: number, data: number[]) {
    return request({
      url: `${ROLE_BASE_URL}/${roleId}/menus`,
      method: "put",
      data: data,
    });
  },

  /**
   * 获取角色表单数据
   *
   * @param id 角色ID
   * @returns 角色表单数据
   */
  getFormData(id: number) {
    return request<any, RoleForm>({
      url: `${ROLE_BASE_URL}/${id}/form`,
      method: "get",
    });
  },

  /** 添加角色 */
  add(data: RoleForm) {
    return request({
      url: `${ROLE_BASE_URL}`,
      method: "post",
      data: data,
    });
  },

  /**
   * 更新角色
   *
   * @param id 角色ID
   * @param data 角色表单数据
   */
  update(id: number, data: RoleForm) {
    return request({
      url: `${ROLE_BASE_URL}/${id}`,
      method: "put",
      data: data,
    });
  },

  /**
   * 批量删除角色，多个以英文逗号(,)分割
   *
   * @param ids 角色ID字符串，多个以英文逗号(,)分割
   */
  deleteByIds(ids: string) {
    return request({
      url: `${ROLE_BASE_URL}/${ids}`,
      method: "delete",
    });
  },
  /**
   * 更改状态
   */
  setStatus(id: number, data: any) {
    return request({
      url: `${ROLE_BASE_URL}/${id}/status`,
      method: "put",
      data: data,
    });
  },
};

export default RoleAPI;

/** 角色分页对象 */
export interface RoleItem {
  /** 角色编码 */
  code?: string;
  /** 角色ID */
  id?: number;
  /** 角色名称 */
  name?: string;
  /** 排序 */
  sort?: number;
  /** 角色状态 */
  status?: number;
  userCount?: number;
  dataScope?: number; // 数据权限
  /** 创建时间 */
  createTime?: Date;
  /** 修改时间 */
  updateTime?: Date;
}

/** 角色表单对象 */
export interface RoleForm {
  /** 角色ID */
  id?: number;
  /** 角色编码 */
  code?: string;
  /** 数据权限 */
  dataScope?: number;
  /** 角色名称 */
  name?: string;
  /** 排序 */
  sort?: number;
  /** 角色状态(1-正常；0-停用) */
  status?: number;
}
