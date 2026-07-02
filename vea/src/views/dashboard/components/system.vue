<template v-bind="$attrs">
  <el-card>
    <template #header>
      <div class="flex justify-between">
        <div><bt-icon icon-class="bt-system" /></div>
        <div class="card-title w-full">{{ attrs.title }}</div>
        <div style="right: 14px;">
          <el-tag>在线:{{ onLineCount.onlineUserCount }}</el-tag>
        </div>
      </div>
    </template>
    <div :style="contanierStyle">
      <LabelCard label="Server">{{ cardData.serverInfo }}</LabelCard>
      <LabelCard label="PHP">{{ cardData.phpVersion }}</LabelCard>
      <LabelCard label="Vue3">{{ cardData.Vue3Version }}</LabelCard>
      <LabelCard label="Redis">{{ cardData.RedisVerion }}</LabelCard>
      <LabelCard label="Mysql">{{ cardData.MysqlVerion }}</LabelCard>
    </div>
  </el-card>

</template>

<script setup lang="ts">
  import { useOnlineCount } from "@/composables";
  import LabelCard from "@/components/LabelCard/index.vue";
  import DashboardAPI from "@/api/dashboard";
  defineOptions({
    name: "DashboardSystemCard",
  });

  const onLineCount = useOnlineCount();
  const props = defineProps<{
    maxHeight: string,
    attrs: {
      title: ""
    }
  }>();


  const cardData = reactive({
    serverInfo: "127.0.0.1:8080",
    phpVersion: "8.5",
    Vue3Version: "3.5.34",
    RedisVerion: "12.10.0",
    MysqlVerion: "9.0.0"
  })

  const contanierStyle = ref({
    height: props.maxHeight,
    width: "100%",
    minWidth: "25%",
    minHeight: "300px"
  });

  const handleQuery = async () => {
    await DashboardAPI.systemInfo().then((data: any) => {
      Object.assign(cardData, data);
    }).catch((err: any) => {
      console.log(err);
    });
  }

  onMounted(() => {
    handleQuery();
  });

</script>