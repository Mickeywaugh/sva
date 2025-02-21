<template>
  <div class="flex gap-2">
    <div v-if="!editable">
      {{ inputValue }}
      <el-button type="primary" size="small" @click="showInput">
        <bt-icon icon-class="bt-o-edit" />
      </el-button>
    </div>
    <div v-else>
      <el-input-number
        v-if="isNumber"
        ref="InputRef"
        v-model="inputValue"
        :step="step"
        size="small"
        style="width: 90px !important"
        controls-position="right"
        @change="(val) => handleInputConfirm(val)"
        @blur="editable = false"
      />
      <el-input
        v-else
        ref="InputRef"
        v-model="inputValue"
        style="width: 90px !important"
        size="small"
        @change="(val) => handleInputConfirm(val)"
      />
    </div>
  </div>
</template>
<script lang="ts" setup>
const { t } = useI18n();

defineOptions({
  name: "PmsFieldEditCmp",
});

const props = defineProps<{
  modelValue: any;
  isNumber: boolean;
  step?: number;
  needConfirm?: boolean;
}>();

const inputValue = ref<any>(props.modelValue);
const editable = ref(false);
const InputRef = ref<HTMLInputElement>();

const emit = defineEmits(["update:modelValue"]);

const showInput = () => {
  editable.value = true;
  nextTick(() => {
    InputRef.value?.focus();
  });
};

const handleInputConfirm = (val: any) => {
  if (props.needConfirm) {
    ElMessageBox.confirm(t("common.confirms.operation"), t("common.warning"), {
      confirmButtonText: t("common.confirm"),
      cancelButtonText: t("common.cancel"),
      type: "warning",
    })
      .then(async () => {
        editable.value = false;
        inputValue.value = val;
        emit("update:modelValue", inputValue.value);
      })
      .catch(() => {
        inputValue.value = props.modelValue;
        return;
      });
  } else {
    editable.value = false;
    inputValue.value = val;
    emit("update:modelValue", inputValue.value);
  }
};
onMounted(() => {});
</script>
