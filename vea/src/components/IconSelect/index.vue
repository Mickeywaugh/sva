<template>
  <div ref="iconSelectRef" :style="{ width: props.width }">
    <el-popover :visible="popoverVisible" :width="width" placement="bottom-end">
      <template #reference>
        <el-input v-model="selectedIcon" class="reference" readonly placeholder="点击选择图标" @click="popoverVisible = !popoverVisible">
          <template #prepend>
            <vea-icon v-if="selectedIcon" :icon-class="selectedIcon" />
          </template>
          <template #suffix>
            <el-icon :style="{
              transform: popoverVisible ? 'rotate(180deg)' : 'rotate(0)',
              transition: 'transform .5s',
            }" @click="popoverVisible = !popoverVisible">
              <ArrowDown />
            </el-icon>
          </template>
        </el-input>
      </template>

      <!-- 下拉选择弹窗 -->
      <div ref="popoverContentRef">
        <el-input v-model="searchText" placeholder="搜索图标" clearable @input="(val: string) => handleSearch(val)" />
        <el-tabs v-model="activeTab">
          <el-tab-pane label="VEA 图标" name="vea">
            <el-scrollbar height="300px">
              <ul class="icon-container">
                <li v-for="icon in filteredIcons" :key="icon" class="icon-item" @click="selectIcon(icon)">
                  <el-tooltip :content="icon" placement="bottom" effect="light">
                    <vea-icon :icon-class="icon" />
                  </el-tooltip>
                </li>
              </ul>
            </el-scrollbar>
          </el-tab-pane>
          <el-tab-pane label="Element 图标" name="element">
            <el-scrollbar height="300px">
              <ul class="icon-container">
                <li v-for="icon in filteredIcons" :key="icon" class="icon-item" @click="selectIcon(icon)">
                  <el-icon>
                    <component :is="icon" />
                  </el-icon>
                </li>
              </ul>
            </el-scrollbar>
          </el-tab-pane>
        </el-tabs>
      </div>
    </el-popover>
  </div>
</template>

<script setup lang="ts">
  import * as ElementPlusIconsVue from "@element-plus/icons-vue";
  import IconStore from "@/assets/iconfont/iconfont.json";
  const props = defineProps({
    modelValue: {
      type: String,
      require: false,
      default: "",
    },
    width: {
      type: String,
      require: false,
      default: "500px",
    },
  });

  const { t } = useI18n();
  const emit = defineEmits(["update:modelValue"]);
  const selectedIcon = toRef(props, "modelValue");

  const activeTab = ref("vea"); // 默认激活的Tab
  const searchText = ref(""); // 筛选的值
  const popoverVisible = ref(false); // 弹窗显示状态

  const epIcons: string[] = Object.keys(ElementPlusIconsVue); // Element Plus图标集合
  const VeaIconsTemp: object[] = Object.values(IconStore.glyphs); //
  const VeaIcons: string[] = [];
  const currIcons = ref<string[]>([]); //当前选项卡下的图标集合
  const filteredIcons = ref<string[]>([]);
  onMounted(() => {
    VeaIconsTemp.forEach((icon: any) => {
      VeaIcons.push(`vea-${icon.font_class}`);
    });
    currIcons.value = VeaIcons;
  });

  /**
   * icon 筛选
   */
  function handleSearch(searchText: string) {
    if (searchText) {
      filteredIcons.value = currIcons.value.filter((iconName:string) =>
        iconName.toLowerCase().includes(searchText.toLowerCase())
      );
    } else {
      currIcons.value = activeTab.value === "vea" ? VeaIcons : epIcons;
    }
  }

  //切换tab，将当前选项卡下的图标库赋值给currIcons
  watch(activeTab, (newVal:string) => {
    activeTab.value = newVal;
    currIcons.value = [];
    currIcons.value = filteredIcons.value = newVal === "vea" ? VeaIcons : epIcons;
  });

  /**
   * 选择图标
   */
  function selectIcon(iconName: string) {
    if (activeTab.value === "element") {
      iconName = `ep-${iconName}`;
    }
    emit("update:modelValue", iconName);
    popoverVisible.value = false;
  }
</script>

<style scoped lang="scss">

  .reference :deep(.el-input__wrapper),
  .reference :deep(.el-input__inner) {
    cursor: pointer;
  }

  .icon-container {
    display: flex;
    flex-wrap: wrap;

    .icon-item {
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 8px;
      margin: 4px;
      cursor: pointer;
      border: 1px solid #dcdfe6;
      border-radius: 4px;
      transition: all 0.3s;
    }

    .icon-item:hover {
      border-color: #409eff;
      scale: 1.2;
    }
  }
</style>
