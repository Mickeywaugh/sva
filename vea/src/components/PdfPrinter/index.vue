<template>
  <el-dialog :title="title" width="300px">
    <div v-if="pdfUrl">
      <iframe :src="pdfUrl" width="100%" height="500px" />
    </div>
    <template #footer>
      <span class="dialog-footer">
        <el-button type="primary" @click="handlePrint">打 印</el-button>
        <el-button @click="">取 消</el-button>
      </span>
    </template>
  </el-dialog>
</template>
<script lang="ts" setup>
defineOptions({
  name: "PdfPrinterCmp",
});

const props = defineProps<{
  modelValue: number;
  pdfBlob: Blob;
  title?: string;
  pdfUrl?: string;
}>();

const emit = defineEmits(["update:modelValue"]);

const handlePrint = () => {
  // 确保DOM更新完毕后再访问ref
};

onMounted(() => {});
</script>
