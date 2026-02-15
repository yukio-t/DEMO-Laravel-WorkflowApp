# syntax=docker/dockerfile:1

# -------------------------
# 1) Frontend build (Vite)
# -------------------------
FROM dhi.io/node:22-debian13-dev AS frontend
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci
COPY resources ./resources
COPY vite.config.* ./
COPY public ./public
# 依存によっては以下が必要：tailwind.config / postcss.config 等
COPY tailwind.config.* postcss.config.* ./
RUN npm run build

# -------------------------
# 2) PHP deps (Composer)
# -------------------------
FROM dhi.io/composer:2-debian13-dev AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
# scripts は app 本体無しで壊れることがあるため安全側で抑止
RUN composer install \
  --no-dev --prefer-dist --no-interaction --no-progress --no-scripts

# -------------------------
# 3) Build stage (artisan cache etc.)
#   ※ここでRUNを完結させ、prodはCOPYだけに寄せる
# -------------------------
FROM dhi.io/php:8.5.0-debian13-dev AS build
WORKDIR /app

ENV APP_ENV=production \
    APP_DEBUG=false \
    LOG_CHANNEL=stderr \
    DB_CONNECTION=sqlite

# 先に依存を入れる（キャッシュ効率）
COPY --from=vendor /app/vendor ./vendor
COPY --from=frontend /app/public/build ./public/build

# アプリ本体（不要物は .dockerignore 前提）
COPY . .

# SQLite（サンプルなので永続しない前提）
RUN mkdir -p database storage bootstrap/cache && \
    test -f database/database.sqlite || touch database/database.sqlite

# キャッシュ生成（route:cache は “隠れ失敗” を生みやすいので今回は外す）
RUN php artisan config:cache

# -------------------------
# 4) Dev runtime (local)
# -------------------------
FROM dhi.io/php:8.5.0-debian13-dev AS dev
WORKDIR /app
ENV APP_ENV=local \
    APP_DEBUG=true \
    LOG_CHANNEL=stderr \
    DB_CONNECTION=sqlite
COPY --from=build /app /app
EXPOSE 8080
CMD ["php", "-S", "0.0.0.0:8080", "-t", "public"]

# -------------------------
# 5) Prod runtime (Cloud Run)
#   ★ non-dev（シェル無し想定）: RUNしない / COPYのみ
# -------------------------
FROM dhi.io/php:8.5.0-debian13 AS prod
WORKDIR /app
ENV APP_ENV=production \
    APP_DEBUG=false \
    LOG_CHANNEL=stderr \
    DB_CONNECTION=sqlite
COPY --from=build /app /app
EXPOSE 8080
# Cloud Run は通常 8080 なので固定（$PORT展開はnon-devだと難しいため）
CMD ["php", "-S", "0.0.0.0:8080", "-t", "public"]
