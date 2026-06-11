<template>
  <el-tag v-bind="statusTag" :v-bind="props"><ga-icon v-if="statusTag.icon"
      :icon-class="statusTag.icon"></ga-icon>{{ t(statusTag.label) }}{{ extraLabel }}</el-tag>
</template>
<script setup lang="ts">
  import { StatusMap, StatusTag } from "@/enums/app";
  const { t } = useI18n();

  defineOptions({
    name: "StatusTagCmp",
  });

  const props = defineProps<{
    modelValue: number;
    map: StatusMap;
    extraLabel?: string | number;
    status?: number;
  }>();

  watch(() => props.modelValue, (val) => {
    status.value = val;
    statusTag.value = props.map.get(status.value) ?? { type: "info", effect: "light", label: "Unknown" };
  });
  const status = ref<number>(props.status ? props.status : props.modelValue);
  const statusTag = ref<StatusTag>(props.map.get(status.value) ?? { type: "info", effect: "light", label: "Unknown" });
</script>