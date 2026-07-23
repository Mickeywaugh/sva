import { ref, readonly } from "vue";
import AuthAPI from "@/api/auth";
import { AuthStorage } from "@/utils/auth";
import { useOnlineCount } from "./sse/useOnlineCount";

const HEARTBEAT_INTERVAL = 5 * 60 * 1000; // 5分钟

let globalInstance: ReturnType<typeof createHeartbeatComposable> | null = null;

function createHeartbeatComposable() {
  const isRunning = ref(false);
  let timer: ReturnType<typeof setInterval> | null = null;

  const sendHeartbeat = async () => {
    if (!AuthStorage.getAccessToken()) return;
    try {
      const res = await AuthAPI.heartbeat();
      if (res?.onlineCount !== undefined) {
        useOnlineCount({ autoInit: false }).updateCount(res.onlineCount);
      }
    } catch (e) {
      console.error("Heartbeat error:", e);
    }
  };

  const start = () => {
    if (isRunning.value) {
      console.log("[Heartbeat] Already running, skip");
      return;
    }
    const token = AuthStorage.getAccessToken();
    if (!token) {
      console.warn("[Heartbeat] No access token, cannot start");
      return;
    }
    isRunning.value = true;
    console.log("[Heartbeat] Started, interval:", HEARTBEAT_INTERVAL / 1000, "s");

    // 立即发送一次
    sendHeartbeat();

    // 每5分钟发送一次
    timer = setInterval(sendHeartbeat, HEARTBEAT_INTERVAL);
  };

  const stop = () => {
    isRunning.value = false;
    if (timer) {
      clearInterval(timer);
      timer = null;
    }
  };

  return { isRunning: readonly(isRunning), start, stop };
}

/**
 * 心跳组合式函数（单例模式）
 * 登录后每5分钟向后端发送心跳请求，未登录不发送。
 */
export function useHeartbeat() {
  if (!globalInstance) {
    globalInstance = createHeartbeatComposable();
  }
  return globalInstance;
}
