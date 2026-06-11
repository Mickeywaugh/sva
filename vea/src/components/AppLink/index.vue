<template>
  <component :is="linkType" v-bind="linkProps(to)">
    <slot />
  </component>
</template>

<script setup lang="ts">
  defineOptions({
    name: "AppLink",
    inheritAttrs: false
  });

  import { isExternal } from "@/utils/index";

  const props = defineProps({
    to: {
      type: Object,
      required: true,
    },
    blank: {
      type: Number,
      default: 0
    }
  });

  const isExternalLink = computed(() => {
    return isExternal(props.to.path || "");
  });

  const linkType = computed(() => (isExternalLink.value ? "a" : "router-link"));

  const linkProps = (to: Object) => {
    return isExternalLink.value
      ? { href: to, target: "_blank", rel: "noopener noreferrer" }
      : props.blank ? { to, target: "_blank" } : { to };
  };

</script>
