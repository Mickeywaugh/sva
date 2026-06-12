<template>
  <span>
    <i v-if="is" :style="{ color: color, fontSize: size }" :class="['icon iconfont', iconClass]" v-bind="$attrs"></i>
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
    name: "Icon",
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

  const is = ref<boolean>(props.iconClass?.startsWith("vea-"));
  const isEp = ref<boolean>(props.iconClass?.startsWith("ep-"));
  const IconCmp = ref("");
  watch(
    () => props.iconClass,
    (newIconClass:string) => {
      if (newIconClass === "") {
        return;
      }
      is.value = newIconClass.startsWith("vea-");
      isEp.value = newIconClass.startsWith("ep-");
      IconCmp.value = isEp.value ? props.iconClass?.replace("ep-", "") : "";
    }
  );
  onMounted(() => {
    is.value = props.iconClass?.startsWith("vea-");
    isEp.value = props.iconClass?.startsWith("ep-");
    IconCmp.value = isEp.value ? props.iconClass?.replace("ep-", "") : "";
  });
</script>
