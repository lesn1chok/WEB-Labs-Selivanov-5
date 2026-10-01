FROM node:22-bookworm-slim AS frontend
WORKDIR /build
COPY package*.json ./
RUN npm ci
COPY lab05 ./lab05
COPY shared ./shared
COPY vite.config.js ./
RUN npm run build

FROM php:8.3-cli-bookworm AS app
WORKDIR /app
RUN php -r "foreach(['pdo_sqlite','openssl'] as \$e) if(!extension_loaded(\$e)) exit(1);" 
COPY --chown=www-data:www-data . .
COPY --from=frontend --chown=www-data:www-data /build/dist ./dist
RUN mkdir -p /data && chown www-data:www-data /data
ENV LAB_DATA_DIR=/data
USER www-data
EXPOSE 8080
HEALTHCHECK --interval=15s --timeout=5s --retries=3 CMD php -r "exit(@file_get_contents('http://127.0.0.1:8080/api.php?resource=health')===false?1:0);"
CMD ["php","-S","0.0.0.0:8080","router.php"]
