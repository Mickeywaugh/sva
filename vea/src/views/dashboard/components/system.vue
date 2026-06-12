<template v-bind="$attrs">
  <el-card>
    <template #header>
      <div class="flex justify-between">
        <div><vea-icon icon-class="vea-system" /></div>
        <div class="card-title w-full">{{ attrs.title }}</div>
        <div style="right: 14px;">
          <el-tag>在线:{{ onLineCount.onlineUserCount }}</el-tag>
        </div>
      </div>
    </template>
    <div :style="contanierStyle">

    </div>
  </el-card>

</template>

<script setup lang="ts">
  import { useOnlineCount } from "@/composables";

  defineOptions({
    name: "StaticsMonthlyYield",
  });

  const onLineCount = useOnlineCount();
  const props = defineProps<{
    maxHeight: string,
    attrs: {
      title: ""
    }
  }>();



  const contanierStyle = ref({
    height: props.maxHeight,
    width: "100%",
    minWidth: "25%",
    minHeight: "300px"
  });


  const { t } = useI18n();
  const loading = ref(false);
  const FullScreen = ref(false);

  onMounted(() => {

  });

</script>