/**
 * 通知中心逻辑
 */
import { ref, computed, onMounted, onBeforeUnmount } from "vue";
import type { NoticeItem, NoticeDetail } from "@/api/system/notice";
import NoticeAPI from "@/api/system/notice";
import { useSse } from "@/composables";
import router from "@/router";
import { useUserStoreHook } from "@/stores/user";

const PAGE_SIZE = 5;

/**
 * 根据 userId 构建通知订阅的 topic
 * 后端对应的 topic 为 /user/{userId}/notices
 */
function buildNoticeTopic(userId: number | string): string {
  return `user.${userId}.notices`;
}

function buildRevokeTopic(userId: number | string): string {
  return `user.${userId}.notice-revoke`;
}

export function useNotice() {
  const { on } = useSse();
  const userStore = useUserStoreHook();
  const userId = computed(() => userStore.userInfo.userId);

  // 状态
  const list = ref<NoticeItem[]>([]);
  const unreadTotal = ref(0);
  const detail = ref<NoticeDetail | null>(null);
  const dialogVisible = ref(false);

  let unsubNotice: (() => void) | null = null;
  let unsubRevoke: (() => void) | null = null;

  // ============================================
  // 数据获取
  // ============================================

  async function fetchList(params?: Partial<PageQueryParams>) {
    const query: PageQueryParams = {
      pageNum: 1,
      pageSize: PAGE_SIZE,
      isRead: 0,
      ...params,
    };
    const page = await NoticeAPI.getMyNoticePage(query);
    list.value = page.list || [];
    unreadTotal.value = page.total ?? 0;
  }

  async function read(id: number) {
    detail.value = await NoticeAPI.getDetail(id);
    dialogVisible.value = true;

    const idx = list.value.findIndex((item: NoticeItem) => item.id === id);
    if (idx >= 0) list.value.splice(idx, 1);
    if (unreadTotal.value > 0) unreadTotal.value -= 1;

    await fetchList();
  }

  async function readAll() {
    await NoticeAPI.readAll();
    list.value = [];
    unreadTotal.value = 0;
    ElMessage.success("已全部标记为已读");
  }

  function goMore() {
    router.push({ name: "MyNotice" });
  }

  // ============================================
  // SSE 订阅（基于 userId 的动态 topic）
  // ============================================

  function setupSubscription() {
    if (!userId.value) {
      console.warn("[useNotice] userId 未就绪，跳过 SSE 订阅");
      return;
    }

    const noticeTopic = buildNoticeTopic(userId.value);
    const revokeTopic = buildRevokeTopic(userId.value);

    // 订阅新通知
    unsubNotice = on(noticeTopic, (data: any) => {
      try {
        if (!data?.id) return;

        // 去重
        if (list.value.some((item: NoticeItem) => item.id === data.id)) return;

        unreadTotal.value += 1;

        list.value.unshift({
          id: data.id,
          title: data.title,
          type: data.type,
          publishTime: data.publishTime,
        } as NoticeItem);

        // 保持列表长度不超过 PAGE_SIZE
        if (list.value.length > PAGE_SIZE) {
          list.value.length = PAGE_SIZE;
        }

        ElNotification({
          title: "您收到一条新的通知消息！",
          message: data.title,
          type: "success",
          position: "bottom-right",
        });
      } catch (e) {
        console.error("[useNotice] 解析通知消息失败", e);
      }
    });

    // 订阅通知撤回
    unsubRevoke = on(revokeTopic, (data: any) => {
      try {
        if (!data?.id) return;

        const idx = list.value.findIndex((item: NoticeItem) => item.id === data.id);
        if (idx >= 0) {
          list.value.splice(idx, 1);
          if (unreadTotal.value > 0) unreadTotal.value -= 1;
        }
      } catch (e) {
        console.error("[useNotice] 处理撤回通知失败", e);
      }
    });
  }

  // ============================================
  // 生命周期
  // ============================================

  onMounted(() => {
    fetchList();
    setupSubscription();
  });

  onBeforeUnmount(() => {
    unsubNotice?.();
    unsubRevoke?.();
    unsubNotice = null;
    unsubRevoke = null;
  });

  return {
    list,
    unreadTotal,
    detail,
    dialogVisible,
    fetchList,
    read,
    readAll,
    goMore
  };
}
