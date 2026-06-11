import { ref, computed, readonly, watch } from "vue";
import { useUserStoreHook } from "@/stores/user";

export interface UseSseOptions {
  /** Mercure Hub 地址，默认从 VITE_MERCURE_URL 环境变量读取 */
  url?: string;
  /** 订阅的 topic 列表（可选，默认从 userInfo.sseTopics 动态获取） */
  topics?: string[];
  /** 是否在控制台打印调试日志 */
  debug?: boolean;
  /** 重连间隔(ms) */
  reconnectInterval?: number;
  /** 最大重试次数，0 表示无限重连 */
  maxReconnectAttempts?: number;
}

type EventHandler = (data: unknown) => void;

export enum SseConnectionState {
  DISCONNECTED = "DISCONNECTED",
  CONNECTING = "CONNECTING",
  CONNECTED = "CONNECTED",
}

let globalInstance: ReturnType<typeof createSseConnection> | null = null;

function createSseConnection(options: UseSseOptions = {}) {
  const userStore = useUserStoreHook();

  const config = {
    // 开发环境通过 Vite 代理访问，避免 CORS；生产环境直接访问
    url: options.url ?? `${window.location.origin}/.well-known/mercure`,
    topics: [...new Set([...["system.notification", "system.onlineCount", "dict.change"], ...(userStore.userInfo.sseTopics ?? []), ...(options.topics ?? [])])],
    debug: options.debug ?? false,
    reconnectInterval: options.reconnectInterval ?? 3000,
    maxReconnectAttempts: options.maxReconnectAttempts ?? 0, // 0 表示无限重连
  };

  const connectionState = ref<SseConnectionState>(SseConnectionState.DISCONNECTED);
  const isConnected = computed(() => connectionState.value === SseConnectionState.CONNECTED);

  let eventSource: EventSource | null = null;
  let isManualDisconnect = false;
  let reconnectTimer: ReturnType<typeof setTimeout> | null = null;
  let reconnectAttempts = 0;

  const eventHandlers = new Map<string, Set<EventHandler>>();

  const log = (...args: unknown[]) => {
    if (config.debug) {
      console.debug("[SSE]", ...args);
    }
  };
  const logError = (...args: unknown[]) => console.error("[SSE]", ...args);

  // 构建 Mercure 订阅 URL（带 topic 参数）
  const buildUrl = (): string => {
    const url = new URL(config.url);
    config.topics.forEach((topic) => url.searchParams.append("topic", topic));
    return url.toString();
  };

  // 分发事件给订阅者
  const dispatchEvent = (eventName: string, data: unknown) => {
    const handlers = eventHandlers.get(eventName);
    if (handlers && handlers.size > 0) {
      handlers.forEach((handler) => handler(data));
    }
    log(`收到事件[${eventName}]:`, data);
  };

  // 处理收到的 SSE 消息
  const handleMessage = (event: MessageEvent<string>) => {
    const { data } = event;
    if (!data) return;

    try {
      const parsed = JSON.parse(data);
      // Mercure 自定义事件名与 topic 路径一致
      const eventName = event.type !== "message" ? event.type : "message";
      dispatchEvent(eventName, parsed);
    } catch {
      dispatchEvent(event.type || "message", data);
    }
  };

  // 清理 EventSource
  const closeEventSource = () => {
    if (eventSource) {
      eventSource.close();
      eventSource = null;
    }
    if (reconnectTimer) {
      clearTimeout(reconnectTimer);
      reconnectTimer = null;
    }
  };

  // 调度重连
  const scheduleReconnect = () => {
    if (isManualDisconnect) return;

    const maxAttempts = config.maxReconnectAttempts;
    if (maxAttempts > 0 && reconnectAttempts >= maxAttempts) {
      logError(`已达到最大重试次数 ${maxAttempts}，停止重连`);
      connectionState.value = SseConnectionState.DISCONNECTED;
      return;
    }

    reconnectAttempts++;
    const delay = config.reconnectInterval;
    log(`将在 ${delay}ms 后重试（第 ${reconnectAttempts} 次）`);

    reconnectTimer = setTimeout(() => {
      connect();
    }, delay);
  };

  // 建立 Mercure Hub 的 SSE 连接（使用原生 EventSource）
  const connect = () => {
    isManualDisconnect = false;

    if (connectionState.value === SseConnectionState.CONNECTED) {
      log("SSE 已连接，跳过重复连接");
      return;
    }

    if (config.topics.length === 0) {
      log("topic 列表为空，跳过 SSE 连接（等待用户信息加载）");
      return;
    }

    // 清理旧连接
    closeEventSource();

    connectionState.value = SseConnectionState.CONNECTING;

    const url = buildUrl();
    log("正在建立 SSE 连接...");
    log(`订阅 URL: ${url}`);
    log(`订阅 topics: ${config.topics.join(", ")}`);

    try {
      // 使用原生 EventSource，匿名订阅（ALLOW_ANONYMOUS=1）
      eventSource = new EventSource(url);

      eventSource.onopen = () => {
        connectionState.value = SseConnectionState.CONNECTED;
        reconnectAttempts = 0;
        log("SSE 连接已建立");
      };

      eventSource.onmessage = handleMessage;

      // 注册所有 topic 对应的事件监听
      config.topics.forEach((topic) => {
        eventSource!.addEventListener(topic, (event: Event) => {
          handleMessage(event as MessageEvent<string>);
        });
      });

      eventSource.onerror = () => {
        const state = eventSource?.readyState;
        if (state === EventSource.CLOSED) {
          logError("SSE 连接已关闭");
        } else {
          logError("SSE 连接错误");
        }

        closeEventSource();

        if (isManualDisconnect) {
          connectionState.value = SseConnectionState.DISCONNECTED;
          return;
        }

        connectionState.value = SseConnectionState.DISCONNECTED;
        scheduleReconnect();
      };
    } catch (err) {
      logError("创建 EventSource 失败:", err);
      connectionState.value = SseConnectionState.DISCONNECTED;
      scheduleReconnect();
    }
  };

  // 订阅事件，返回取消函数
  const on = <T = unknown>(eventName: string, handler: (data: T) => void): (() => void) => {
    if (!eventHandlers.has(eventName)) {
      eventHandlers.set(eventName, new Set());
    }
    const wrappedHandler: EventHandler = (data) => handler(data as T);
    eventHandlers.get(eventName)!.add(wrappedHandler);
    log(`已订阅事件: ${eventName}`);

    return () => {
      const handlers = eventHandlers.get(eventName);
      if (handlers) {
        handlers.delete(wrappedHandler);
        if (handlers.size === 0) {
          eventHandlers.delete(eventName);
        }
      }
    };
  };

  // 主动断开，不会触发重连
  const disconnect = () => {
    isManualDisconnect = true;
    closeEventSource();
    connectionState.value = SseConnectionState.DISCONNECTED;
    log("SSE 连接已断开");
  };

  // 登出时调用，断开并释放所有资源
  const cleanup = () => {
    disconnect();
    eventHandlers.clear();
    log("SSE 资源已清理");
  };

  return {
    connectionState: readonly(connectionState),
    isConnected,
    connect,
    disconnect,
    cleanup,
    on
  };
}

export function useSse(options: UseSseOptions = {}) {
  if (!globalInstance) {
    globalInstance = createSseConnection(options);
  }
  return globalInstance;
}

/**
 * 清理 SSE 连接并释放全局单例
 */
export function cleanupSse() {
  if (globalInstance) {
    globalInstance.cleanup();
    globalInstance = null;
  }
}
