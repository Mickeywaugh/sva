<template>
  <div class="fullscreen-trigger" @click="toggle">
    <div>
      <ga-icon :icon-class="(isFullscreen ? 'bt-fullscreen-exit' : 'bt-fullscreen')" />
    </div>
  </div>
</template>

<script setup lang="ts">
  const { isFullscreen, toggle } = useFullscreen();
</script>

<style lang="scss" scoped>
  .fullscreen-trigger {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 100%;
    height: 100%;
  }
</style>
