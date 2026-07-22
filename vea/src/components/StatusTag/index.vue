<template>
  <el-tag v-bind="statusTag" :v-bind="props"><bt-icon v-if="statusTag.icon"
      :icon-class="statusTag.icon"></bt-icon>{{ t(statusTag.label) }}{{ extraLabel }}</el-tag>
</template>
<script setup lang="ts">
  import { StatusMap, StatusTag } from "@/enums/app";
  const { t } = useI18n();

  defineOptions({
    name: "StatusTagCmp",
  });

  const props = defineProps<{
    modelValue: number | null;
    map: StatusMap;
    extraLabel?: string | number;
    status?: number;
  }>();

  watch(() => props.modelValue, (val) => {
    if (val) {
      status.value = val;
      statusTag.value = props.map.get(status.value) ?? { type: "info", effect: "light", label: "Unknown" };
    }
  });
  const status = ref<number | null>(props.status ? props.status : props.modelValue);
  const statusTag = ref<StatusTag>(status.value ? props.map.get(status.value) ?? { type: "info", effect: "light", label: "Unknown" } : { type: "info", effect: "light", label: "Unknown" });
</script>