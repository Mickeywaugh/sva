import request from "@/utils/request";

const NOTICE_BASE_URL = "/api/v1/system/notices";

const NoticeAPI = {
  /** 获取通知公告分页数据 */
  getPage(queryParams?: PageQueryParams) {
    return request<any, PageResult<NoticeItem>>({
      url: `${NOTICE_BASE_URL}/page`,
      method: "post",
      data: queryParams,
    });
  },

  /**
   * 获取通知公告表单数据
   *
   * @param id NoticeID
   * @returns Notice表单数据
   */
  getFormData(id: number) {
    return request<any, NoticeForm>({
      url: `${NOTICE_BASE_URL}/${id}/form`,
      method: "get",
    });
  },

  /**
   * 添加通知公告
   *
   * @param data Notice表单数据
   * @returns
   */
  add(data: NoticeForm) {
    return request({
      url: `${NOTICE_BASE_URL}`,
      method: "post",
      data,
    });
  },

  /**
   * 更新通知公告
   *
   * @param id NoticeID
   * @param data Notice表单数据
   */
  update(id: number, data: NoticeForm) {
    return request({
      url: `${NOTICE_BASE_URL}/${id}`,
      method: "put",
      data,
    });
  },

  /**
   * 批量删除通知公告，多个以英文逗号(,)分割
   *
   * @param ids 通知公告ID字符串，多个以英文逗号(,)分割
   */
  deleteByIds(ids: string) {
    return request({
      url: `${NOTICE_BASE_URL}/${ids}`,
      method: "delete",
    });
  },

  /**
   * 发布通知
   *
   * @param id 被发布的通知公告id
   * @returns
   */
  publish(id: number) {
    return request({
      url: `${NOTICE_BASE_URL}/${id}/publish`,
      method: "put",
    });
  },

  /**
   * 撤回通知
   *
   * @param id 撤回的通知id
   * @returns
   */
  revoke(id: number) {
    return request({
      url: `${NOTICE_BASE_URL}/${id}/revoke`,
      method: "put",
    });
  },
  /**
   * 查看通知
   *
   * @param id
   */
  getDetail(id: number) {
    return request<any, NoticeDetail>({
      url: `${NOTICE_BASE_URL}/${id}/detail`,
      method: "get",
    });
  },

  /* 全部已读 */
  readAll() {
    return request({
      url: `${NOTICE_BASE_URL}/read-all`,
      method: "put",
    });
  },

  /** 获取我的通知分页列表 */
  getMyNoticePage(queryParams?: PageQueryParams) {
    return request<any, PageResult<NoticeItem>>({
      url: `${NOTICE_BASE_URL}/my-page`,
      method: "POST",
      data: queryParams,
    });
  },
};

export default NoticeAPI;
/**
 * Notice 通知类型定义
 */

/** 通知表单对象 */
export interface NoticeForm {
  /** 通知ID */
  id?: number;
  /** 通知标题 */
  title?: string;
  /** 通知内容 */
  content?: string;
  /** 通知类型 */
  type?: number;
  /** 通知等级 */
  level?: string;
  /** 发布状态(0:草稿;1:已发布;-1:已撤回) */
  status?: number;
  /** 目标用户ID列表 */
  targetUserIds?: number[];
  /** 目标类型 (1:全部,2:指定用户等) */
  targetType?: number;
}

/** 通知分页对象 */
export interface NoticeItem {
  /** 通知ID */
  id: number;
  /** 通知标题 */
  title: string;
  /** 通知内容 */
  content: string;
  /** 通知类型 */
  type: number;
  /** 通知等级 */
  level: string;
  /** 发布状态 */
  publishStatus: number;
  /** 是否已读 */
  isRead: number;
  /** 发布时间 */
  publishTime?: Date;
  /** 撤回时间 */
  revokeTime?: Date;
}

/** 通知详情对象 */
export interface NoticeDetail {
  /** 通知ID */
  id?: string;
  /** 通知标题 */
  title?: string;
  /** 通知内容 */
  content?: string;
  /** 通知类型 */
  type?: number;
  /** 通知等级 */
  level?: string;
  /** 发布状态 */
  publishStatus?: number;
  /** 目标用户ID */
  targetUserIds?: string;
  /** 发布人名称 */
  publisherName?: string;
  /** 发布时间 */
  publishTime?: Date;
}
