<template>
  <span>
    <i v-if="isGa" :style="{ color: color, fontSize: size }" :class="['icon iconfont', iconClass]" v-bind="$attrs"></i>
    <span v-else>
      <el-icon :color="color" :size="size">
        <component v-if="IconCmp" :is="IconCmp"></component>
      </el-icon>
    </span>
    <slot></slot>
  </span>
</template>

<script setup lang="ts">
  defineOptions({
    name: "GaIcon",
  });
  const props = defineProps({
    iconClass: {
      type: String,
      required: false,
      default: "ga-icon",
    },
    color: {
      type: String,
      default: "",
    },
    size: {
      type: String,
      default: "1rem",
    },
  });

  const isGa = ref<boolean>(props.iconClass?.startsWith("ga-"));
  const isEp = ref<boolean>(props.iconClass?.startsWith("ep-"));
  const IconCmp = ref("");
  watch(
    () => props.iconClass,
    (newIconClass) => {
      if (newIconClass === "") {
        return;
      }
      isGa.value = newIconClass.startsWith("ga-");
      isEp.value = newIconClass.startsWith("ep-");
      IconCmp.value = isEp.value ? props.iconClass?.replace("ep-", "") : "";
    }
  );
  onMounted(() => {
    isGa.value = props.iconClass?.startsWith("ga-");
    isEp.value = props.iconClass?.startsWith("ep-");
    IconCmp.value = isEp.value ? props.iconClass?.replace("ep-", "") : "";
  });
</script>
