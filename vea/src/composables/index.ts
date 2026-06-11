// SSE 服务
export { setupSse, cleanupSseServices } from "./sse";
export { useSse, useDictSync, useOnlineCount, cleanupSse, SseConnectionState } from "./sse";
export type { DictMessage, DictChangeMessage, DictChangeCallback } from "./sse";

// // Mercure 服务
// export { setupMercure, cleanupMercureServices } from "./mercure";
// export { useMercure, cleanupMercure, MercureConnectionState } from "./mercure";
// export type { DictMessage, DictChangeMessage, DictChangeCallback } from "./mercure";

// 表格相关
export { useTableSelection } from "./useTableSelection";

// 最近访问菜单
export { useRecentMenus } from "./useRecentMenus";
export type { RecentMenuItem } from "./useRecentMenus";
