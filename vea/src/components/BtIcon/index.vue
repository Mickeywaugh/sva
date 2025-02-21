<template>
  <span>
    <i
      v-if="isBt"
      :style="{ color }"
      :size="size"
      :class="['icon iconfont', iconClass]"
      v-bind="$attrs"
    ></i>
    <span v-else>
      <el-icon :color="color" :size="size">
        <component v-if="BtIconCmp" :is="BtIconCmp"></component>
      </el-icon>
    </span>
    <slot></slot>
  </span>
</template>

<script setup lang="ts">
defineOptions({
  name: "BtIcon",
  inheritAttrs: true,
});
const props = defineProps({
  iconClass: {
    type: String,
    required: false,
    default: "bt-icon",
  },
  color: {
    type: String,
    default: "",
  },
  size: {
    type: String,
    default: "1em",
  },
});

const isBt = ref<boolean>(props.iconClass.startsWith("bt-"));
const BtIconCmp = ref("");
watch(
  () => props.iconClass,
  (newIconClass) => {
    isBt.value = newIconClass.startsWith("bt-");
    BtIconCmp.value = isBt.value ? "" : props.iconClass.replace("ep-", "");
  }
);
onMounted(() => {
  isBt.value = props.iconClass.startsWith("bt-");
  BtIconCmp.value = isBt.value ? "" : props.iconClass.replace("ep-", "");
});
</script>

<style scoped>
.icon-with-text {
  display: inline-flex;
  align-items: center;
}
.text {
  margin-left: 4px;
}
</style>
