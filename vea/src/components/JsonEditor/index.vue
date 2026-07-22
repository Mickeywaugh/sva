<template>
  <el-input v-model="inputValue" type="textarea" @change="(val: string) => handleChange(val)" :rows="rows" />
</template>

<script setup lang="ts">
  const props = defineProps<{
    modelValue: any;
    rows?: number;
  }>();

  const emit = defineEmits(["update:modelValue"]);
  const inputValue = ref<string>(props.modelValue ? JSON.stringify(props.modelValue, null, 2) : "");
  const rows = computed(() => props.rows || 12);
  const handleChange = (val: string) => {
    try {
      let jsonObj = JSON.parse(val.trim());
      emit("update:modelValue", jsonObj);
    } catch (e) {
      ElMessage.error("JSON格式不正确");
    }
  };
</script>