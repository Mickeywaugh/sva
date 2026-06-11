/**
 * API 相关枚举
 *
 * @description
 * 包含 API 响应码、请求状态等枚举定义
 */

/**
 * API 响应码枚举
 */
export const enum ApiCodeEnum {
  /**
   * 权限不足
   */
  PERMISSION_DENIED = 403,

  /**
 * 成功
 */
  SUCCESS = 0,
  /**
   * 错误
   */
  ERROR = 1,

  /**
   * 访问令牌无效或过期
   */
  ACCESS_TOKEN_INVALID = 401,

  /**
   * 刷新令牌无效或过期
   */
  REFRESH_TOKEN_INVALID = 402,
}
