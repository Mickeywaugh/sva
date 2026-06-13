<template>
  <span>
    <i v-if="isVea" :style="{ color: color, fontSize: size }" :class="['icon iconfont', iconClass]" v-bind="$attrs"></i>
    <span v-else>
      <el-icon :color="color" :size="size">
        <component v-if="VeaIconCmp" :is="VeaIconCmp"></component>
      </el-icon>
    </span>
    <slot></slot>
  </span>
</template>

<script setup lang="ts">
  defineOptions({
    name: "VeaIcon",
  });
  const props = defineProps({
    iconClass: {
      type: String,
      required: false,
      default: "vea-icon",
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

  const isVea = ref<boolean>(props.iconClass?.startsWith("vea-"));
  const VeaIconCmp = ref("");
  watch(
    () => props.iconClass,
    (newIconClass: string) => {
      if (newIconClass === "") {
        return;
      }
      isVea.value = newIconClass.startsWith("vea-");
      VeaIconCmp.value = isVea.value ? "" : props.iconClass?.replace("ep-", "");
    }
  );
  onMounted(() => {
    isVea.value = props.iconClass?.startsWith("vea-");
    VeaIconCmp.value = isVea.value ? "" : props.iconClass?.replace("ep-", "");
  });
</script>
