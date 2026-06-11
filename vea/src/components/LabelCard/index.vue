<template>
  <div class="flex w-full">
    <div class="w-1/5">{{ label }}{{ seperator }}</div>
    <div class="w-3/5">
      <slot></slot>
    </div>
    <div class="w-1/5">{{ tip }}</div>
  </div>
</template>

<script setup lang="ts">
  defineOptions({
    name: "LabelCardCmp",
    inheritAttrs: true
  });

  const props = withDefaults(defineProps<{
    label: string;
    seperator?: string;
    tip?: string;
  }>(), {
    seperator: ":",
    tip: ""
  });
</script>
