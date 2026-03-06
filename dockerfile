# syntax=docker/dockerfile:1

# =========================================================
# Local (works everywhere): Official images
# =========================================================

# Composer deps (LOCAL)
FROM composer:2 AS vendor_local
WORKDIR /app
COPY src/composer.json src/composer.lock ./
# ローカルは dev パッケージも必要（Pail 等）
RUN composer install --prefer-dist --no-interaction --no-progress --no-scripts

# Build (LOCAL) - minimal
FROM php:8.4-cli-bookworm AS build_local
WORKDIR /app

ENV APP_ENV=production \
    APP_DEBUG=false \
    LOG_CHANNEL=stderr \
    DB_CONNECTION=sqlite

COPY --from=vendor_local /app/vendor ./vendor
COPY src/ ./

# Laravel が要求するディレクトリを必ず作る（空ディレクトリがCOPYされない問題対策）
RUN mkdir -p \
      storage/framework/cache \
      storage/framework/sessions \
      storage/framework/views \
      storage/logs \
      bootstrap/cache \
      database

EXPOSE 8080

# Dev runtime (LOCAL)
FROM php:8.4-cli-bookworm AS dev-local
WORKDIR /app

ENV APP_ENV=local \
    APP_DEBUG=true \
    LOG_CHANNEL=stderr \
    DB_CONNECTION=sqlite

COPY --from=build_local /app /app

# ※ .env はイメージに含めない想定。docker run の --env-file で渡す。
CMD ["php", "-S", "0.0.0.0:8080", "-t", "public"]


# =========================================================
# (Optional) Frontend build targets
#  - Tailwind/Vite 導入後に使う（package-lock.json がある前提）
# =========================================================

FROM node:22-bookworm AS frontend_local
WORKDIR /work
COPY src/package.json src/package-lock.json ./
RUN npm ci
COPY src/resources ./resources
COPY src/vite.config.* ./
COPY src/public ./public
# Tailwind導入後に有効化（ファイルが無ければ削除）
# COPY src/tailwind.config.* src/postcss.config.* ./
RUN npm run build

FROM dev-local AS dev-local-assets
COPY --from=frontend_local /work/public/build /app/public/build


# =========================================================
# Prod build (deps): vendor without dev
#  - prod 実行イメージ(dhi)には composer が無い想定なので、
#    依存解決は別ステージで完結させて COPY する
# =========================================================

FROM composer:2 AS vendor_prod
WORKDIR /app
COPY src/composer.json src/composer.lock ./
RUN composer install \
    --no-dev \
    --prefer-dist \
    --no-interaction \
    --no-progress \
    --no-scripts \
    --optimize-autoloader

FROM php:8.4-cli-bookworm AS build_prod
WORKDIR /app

ENV APP_ENV=production \
    APP_DEBUG=false \
    LOG_CHANNEL=stderr \
    DB_CONNECTION=sqlite

COPY --from=vendor_prod /app/vendor ./vendor
COPY src/ ./

RUN mkdir -p \
      storage/framework/cache \
      storage/framework/sessions \
      storage/framework/views \
      storage/logs \
      bootstrap/cache \
      database


# =========================================================
# Prod (Cloud Run): DHI non-dev
# ※ローカル実行はしない前提（DHI PHP をローカルで動かせない環境があるため）
# =========================================================

FROM dhi.io/php:8.5.0-debian13 AS prod
WORKDIR /app

ENV APP_ENV=production \
    APP_DEBUG=false \
    LOG_CHANNEL=stderr \
    DB_CONNECTION=sqlite

COPY --from=build_prod /app /app

EXPOSE 8080
CMD ["php", "-S", "0.0.0.0:8080", "-t", "public"]