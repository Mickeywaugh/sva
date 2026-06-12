# SymfonyVueAdmin

#### 介绍

基于 SVA 框架开发，Symfony 8 + Vue 3 + Element-Plus 前后端分离式后台管理模板，由 Mickeywaugh 开发维护。

#### 软件架构

- **前端**：Vue 3 + Vite + TypeScript + Element-Plus，基于 [vue3-element-admin](https://github.com/youlaitech/vue3-element-admin)（有来技术）
- **后端**：Symfony 8.1（PHP 8.1+）
- **目录结构**：

```
sva/
├── vea/   # 前端代码
└── api/   # 后端代码
```

#### 安装教程

1. 克隆仓库：
   ```bash
   git clone https://github.com/Mickeywaugh/sva.git
   ```
2. 安装前端依赖：
   ```bash
   cd sva/vea && pnpm i
   ```
3. 安装 PHP 依赖：
   ```bash
   cd sva/api && composer install
   ```
4. 生成 JWT 密钥：
   ```bash
   cd sva/api && php bin/console lexik:jwt:generate-keypair
   ```

#### 配置说明

| 配置文件 | 用途 |
|---------|------|
| `api/.env` | Symfony 生产环境配置（数据库连接等） |
| `api/.env.dev` | Symfony 开发环境配置（数据库连接等） |
| `vea/.env.development` | Vue 开发环境配置（后端接口地址） |
| `vea/.env.production` | Vue 生产环境配置（后端接口地址） |

#### 启动方式

**开发环境：**

```bash
# 启动 Symfony（HTTP 协议）
cd sva/api && symfony server:start -d --listen=0.0.0.0:8000 --no-tls=true

# 启动 Vue 开发服务器
cd sva/vea && pnpm run dev
```

**生产环境：**

```bash
# 1. 启动 Symfony（HTTPS 协议）
cd sva/api && symfony server:start -d --listen=0.0.0.0:8000

# 2. 编译前端代码
cd sva/vea && sh build.sh

# 3. 将 dist/ 目录下所有文件部署到 Web 服务器（Nginx / Apache）站点目录
```
