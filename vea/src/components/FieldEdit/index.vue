<template>
  <div class="flex-inline gap-2">
    <div v-if="!editable">
      {{ inputValue }}
      <el-button type="primary" size="small" @click="showInput"><vea-icon icon-class="vea-o-edit" /></el-button>
    </div>
    <div v-else>
      <el-input-number v-if="isNumber" v-model="inputValue" :step="step" ref="InputRef" size="small" :style="inputStyle"
        @change="(val: any) => handleInputConfirm(val)" controls-position="right" @blur="editable = false" />
      <el-input v-else v-model="inputValue" ref="InputRef" :style="inputStyle" size="small"
        @change="(val: string) => handleInputConfirm(val)" />
    </div>
    <vea-icon v-if="tips" icon-class="vea-o-info" :title="tips" />
  </div>
</template>
<script lang="ts" setup>
  import { ref, watch, onMounted, nextTick } from 'vue';
  import { ElMessageBox } from 'element-plus';
  import { useI18n } from 'vue-i18n';

  const { t } = useI18n();

  defineOptions({
    name: "FieldEditCmp",
    inheritAttrs: true,
  });

  const props = defineProps<{
    modelValue: string | number | undefined;
    isNumber: boolean;
    step?: number;
    needConfirm?: boolean;
    tips?: string;
    width?: number;
  }>();

  const DEFAULT_WIDTH = 90;

  const inputValue = ref<any>(props.modelValue ?? '');
  const editable = ref(false);
  const InputRef = ref<HTMLInputElement>();
  const inputStyle = ref({
    width: `${props.width || DEFAULT_WIDTH}px!important`,
    textAlign: "right",
  });
  const emit = defineEmits(["update:modelValue"]);

  const showInput = () => {
    editable.value = true;
    nextTick(() => {
      if (InputRef.value) {
        InputRef.value.focus();
      }
    });
  };

  const handleInputConfirm = async (val: string | number | undefined) => {
    try {
      if (props.needConfirm) {
        await ElMessageBox.confirm(t("common.confirms.operation"), t("common.warning"), {
          confirmButtonText: t("common.confirm"),
          cancelButtonText: t("common.cancel"),
          type: "warning",
        });
        editable.value = false;
        inputValue.value = val;
        emit("update:modelValue", inputValue.value);
      } else {
        editable.value = false;
        inputValue.value = val;
        emit("update:modelValue", inputValue.value);
      }
    } catch (error) {
      inputValue.value = props.modelValue ?? '';
    }
  };

  watch(() => props.modelValue, (newVal) => {
    inputValue.value = newVal ?? '';
  });

  onMounted(() => {
    inputValue.value = props.modelValue ?? '';
  });

</script>

<style scoped>

  /* 使用深度选择器确保样式应用到输入框的内部元素 */
  ::v-deep(.el-input__inner),
  ::v-deep(.el-input-number__inner) {
    text-align: center !important;
  }
</style>