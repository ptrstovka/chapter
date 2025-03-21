FROM composer:latest AS vendor
WORKDIR /var/www/html
COPY composer* ./
RUN composer install \
  --no-dev \
  --no-interaction \
  --prefer-dist \
  --ignore-platform-reqs \
  --optimize-autoloader \
  --apcu-autoloader \
  --ansi \
  --no-scripts \
  --audit

FROM node:20 AS nodemodules
WORKDIR /var/www/html
COPY . .
COPY --from=vendor /var/www/html/vendor vendor
ENV VITE_APP_NAME=Chapter
RUN npm install --ci --no-audit && npm run build

FROM serversideup/php:8.4-fpm-nginx

USER root

COPY docker/s6-overlay/s6-rc.d/queue /etc/s6-overlay/s6-rc.d/queue

RUN apt-get update && apt-get install -y --no-install-recommends \
    ffmpeg \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* \
    && mkdir -p /data/public \
    && mkdir -p /data/private \
    && chown -R www-data /data \
    && install-php-extensions bcmath intl \
    && touch /etc/s6-overlay/s6-rc.d/user/contents.d/queue

USER www-data

COPY --chown=www-data:www-data . .
COPY --chown=www-data:www-data --from=vendor /var/www/html/vendor vendor
COPY --chown=www-data:www-data --from=nodemodules /var/www/html/public/build public/build
RUN rm composer.lock package-lock.json vite.config.ts tsconfig.json tailwind.config.js postcss.config.js .dockerignore
ENV APP_DEBUG=false
ENV APP_LOCALE=en
ENV APP_FALLBACK_LOCALE=en
ENV APP_MAINTENANCE_DRIVER=file
ENV BCRYPT_ROUNDS=12
ENV LOG_CHANNEL=stderr
ENV LOG_STACK=single
ENV LOG_DEPRECATIONS_CHANNEL=null
ENV LOG_LEVEL=debug
ENV DB_CONNECTION=sqlite
ENV DB_DATABASE=/data/database.sqlite
ENV SESSION_DRIVER=database
ENV SESSION_LIFETIME=120
ENV SESSION_ENCRYPT=false
ENV SESSION_PATH=/
ENV SESSION_DOMAIN=null
ENV BROADCAST_CONNECTION=log
ENV FILESYSTEM_DISK=local
ENV CONTENT_DISK=public
ENV QUEUE_CONNECTION=database
ENV CACHE_STORE=database
ENV FFMPEG_BINARIES=/usr/bin/ffmpeg
ENV FFPROBE_BINARIES=/usr/bin/ffprobe
ENV PRIVATE_STORAGE_PATH=/data/private
ENV PUBLIC_STORAGE_PATH=/data/public
ENV AUTORUN_ENABLED=true
ENV SHOW_WELCOME_MESSAGE=false
