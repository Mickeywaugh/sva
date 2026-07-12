<template>
  <div class="app-container">
    <el-card shadow="never" class="table-card">
      <div class="toolbar">
        <div class="left-toolbar">
          <el-button v-hasPerm="['sys:menu:add']" type="success" v-icon="'vea-o-plus'" @click="handleOpenDialog(0)">
            {{ $t("common.create") }}
          </el-button>
        </div>
        <div class="right-toolbar">
          <el-form ref="queryFormRef" :model="menuTableData.params" :inline="true">
            <el-form-item :label="t('common.keywords')" prop="keywords">
              <el-input v-model="menuTableData.params.keywords" placeholder="Menu Name" clearable @keyup.enter="handleQuery" />
            </el-form-item>
            <el-form-item>
              <el-button-group>
                <el-button type="primary" v-icon="'vea-search'" @click="handleQuery">{{ t('common.search') }}</el-button>
                <el-button v-icon="'vea-refresh'" @click="handleResetQuery">{{ t('common.reset') }}</el-button>
              </el-button-group>
            </el-form-item>
          </el-form>
        </div>
      </div>

      <el-table v-loading="loading" :data="menuTableData.tree" highlight-current-row row-key="id" fit :tree-props="{
        children: 'children',
        hasChildren: 'hasChildren',
      }" @row-click="handleRowClick">
        <el-table-column :label="t('sys.menu.name')" min-width="150" prop="title">
          <template v-slot="{ row }">
            <vea-icon :icon-class="row.icon">{{ t(row.title) }}</vea-icon>
          </template>
        </el-table-column>
        <el-table-column :label="t('sys.menu.type')" align="center" width="100">
          <template v-slot="{ row }">
            <el-tag v-if="row.type === MenuTypeEnum.CATALOG" type="warning">{{ t('sys.menu.catalog') }}</el-tag>
            <el-tag v-if="row.type === MenuTypeEnum.MENU" type="success">{{ t('sys.menu.node') }}</el-tag>
            <el-tag v-if="row.type === MenuTypeEnum.BUTTON" type="danger">{{ t('sys.menu.button') }}</el-tag>
            <el-tag v-if="row.type === MenuTypeEnum.EXTLINK" type="info">{{ t('sys.menu.extlink') }}</el-tag>
          </template>
        </el-table-column>
        <el-table-column :label="t('sys.menu.routeName')" align="left" prop="routeName" />
        <el-table-column :label="t('sys.menu.path')" align="left" prop="routePath" />
        <el-table-column :label="t('sys.menu.component')" align="left" width="250" prop="component" />
        <el-table-column :label="t('sys.menu.noAuth')" align="center">
          <template v-slot="{ row }">
            <el-switch v-if="row.type === MenuTypeEnum.MENU" v-model="row.noAuth" :active-value="1" :inactive-value="0" inline-prompt
              :active-text="t('common.yes')" :inactive-text="t('common.no')"
              :before-change="() => handleFieldChange(row, { noAuth: Number(!row.noAuth) })" />
          </template>
        </el-table-column>
        <el-table-column :label="t('common.visible')" align="center" width="80">
          <template v-slot="{ row }">
            <el-switch v-if="[MenuTypeEnum.MENU, MenuTypeEnum.CATALOG].includes(row.type)" v-model="row.visible" :active-value="1"
              :inactive-value="0" inline-prompt :active-text="t('common.show')" :inactive-text="t('common.hidden')"
              :before-change="() => handleFieldChange(row, { visible: Number(!row.visible) })" />
          </template>
        </el-table-column>
        <el-table-column :label="t('common.sort')" align="center" width="80" prop="sort" />
        <el-table-column fixed="right" align="center" :label="t('common.operation')" min-width="220">
          <template v-slot="{ row }">
            <el-button-group>
              <el-button v-if="[MenuTypeEnum.CATALOG, MenuTypeEnum.MENU].includes(row.type)" v-hasPerm="['sys:menu:add']" type="primary"
                size="small" @click.stop="handleOpenDialog(row.id)">
                <vea-icon icon-class="vea-o-plus" />{{ $t("common.create") }}
              </el-button>
              <el-button v-hasPerm="['sys:menu:edit']" type="primary" size="small" @click.stop="handleOpenDialog(undefined, row.id)">
                <vea-icon icon-class="vea-o-edit" />{{ $t("common.edit") }}
              </el-button>
              <el-button v-hasPerm="['sys:menu:delete']" type="warning" size="small" @click.stop="handleDelete(row.id)"><vea-icon
                  icon-class="vea-o-delete" />
                {{ $t("common.delete") }}
              </el-button>
            </el-button-group>
          </template>
        </el-table-column>
      </el-table>
    </el-card>

    <el-drawer v-model="dialog.visible" :title="dialog.title" size="50%" @close="handleCloseDialog">
      <el-form ref="menuFormRef" :model="dialog.formData" :rules="rules" label-width="100px">
        <el-form-item label="父级菜单" prop="parentId">
          <el-tree-select v-model="dialog.formData.parentId" placeholder="选择上级菜单" :data="menuOptions" filterable check-strictly
            :render-after-expand="false" />
        </el-form-item>

        <el-form-item :label="t('sys.menu.name')" prop="name">
          <el-input v-model="dialog.formData.name" :placeholder="t('common.please.input', { s: t('sys.menu.name') })" />
        </el-form-item>
        <el-form-item :label="t('sys.menu.tindex')" prop="name">
          <el-input v-model="dialog.formData.t" :placeholder="t('common.please.input', { s: t('sys.menu.tindex') })
            " />
        </el-form-item>
        <el-form-item :label="t('sys.menu.type')" prop="type">
          <el-radio-group v-model="dialog.formData.type" @change="handleMenuTypeChange">
            <el-radio-button :value="1">{{ $t("sys.menu.catalog") }}</el-radio-button>
            <el-radio-button :value="2">{{ $t("sys.menu.node") }}</el-radio-button>
            <el-radio-button :value="4">{{ $t("sys.menu.button") }}</el-radio-button>
            <el-radio-button :value="3">{{ $t("sys.menu.extlink") }}</el-radio-button>
          </el-radio-group>
        </el-form-item>

        <el-form-item v-if="dialog.formData.type == MenuTypeEnum.EXTLINK" :label="t('sys.menu.extLinkUrl')" prop="routePath">
          <el-input v-model="dialog.formData.routePath" :placeholder="t('common.please.input', { s: t('sys.menu.extLinkUrl') })" />
        </el-form-item>

        <el-form-item v-if="[MenuTypeEnum.CATALOG, MenuTypeEnum.MENU].includes(dialog.formData.type)" prop="routePath">
          <template #label>
            <div class="flex-y-center">
              {{ t("sys.menu.routePath") }}
              <el-tooltip placement="bottom" effect="light">
                <template #content>
                  定义应用中不同页面对应的 URL 路径，目录需以 / 开头，菜单项不用。例如：系统管理目录
                  /system，系统管理下的用户管理菜单 user。
                </template>
                <el-icon class="ml-1 cursor-pointer">
                  <QuestionFilled />
                </el-icon>
              </el-tooltip>
            </div>
          </template>
          <el-input v-if="dialog.formData.type == MenuTypeEnum.CATALOG" v-model="dialog.formData.routePath" placeholder="system" />
          <el-input v-else v-model="dialog.formData.routePath" placeholder="user" />
        </el-form-item>
        <el-form-item v-if="[MenuTypeEnum.CATALOG, MenuTypeEnum.MENU].includes(dialog.formData.type)" prop="routeName">
          <template #label>
            <div class="flex-y-center">
              {{ t("sys.menu.routeName") }}
              <el-tooltip placement="bottom" effect="light">
                <template #content>
                  路由名称是根据路由路径自动生成的，用于路由标识。以/分割后首字大写连接。Eg： system/user => SystemUser<br>
                  如果需要开启缓存，建议组件路径对应的页面中 defineOptions 的 name 与此处一致。
                </template>
                <el-icon class="ml-1 cursor-pointer">
                  <QuestionFilled />
                </el-icon>
              </el-tooltip>
            </div>
          </template>
          {{ dialog.formData.routeName }}
        </el-form-item>
        <el-form-item v-if="[MenuTypeEnum.MENU].includes(dialog.formData.type)" prop="component">
          <template #label>
            <div class="flex-y-center">
              {{ t("sys.menu.pagePath") }}
              <el-tooltip placement="bottom" effect="light">
                <template #content>
                  组件页面完整路径，相对于 src/views/，如 system/user/index，缺省后缀 .vue
                </template>
                <vea-icon icon-class="vea-o-question ml-1 cursor-pointer"></vea-icon>
              </el-tooltip>
            </div>
          </template>

          <el-input v-model="dialog.formData.component" placeholder="system/user/index" style="width: 95%">
            <template v-if="dialog.formData.type == MenuTypeEnum.MENU" #prepend>src/views/</template>
            <template v-if="dialog.formData.type == MenuTypeEnum.MENU" #append>.vue</template>
          </el-input>
        </el-form-item>

        <el-form-item v-if="dialog.formData.type == MenuTypeEnum.MENU">
          <template #label>
            <div class="flex-y-center">
              {{ t("sys.menu.routeParams") }}
              <el-tooltip placement="bottom" effect="light">
                <template #content>
                  组件页面使用 `useRoute().query.参数名` 获取路由参数值。
                </template>
                <vea-icon icon-class="vea-o-question ml-1 cursor-pointer"></vea-icon>
              </el-tooltip>
            </div>
          </template>

          <div v-if="!dialog.formData.params || dialog.formData.params.length === 0">
            <el-button type="success" plain @click="dialog.formData.params = [{ key: '', value: '' }]">
              {{ t("common.add") }}
            </el-button>
          </div>

          <div v-else>
            <div v-for="(item, index) in dialog.formData.params" :key="index">
              <el-input v-model="item.key" placeholder="Name" style="width: 100px" />
              <span class="mx-1">=</span>
              <el-input v-model="item.value" placeholder="Value" style="width: 100px" />
              <el-icon v-if="dialog.formData.params.indexOf(item) === dialog.formData.params.length - 1"
                class="ml-2 cursor-pointer color-[var(--el-color-success)]" style="vertical-align: -0.15em"
                @click="dialog.formData.params.push({ key: '', value: '' })">
                <CirclePlusFilled />
              </el-icon>
              <el-icon class="ml-2 cursor-pointer color-[var(--el-color-danger)]" style="vertical-align: -0.15em"
                @click="dialog.formData.params.splice(dialog.formData.params.indexOf(item), 1)">
                <DeleteFilled />
              </el-icon>
            </div>
          </div>
        </el-form-item>

        <el-form-item v-if="dialog.formData.type !== MenuTypeEnum.BUTTON" prop="visible" :label="t('common.visible')">
          <el-switch v-model="dialog.formData.visible" :active-value="1" :inactive-value="0" inline-prompt :active-text="t('common.show')"
            :inactive-text="t('common.hidden')" />
        </el-form-item>
        <el-form-item v-if="dialog.formData.type == MenuTypeEnum.MENU" prop="noAuth" :label="t('sys.menu.noAuth')">
          <el-switch v-model="dialog.formData.noAuth" :active-value="1" :inactive-value="0" inline-prompt :active-text="t('common.yes')"
            :inactive-text="t('common.no')" />
        </el-form-item>
        <el-form-item v-if="dialog.formData.type === MenuTypeEnum.CATALOG" :label="t('sys.menu.alwaysShowRoot')">
          <template #label>
            <div>
              {{ $t("sys.menu.alwaysShow") }}
              <el-tooltip placement="bottom" effect="light">
                <template #content>{{ $t("common.yes") }}：{{ $t("sys.menu.rootShowCatalog") }}
                  <br />{{ $t("common.no") }}：{{
                    t("sys.menu.rootShowRoute")
                  }}
                </template>

                <div>
                  <VeaIcon icon-class="vea-o-question-fill"></VeaIcon>
                </div>
              </el-tooltip>
            </div>
          </template>

          <el-switch v-model="dialog.formData.alwaysShow" :active-value="1" :inactive-value="0" inline-prompt :active-text="t('common.yes')"
            :inactive-text="t('common.no')" />
        </el-form-item>

        <el-form-item v-if="dialog.formData.type === MenuTypeEnum.MENU" :label="t('sys.menu.cacheOrNo')">
          <el-switch v-model="dialog.formData.keepAlive" :active-value="true" :inactive-value="false" inline-prompt
            :active-text="t('common.yes')" :inactive-text="t('common.no')" />
        </el-form-item>

        <el-form-item v-if="dialog.formData.type === MenuTypeEnum.MENU" :label="t('sys.menu.newWindow')">
          <el-switch v-model="dialog.formData.blank" :active-value="1" :inactive-value="0" inline-prompt :active-text="t('common.yes')"
            :inactive-text="t('common.no')" />
        </el-form-item>

        <el-form-item :label="t('common.sort')" prop="sort">
          <el-input-number v-model="dialog.formData.sort" style="width: 100px" controls-position="right" :min="0" />
        </el-form-item>

        <!-- 权限标识 -->
        <el-form-item v-if="dialog.formData.type == MenuTypeEnum.BUTTON" :label="t('sys.menu.perm')" prop="perm">
          <el-input v-model="dialog.formData.perm" placeholder="sys:user:add" />
        </el-form-item>

        <el-form-item v-if="dialog.formData.type !== MenuTypeEnum.BUTTON" :label="t('sys.menu.icon')" prop="icon">
          <!-- 图标选择器 -->
          <icon-select v-model="dialog.formData.icon" />
        </el-form-item>

        <el-form-item v-if="dialog.formData.type == MenuTypeEnum.CATALOG" :label="t('sys.menu.redirect')">
          <el-input v-model="dialog.formData.redirect" :placeholder="t('common.please.input', { s: t('sys.menu.redirect') })" />
        </el-form-item>
      </el-form>

      <template #footer>
        <div class="dialog-footer">
          <el-button type="primary" @click="handleSubmit">{{ t("common.submit") }}</el-button>
          <el-button @click="handleCloseDialog">{{ $t("common.cancel") }}</el-button>
        </div>
      </template>
    </el-drawer>
  </div>
