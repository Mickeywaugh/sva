# SymfonyVueAdmin

#### Description

A front-end and back-end separated admin template built on the SVA framework, powered by Symfony 8 + Vue 3 + Element-Plus, developed and maintained by Mickeywaugh.

#### Software Architecture

- **Front-end**: Vue 3 + Vite + TypeScript + Element-Plus, based on [vue3-element-admin](https://github.com/youlaitech/vue3-element-admin) (youlai)
- **Back-end**: Symfony 8.1 (PHP 8.1+)
- **Directory structure**:

```
sva/
├── vea/   # Front-end code
└── api/   # Back-end code
```

#### Installation

1. Clone the repository:
   ```bash
   git clone https://github.com/Mickeywaugh/sva.git
   ```
2. Install front-end dependencies:
   ```bash
   cd sva/vea && pnpm i
   ```
3. Install PHP dependencies:
   ```bash
   cd sva/api && composer install
   ```
4. Generate JWT keys:
   ```bash
   cd sva/api && php bin/console lexik:jwt:generate-keypair
   ```

#### Configuration

| File | Purpose |
|------|---------|
| `api/.env` | Symfony production environment (database connection, etc.) |
| `api/.env.dev` | Symfony development environment (database connection, etc.) |
| `vea/.env.development` | Vue development environment (back-end API base URL) |
| `vea/.env.production` | Vue production environment (back-end API base URL) |

#### Startup

**Development:**

```bash
# Start Symfony (HTTP)
cd sva/api && symfony server:start -d --listen=0.0.0.0:8000 --no-tls=true

# Start Vue dev server
cd sva/vea && pnpm run dev
```

**Production:**

```bash
# 1. Start Symfony (HTTPS)
cd sva/api && symfony server:start -d --listen=0.0.0.0:8000

# 2. Build front-end
cd sva/vea && sh build.sh

# 3. Deploy all files from dist/ to your web server (Nginx / Apache) document root
```
