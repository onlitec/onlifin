# Dockerfile simplificado para Onlifin
FROM php:8.2-fpm-alpine

WORKDIR /var/www/html

# Instalar dependências do sistema
RUN apk add --no-cache \
    nginx \
    supervisor \
    sqlite \
    sqlite-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libzip-dev \
    icu-dev \
    oniguruma-dev \
    curl-dev \
    libxml2-dev \
    postgresql-dev \
    mysql-dev \
    mysql-client \
    mariadb-client \
    autoconf \
    g++ \
    make \
    git \
    unzip \
    bash \
    nodejs \
    npm

# Configurar extensões PHP
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        gd \
        zip \
        intl \
        mbstring \
        pdo \
        pdo_sqlite \
        pdo_mysql \
        pdo_pgsql \
        curl \
        xml \
        bcmath \
        opcache

# Instalar Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Criar usuário para a aplicação
RUN addgroup -g 1000 -S www && \
    adduser -u 1000 -D -S -G www www

# Copiar arquivos da aplicação
COPY . .

# Instalar dependências Node.js e build assets
RUN npm install && npm run build

# Instalar dependências PHP
RUN composer install --no-dev --optimize-autoloader --no-interaction --ignore-platform-req=ext-sockets

# Criar diretório para SQLite se não existir
RUN mkdir -p /var/www/html/database \
    && touch /var/www/html/database/database.sqlite

# Configurar Nginx
COPY docker/nginx.conf /etc/nginx/nginx.conf
COPY docker/default.conf /etc/nginx/http.d/default.conf

# Configurar PHP-FPM
COPY docker/php-fpm.conf /usr/local/etc/php-fpm.d/www.conf
COPY docker/php.ini /usr/local/etc/php/php.ini

# Configurar Supervisor
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Script de inicialização
COPY docker/start.sh /start.sh
RUN chmod +x /start.sh

# Expor porta
EXPOSE 80

# Comando de inicialização
CMD ["/start.sh"]
