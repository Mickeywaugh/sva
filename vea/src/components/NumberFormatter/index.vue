<template>
  <span>{{ formattedNumber }}</span>
</template>

<script setup lang="ts">
defineOptions({
  name: "NumberFormatter",
  inheritAttrs: false,
});

const props = defineProps<{
  modelValue: number | undefined;
}>();

const formattedNumber = computed(() => {
  if (!props.modelValue) {
    return "";
  } else {
    return props.modelValue.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
  }
});
</script>
