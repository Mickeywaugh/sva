import request from "@/utils/request";
// 菜单基础URL
const MENU_BASE_URL = "/api/v1/system/menus";

const MenuAPI = {
  /**
   * 获取当前用户的路由列表
   * <p/>
   * 无需传入角色，后端解析token获取角色自行判断是否拥有路由的权限
   *
   * @returns 路由列表
   */
  getRoutes() {
    return request<any, RouteItem[]>({
      url: `${MENU_BASE_URL}/routes`,
      method: "get",
    });
  },

  /**
   * 获取菜单树形列表
   *
   * @param queryParams 查询参数
   * @returns 菜单树形列表
   */
  getTree(queryParams: MenuQuery) {
    return request<any, MenuItem[]>({
      url: `${MENU_BASE_URL}/list`,
      method: "get",
      params: queryParams,
    });
  },

  /**
   * 获取菜单下拉数据源
   *
   * @returns 菜单下拉数据源
   */
  getOptions(onlyParent?: boolean) {
    return request<any, OptionItem[]>({
      url: `${MENU_BASE_URL}/options`,
      method: "get",
      params: { onlyParent },
    });
  },

  /**
   * 获取菜单下拉数据源,不包括按钮
   */
  getMenuOptions() {
    return request<any, OptionItem[]>({
      url: `${MENU_BASE_URL}/menuOptions`,
      method: "get",
    });
  },
  /**
   * 获取菜单表单数据
   *
   * @param id 菜单ID
   */
  get(id: number) {
    return request<any, MenuForm>({
      url: `${MENU_BASE_URL}/${id}`,
      method: "get",
    });
  },

  /** 
   * 添加菜单
   * 修改菜单
   *
   * @param id 菜单ID
   * @param data 菜单表单数据
   * @returns 请求结果
   */
  set(id: number, data: MenuForm) {
    return request({
      url: `${MENU_BASE_URL}/${id}`,
      method: "post",
      data,
    });
  },

  /**
   * 删除菜单
   *
   * @param id 菜单ID
   * @returns 请求结果
   */
  delete(id: number) {
    return request({
      url: `${MENU_BASE_URL}/${id}`,
      method: "delete",
    });
  },
  /**
   * 更改状态
   */
  setStatus(id: number, data: any) {
    return request({
      url: `${MENU_BASE_URL}/${id}/status`,
      method: "put",
      data,
    });
  },
};

export default MenuAPI;

import type { MenuTypeEnum } from "@/enums/business";

/** 菜单查询参数 */
export interface MenuQuery {
  /** 搜索关键字 */
  keywords?: string;
}

/** 菜单视图对象 */
export interface MenuItem {
  /** 子菜单 */
  children?: MenuItem[];
  /** 组件路径 */
  component?: string;
  /** ICON */
  icon?: string;
  /** 菜单ID */
  id?: number;
  /** 菜单名称 */
  name?: string;
  /** 父菜单ID */
  parentId?: number;
  /** 按钮权限标识 */
  perm?: string;
  /** 跳转路径 */
  redirect?: string;
  /** 路由名称 */
  routeName?: string;
  /** 路由相对路径 */
  routePath?: string;
  /** 菜单排序(数字越小排名越靠前) */
  sort?: number;
  /** 菜单 */
  type?: MenuTypeEnum;
  /** 菜单是否可见(1:显示;0:隐藏) */
  visible?: number;
}

/** 菜单表单对象 */
export interface MenuForm {
  /** 菜单ID */
  id: number;
  /** 父菜单ID */
  parentId?: number;
  /** 菜单名称 */
  name?: string;
  /** 菜单是否可见(1-是 0-否) */
  visible: number;
  /** ICON */
  icon?: string;
  /** 排序 */
  sort?: number;
  /** 路由名称 */
  routeName?: string;
  /** 路由路径 */
  routePath?: string;
  /** 组件路径 */
  component?: string;
  /** 跳转路由路径 */
  redirect?: string;
  /** 菜单 */
  type: MenuTypeEnum;
  /** 权限标识 */
  perm?: string;
  /** 【菜单】是否开启页面缓存 */
  keepAlive?: boolean;
  /** 【目录】只有一个子路由是否始终显示 */
  alwaysShow?: number;
  /** 参数 */
  params?: KeyValue[];
  /** 是否新窗口打开 */
  blank?: number;
  t: string;
  noAuth?: number;
}

interface KeyValue {
  key: string;
  value: string;
}

/** RouteItem，路由对象 */
export interface RouteItem {
  /** 子路由列表 */
  children: RouteItem[];
  /** 组件路径 */
  component?: string;
  /** 路由属性 */
  meta?: Meta;
  /** 路由名称 */
  name?: string;
  /** 路由路径 */
  path?: string;
  /** 跳转链接 */
  redirect?: string;
}

/** Meta，路由属性 */
export interface Meta {
  /** 【目录】只有一个子路由是否始终显示 */
  alwaysShow?: boolean;
  /** 是否隐藏(true-是 false-否) */
  hidden?: boolean;
  /** ICON */
  icon?: string;
  /** 【菜单】是否开启页面缓存 */
  keepAlive?: boolean;
  /** 路由title */
  title?: string;
  /** 是否新窗口打开 */
  blank?: boolean;
  /** 权限标识 */
  params?: {};
  isPublic?: number;
}
