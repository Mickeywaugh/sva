import { useUserStore } from "./user";
import { useRouter } from "vue-router";

export const useMasStore = defineStore("mas", () => {
  const router = useRouter();
  const route = useRoute();
  const userStore = useUserStore();

  function loadMasStore() {}

  return {
    loadMasStore,
  };
});
