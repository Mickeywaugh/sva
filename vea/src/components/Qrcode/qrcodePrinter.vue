<template>
  <div>
    <el-button-group v-if="src">
      <el-button
        :title="$t('common.view', { s: $t('common.qrCode') })"
        @click="showQrcode = !showQrcode"
      >
        <bt-icon :icon-class="showQrcode ? 'bt-eye-close' : 'bt-eye-open'" />
      </el-button>
      <el-button
        :title="$t('common.print', { s: $t('common.qrCode') })"
        @click="handlePrintQrCode()"
      >
        <bt-icon icon-class="bt-printer-fill" />
      </el-button>
    </el-button-group>
    <div v-show="showQrcode" :id="containerRefName">
      <el-image :src="src" :fit="'fill'" style="width: 100px; height: 100px" />
    </div>
  </div>
</template>

<script setup lang="ts">
import { printQrCode } from "@/utils/function";

defineOptions({
  name: "QrCodePrinter",
});

const props = defineProps<{
  title: string;
  id?: number;
  src?: string;
}>();

const containerRefName = ref<string>(`qrCodeCntr${props.id}`);
const showQrcode = ref<boolean>(false);

const handlePrintQrCode = () => {
  // 确保DOM更新完毕后再访问ref
  nextTick(() => {
    if (!props.src) return;
    if (containerRefName.value && document.querySelector(`#${containerRefName.value}`)) {
      const container = document.querySelector(`#${containerRefName.value}`);
      var printContext = container ? container.innerHTML : "";
      printQrCode(props.title, printContext);
    }
  });
};

onMounted(() => {});
</script>
