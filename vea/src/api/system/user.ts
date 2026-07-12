import request from "@/utils/request";

const USER_BASE_URL = "/api/v1/system/users";

const UserAPI = {
  /**
   * 获取当前登录用户信息
   *
   * @returns 登录用户昵称、头像信息，包括角色和权限
   */
  getInfo() {
    return request<any, UserInfo>({
      url: `${USER_BASE_URL}/me`,
      method: "get",
    });
  },

  /**
   * 获取用户分页列表
   *
   * @param queryParams 查询参数
   */
  getPage(queryParams: PageQueryParams) {
    return request<any, PageResult<SysUserItem>>({
      url: `${USER_BASE_URL}/page`,
      method: "post",
      data: queryParams,
    });
  },

  /**
   * 获取用户表单详情
   *
   * @param userId 用户ID
   * @returns 用户表单详情
   */
  get(userId: number) {
    return request<any, SysUserForm>({
      url: `${USER_BASE_URL}/${userId}`,
      method: "get",
    });
  },
  /**
   * 添加用户
   * 修改用户
   *
   * @param id 用户ID
   * @param data 用户表单数据
   */
  set(id: number, data: SysUserForm) {
    return request({
      url: `${USER_BASE_URL}/${id}`,
      method: "post",
      data,
    });
  },

  /**
   * 修改用户密码
   *
   * @param id 用户ID
   * @param password 新密码
   */
  resetPassword(id: number, password: string) {
    return request({
      url: `${USER_BASE_URL}/${id}/resetPassword`,
      method: "put",
      data: { password: password },
    });
  },

  /**
   * 批量删除用户，多个以英文逗号(,)分割
   *
   * @param ids 用户ID字符串，多个以英文逗号(,)分割
   */
  deleteByIds(ids: string) {
    return request({
      url: `${USER_BASE_URL}/${ids}`,
      method: "delete",
    });
  },

  /** 下载用户导入模板 */
  downloadTemplate() {
    return request({
      url: `${USER_BASE_URL}/template`,
      method: "get",
      responseType: "blob",
    });
  },

  /**
   * 导出用户
   *
   * @param queryParams 查询参数
   */
  export(queryParams: PageQueryParams) {
    return request({
      url: `${USER_BASE_URL}/export`,
      method: "get",
      params: queryParams,
      responseType: "blob",
    });
  },

  /**
   * 导入用户
   *
   * @param deptId 部门ID
   * @param file 导入文件
   */
  import(deptId: number, file: File) {
    const formData = new FormData();
    formData.append("file", file);
    return request<any, ExcelResult>({
      url: `${USER_BASE_URL}/import`,
      method: "post",
      params: { deptId },
      data: formData,
      headers: {
        "Content-Type": "multipart/form-data",
      },
    });
  },

  /** 获取个人中心用户信息 */
  getProfile() {
    return request<any, UserProfileVO>({
      url: `${USER_BASE_URL}/profile`,
      method: "get",
    });
  },

  /** 修改个人中心用户信息 */
  updateProfile(data: any) {
    return request({
      url: `${USER_BASE_URL}/profile`,
      method: "put",
      data,
    });
  },

  /** 修改个人中心用户密码 */
  changePassword(data: PasswordChangeForm) {
    return request({
      url: `${USER_BASE_URL}/password`,
      method: "put",
      data,
    });
  },

  /** 发送短信验证码（绑定或更换手机号）*/
  sendMobileCode(mobile: string) {
    return request({
      url: `${USER_BASE_URL}/mobile/code`,
      method: "post",
      params: { mobile },
    });
  },

  /** 绑定或更换手机号 */
  bindOrChangeMobile(data: MobileUpdateForm) {
    return request({
      url: `${USER_BASE_URL}/mobile`,
      method: "put",
      data,
    });
  },

  /** 发送邮箱验证码（绑定或更换邮箱）*/
  sendEmailCode(email: string) {
    return request({
      url: `${USER_BASE_URL}/email/code`,
      method: "post",
      params: { email },
    });
  },

  /** 绑定或更换邮箱 */
  bindOrChangeEmail(data: EmailUpdateForm) {
    return request({
      url: `${USER_BASE_URL}/email`,
      method: "put",
      data,
    });
  },

  /**
   *  获取用户下拉列表
   */
  getOptions() {
    return request<any, OptionItem[]>({
      url: `${USER_BASE_URL}/options`,
      method: "get",
    });
  },
  /**
   * 修改用户密码
   *
   * @param id
   * @param password
   */
  updatePassword(id: number, password: string) {
    return request({
      url: `${USER_BASE_URL}/${id}/password`,
      method: "patch",
      params: { password },
    });
  },
};

export default UserAPI;

/** 登录用户信息 */
export interface UserInfo {
  userId?: number;
  username?: string;
  deptname?: string;
  nickname?: string;
  avatar?: string;
  roles: string[];
  perms: string[];
  sseTopics: string[];
}

/** 用户分页对象 */
export interface SysUserItem extends SysUserForm {
  createTime?: Date;
  deptName?: string;
  updateTime?: Date;
  username: string;
}

export interface SysUserForm {
  id: number;
  username: string;
  avatar?: string;
  createTime?: Date;
  deptName?: string;
  email?: string;
  gender?: number;
  mobile?: string;
  dept?: number;
  nickname?: string;
  roleNames?: string;
  roleIds?: number[];
  status?: number;
}


/** 个人中心用户信息 */
export interface UserProfileVO {
  /** 用户ID */
  id: number;

  /** 用户名 */
  username?: string;

  /** 昵称 */
  nickname?: string;

  /** 头像URL */
  avatar?: string;

  /** 性别 */
  gender?: number;

  /** 手机号 */
  mobile?: string;

  /** 邮箱 */
  email?: string;

  /** 部门名称 */
  deptName?: string;

  /** 角色名称，多个使用英文逗号(,)分割 */
  roleNames?: string;

  /** 创建时间 */
  createTime?: Date;
  oaLoginName?: string;
}

/** 个人中心用户信息表单 */
export interface UserProfileForm {
  /** 用户ID */
  id: number;

  /** 用户名 */
  username?: string;

  /** 昵称 */
  nickname?: string;

  /** 头像URL */
  avatar?: string;

  /** 性别 */
  gender?: number;

  /** 手机号 */
  mobile?: string;

  /** 邮箱 */
  email?: string;
}

/** 修改密码表单 */
export interface PasswordChangeForm {
  /** 原密码 */
  oldPassword?: string;
  /** 新密码 */
  newPassword?: string;
  /** 确认新密码 */
  confirmPassword?: string;
}

/** 修改手机表单 */
export interface MobileUpdateForm {
  /** 手机号 */
  mobile?: string;
  /** 验证码 */
  code?: string;
}

/** 修改邮箱表单 */
export interface EmailUpdateForm {
  /** 邮箱 */
  email?: string;
  /** 验证码 */
  code?: string;
}
