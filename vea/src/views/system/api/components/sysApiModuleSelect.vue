<template>
  <el-select v-model="module" clearable reserve-keyword @change="(val: any) => handleChange(val)" style="width: 160px">
    <el-option v-for="item in moduleOptions" :key="item.value" :label="item.label" :value="item.value" />
  </el-select>
</template>
<script lang="ts" setup>
  import SysApi from "@/api/system/api";

  defineOptions({
    name: "SysApiModuleSelectCmp",
  });

  const props = defineProps<{
    modelValue: string | undefined;
  }>();

  const module = ref(props.modelValue || '');
  const moduleOptions = reactive<OptionItem[]>([]);
  const moduleCache = ref<string>(module.value);

  const emit = defineEmits(["update:modelValue"]);

  const handleChange = (val: string) => {
    if (val !== moduleCache.value) {
      moduleCache.value = val;
    }
    emit("update:modelValue", val);
  };

  const getModuleOptions = () => {
    if (moduleOptions.length > 0) return;
    SysApi.getModuleOptions().then((data: any) => {
      moduleOptions.splice(0);
      Object.assign(moduleOptions, data);
    });
  }

  onBeforeMount(() => {
    getModuleOptions();
  });
</script>