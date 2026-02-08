# syntax=docker/dockerfile:1

# -------------------------
# 1) Frontend build (Vite)
# -------------------------
FROM dhi.io/node:22-debian13-dev AS frontend
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci
COPY . .
RUN npm run build

# -------------------------
# 2) PHP deps (Composer)
# -------------------------
FROM dhi.io/composer:2-debian13-dev AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --prefer-dist --no-interaction --no-progress

# -------------------------
# 3) Runtime (PHP)
# -------------------------
FROM dhi.io/php:8.5.0-debian13-dev AS app
WORKDIR /app

# Cloud Run は $PORT を使う
ENV PORT=8080 \
    APP_ENV=production \
    APP_DEBUG=false \
    LOG_CHANNEL=stderr

# アプリ本体
COPY . .

# vendor / build成果物を注入
COPY --from=vendor /app/vendor ./vendor
COPY --from=frontend /app/public/build ./public/build

# SQLite（サンプルなので「永続しない」前提）
RUN mkdir -p database && \
    test -f database/database.sqlite || (touch database/database.sqlite)

# キャッシュ生成（好みで）
RUN php artisan config:cache && \
    php artisan route:cache || true

EXPOSE 8080

# 1プロセスでHTTP提供（Cloud Run向け）
CMD ["php", "-S", "0.0.0.0:8080", "-t", "public"]
