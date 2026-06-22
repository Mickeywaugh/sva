<template>
  <div class="about-me">
    <h1>Mickey's Coding Histories.</h1>
    <p class="subtitle">一路走来，每一步都算数！</p>
    <div class="repo-links">
      <a href="https://github.com/mickeywaugh" target="_blank" rel="noopener noreferrer">
        <svg class="icon" viewBox="0 0 24 24" fill="currentColor">
          <path
            d="M12 0C5.37 0 0 5.37 0 12c0 5.31 3.435 9.795 8.205 11.385.6.105.825-.255.825-.57 0-.285-.015-1.23-.015-2.235-3.015.555-3.795-.735-4.035-1.41-.135-.345-.72-1.41-1.23-1.695-.42-.225-1.02-.78-.015-.795.945-.015 1.62.87 1.845 1.23 1.08 1.815 2.805 1.305 3.495.99.105-.78.42-1.305.765-1.605-2.67-.3-5.46-1.335-5.46-5.925 0-1.305.465-2.385 1.23-3.225-.12-.3-.54-1.53.12-3.18 0 0 1.005-.315 3.3 1.23.96-.27 1.98-.405 3-.405s2.04.135 3 .405c2.295-1.56 3.3-1.23 3.3-1.23.66 1.65.24 2.88.12 3.18.765.84 1.23 1.905 1.23 3.225 0 4.605-2.805 5.625-5.475 5.925.435.375.81 1.095.81 2.22 0 1.605-.015 2.895-.015 3.3 0 .315.225.69.825.57A12.02 12.02 0 0024 12c0-6.63-5.37-12-12-12z" />
        </svg>
        GitHub
      </a>
      <a href="https://gitee.com/mickeywaugh" target="_blank" rel="noopener noreferrer">
        <svg class="icon" viewBox="0 0 24 24" fill="currentColor">
          <path
            d="M11.984 0A12 12 0 000 12a12 12 0 0012 12 12 12 0 0012-12A12 12 0 0011.984 0zM8.578 6.563h2.297v2.297H8.578V6.563zm4.688 0H19.5v2.297h-6.234V6.563zm-4.688 4.687h2.297v2.297H8.578v-2.297zm4.688 0H19.5v2.297h-6.234v-2.297zm-4.688 4.688h2.297v2.297H8.578v-2.297zm4.688 0H19.5v2.297h-6.234v-2.297z" />
        </svg>
        Gitee
      </a>
    </div>
    <el-timeline>
      <el-timeline-item v-for="item in timelineData" :key="item.period" :timestamp="item.period" placement="top" color="#676B6F" size="large">
        <el-card shadow="hover" class="timeline-card">
          <template #header>
            <h4>{{ item.company }}</h4>
          </template>
          <ul>
            <li class="tmli" v-for="(point, idx) in item.points" :key="idx">{{ point }}</li>
          </ul>
        </el-card>
      </el-timeline-item>
    </el-timeline>
  </div>
</template>

<script setup lang="ts">
  defineOptions({
    name: "AboutMe",
    inheritAttrs: false,
  });
  const t = useI18n();

  const timelineData = [

    {
      period: '2024-02 ~ Now',
      company: '博应信息技术有限公司 (BoingTech Inc.)',
      points: [
        '负责公司核心产品数据管理系统的全面架构设计，确保系统的高效性与扩展性。',
        '独立完成 EPC 编码解码基础类的开发，显著提升了开发团队的工作效率。',
        '开发专用的 PDF 基础服务类，优化了标签打印与排版流程。',
        '针对现有的几个项目性能进行深度优化，减少代码量和执行效率，提升用户体验。',
        '推动公司开发环境和生产环境分离，各项目 APP 系统 Docker 化部署。',
      ],
    },
    {
      period: '2020-12 ~ 2023-10',
      company: '川源信息技术有限公司 (AccelorStor Inc.)',
      points: [
        '负责公司基于 Linux + Nginx + (XML, SQLite3) + PHP 模式的产品用户界面前后端开发。',
        '独立完成公司产品运行状态动态面板视图功能的技术方案、结构设计和开发实现（仿真）。',
        '独立完成公司产品基于 OSS 的云存储功能的设计及开发。',
        '积极推动和实现了产品 UI 的升级，将 UI 中的图片升级为图标字体，提高产品 UI 质量水平。',
      ],
    },
    {
      period: '2018-06 ~ 2020-10',
      company: '上飞飞机装备制造有限公司 (SAEM Inc.)',
      points: [
        '开启公司信息化规划和建设之路。',
        '带领团队完成公司OA重启运营，ERP软件项目从选型、采购、部署、上线实施、后续运维及二次开发等工作。',
        '建设公司门户网站及公众号运营。',
        '建立起公司IT基础设施管理环境，实现总公司和分公司之间的VPN互通。',
      ],
    },
  ];
</script>

<style lang="scss" scoped>
.about-me {
  max-width: 800px;
  margin: 0 auto;
  padding: 40px 20px;

  h1 {
    text-align: center;
    font-size: 28px;
    color: #666B76;
    margin-bottom: 8px;
  }

  .subtitle {
    text-align: center;
    color: #909399;
    font-size: 16px;
    margin-bottom: 24px;
  }

  .repo-links {
    display: flex;
    justify-content: center;
    gap: 24px;
    margin-bottom: 40px;

    a {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 8px 20px;
      border-radius: 6px;
      font-size: 14px;
      color: #fff;
      text-decoration: none;
      transition: opacity 0.2s ease;

      &:first-child {
        background: #24292e;
      }

      &:last-child {
        background: #c71d23;
      }

      &:hover {
        opacity: 0.85;
      }
    }

    .icon {
      width: 18px;
      height: 18px;
    }
  }

  .el-timeline-item .is-start ::before {
    display: none;
  }

  .timeline-card {
    border-radius: 8px;
    transition: transform 0.2s ease;

    &:hover {
      transform: translateY(-2px);
    }

    :deep(.el-card__header) {
      padding: 12px 16px;
      background: linear-gradient(135deg, #409eff10, #409eff05);
      border-bottom: 1px solid #ebeef5;

      h4 {
        margin: 0;
        font-size: 16px;
        color: #878A8F;
      }
    }

    :deep(.el-card__body) {
      padding: 12px 16px;
    }
  }

  ul {
    margin: 0;
    padding-left: 0;
    list-style: none;
    counter-reset: timeline;
  }

  .tmli {
    position: relative;
    padding-left: 32px;
    line-height: 1.8;
    color: #606266;
    font-size: 14px;
    counter-increment: timeline;

    &::before {
      content: counter(timeline);
      position: absolute;
      left: 0;
      top: 0;
      width: 20px;
      height: 20px;
      line-height: 20px;
      text-align: center;
      font-size: 12px;
      font-weight: 600;
      color: #fff;
      background: #409eff;
      border-radius: 50%;
    }
  }

}
</style>