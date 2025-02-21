# SymfonyVueAdmin

#### 介绍
基于SVA框架开发，Symfony Vue3 Admin是由Mickeywaugh开发的后台管理框架；

#### 软件架构
软件架构说明
本后台管理项目分为前后端分离，前端使用vue3-element-admin(有来技术)框架，后端使用Symfony7.2(php8)。
前端代码在vea目录
后端代码在api目录

#### 安装教程

1.  安装： git clone https://github.com/Mickeywaugh/sva.git
2.  cd sva/vue后执行npm install 安装前端依赖
3.  cd sva/api后执行composer install 安装PHP相关依赖
4.  cd sva/api后执行php bin/console lexik:jwt:generate-keypair 创建JWT密钥

#### 使用说明
1.  api/.env 为symfony框架生产环境配置文件，请修改数据库连接信息
2.  api/.env.dev 为symfony框架开发环境配置文件，请修改数据库连接信息
3.  vea/.env.development 为vue框架开发环境配置文件，请修改后端接口地址
4.  vaa/.env.production 为vue框架生产环境配置文件，请修改后端接口地址