</template>

<script setup lang="ts">
  defineOptions({
    name: "SystemMenu",
    inheritAttrs: false,
  });

  import MenuAPI, { MenuQuery, MenuForm, MenuItem } from "@/api/system/menu";
  import { MenuTypeEnum } from "@/enums/business";
  import { Menu } from "vxe-table";

  const queryFormRef = ref();
  const menuFormRef = ref();

  const t = useI18n().t;
  const loading = ref(false);

  // 初始菜单表单数据
  const initialMenuFormData = ref<MenuForm>({
    id: 0,
    t: '',
    parentId: 0,
    visible: 1,
    sort: 1,
    routeName: "",
    routePath: "",
    type: MenuTypeEnum.MENU, // 默认菜单
    alwaysShow: 0,
    keepAlive: true,
    params: [],
    noAuth: 0,
  });

  // 菜单表单
  const dialog = reactive({
    title: t('common.add'),
    visible: false,
    formData: { ...initialMenuFormData.value },
  });

  // 菜单表格数据查询参数
  const menuTableData = reactive({
    tree: [] as MenuItem[],
    params: {
      keywords: undefined
    }
  });
  // 顶级菜单下拉选项
  const menuOptions = ref<OptionItem[]>([]);

  // 表单验证规则
  const rules = reactive({
    parentId: [{ required: true, message: "请选择父级菜单", trigger: "blur" }],
    name: [{ required: true, message: "请输入菜单名称", trigger: "blur" }],
    type: [{ required: true, message: "请选择菜单类型", trigger: "blur" }],
    routePath: [{ required: true, message: "请输入路由路径", trigger: "blur" }],
    component: [{ required: true, message: "请输入组件路径", trigger: "blur" }],
    visible: [{ required: true, message: "请选择显示状态", trigger: "change" }],
  });

  // 选择表格的行菜单ID
  const selectedMenuId = ref<number | undefined>();

  // 查询菜单
  function handleQuery() {
    loading.value = true;
    MenuAPI.getTree(menuTableData.params)
      .then((data: any) => {
        Object.assign(menuTableData.tree, data);
      })
      .finally(() => {
        loading.value = false;
      });
  }

  // 重置查询
  function handleResetQuery() {
    queryFormRef.value.resetFields();
    handleQuery();
  }

  // 行点击事件
  function handleRowClick(row: MenuItem) {
    selectedMenuId.value = row.id;
  }

  /**
   * 打开表单弹窗
   *
   * @param parentId 父菜单ID
   * @param menuId 菜单ID
   */
  function handleOpenDialog(parentId?: number, menuId?: number) {
    MenuAPI.getOptions(true)
      .then((data: any) => {
        menuOptions.value = [{ value: 0, label: "顶级菜单", children: data }];
      })
      .then(() => {
        dialog.visible = true;
        if (menuId) {
          dialog.title = t('common.edit');
          MenuAPI.get(menuId).then((data: any) => {
            initialMenuFormData.value = { ...data };
            dialog.formData = data;
          });
        } else {
          dialog.title = t('common.add');
          dialog.formData.parentId = parentId;
        }
      });
  }

  // 菜单类型切换
  function handleMenuTypeChange() {
    // 如果菜单类型改变
    if (dialog.formData.type !== initialMenuFormData.value.type) {
      if (dialog.formData.type === MenuTypeEnum.MENU) {
        // 目录切换到菜单时，清空组件路径
        if (initialMenuFormData.value.type === MenuTypeEnum.CATALOG) {
          dialog.formData.component = "";
        } else {
          // 其他情况，保留原有的组件路径
          dialog.formData.routePath = initialMenuFormData.value.routePath;
          dialog.formData.component = initialMenuFormData.value.component;
        }
      }
    }
  }

  /**
   * 提交表单
   */
  function handleSubmit() {
    menuFormRef.value.validate((isValid: boolean) => {
      if (isValid) {
        const menuId = dialog.formData.id;
        if (menuId) {
          //修改时父级菜单不能为当前菜单
          if (dialog.formData.parentId == menuId) {
            ElMessage.error("父级菜单不能为当前菜单");
            return;
          }

        }
        MenuAPI.set(dialog.formData.id, dialog.formData).then(() => {
          ElMessage.success("Succeed");
          handleCloseDialog();
          handleQuery();
        });
      }
    });
  }

  // 删除菜单
  function handleDelete(menuId: number) {
    if (!menuId) {
      ElMessage.warning(t('common.please.selectDeleteItems'));
      return false;
    }

    ElMessageBox.confirm(t('common.confirms.deleteItems'), t('common.warning'), {
      confirmButtonText: t("common.confirm"),
      cancelButtonText: t('common.cancel'),
      type: "warning",
    }).then(
      () => {
        loading.value = true;
        MenuAPI.delete(menuId)
          .then(() => {
            ElMessage.success("Delete Succeed");
            handleQuery();
          }).catch((error) => {
            ElMessage.error(error.message);
          })
          .finally(() => {
            loading.value = false;
          });
      },
      () => {
        ElMessage.info("Deletion cancelled.");
      }
    );
  }

  function resetForm() {
    menuFormRef.value.resetFields();
    menuFormRef.value.clearValidate();
    dialog.formData = initialMenuFormData.value;
  }

  // 关闭弹窗
  function handleCloseDialog() {
    dialog.visible = false;
    resetForm();
  }


  const handleFieldChange = async (row: MenuItem, data: any): Promise<boolean> => {
    if (!row.id) return false;
    return ElMessageBox.confirm("确定更改吗?", "提示", {
      confirmButtonText: "确定",
      cancelButtonText: "取消",
      type: "warning",
    }).then(async () => {
      loading.value = true;
      return MenuAPI.set(row.id ?? 0, data)
        .then((response: any) => {
          Object.assign(row, response.data);
          ElMessage.success(t("common.succeed.update"));
          return true;
        })
        .catch((error: any) => {
          ElMessage.error(t("common.failed.update"));
          return false; // API 失败时阻止切换
        })
        .finally(() => {
          loading.value = false;
        });
    }).catch(() => {
      // 用户取消确认，阻止切换
      return false;
    });
  }

  onMounted(() => {
    handleQuery();
  });
</script>
