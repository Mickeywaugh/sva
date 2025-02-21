<template>
  <div @click="toggle">
    <bt-icon :icon-class="isFullscreen ? 'bt-fullscreen-exit' : 'bt-fullscreen'" />
  </div>
</template>

<script setup lang="ts">
const { isFullscreen, toggle } = useFullscreen();
</script>

<style lang="scss" scoped></style>
