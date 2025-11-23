# ============================================
# Stage 1: Builder - Instala dependências
# ============================================
FROM php:8.2-fpm AS builder

# Instala dependências do sistema
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    libzip-dev \
    default-mysql-client \
    libicu-dev \
    libssl-dev \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Instala extensões PHP essenciais
RUN docker-php-ext-install \
    pdo_mysql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    gd \
    zip \
    sockets \
    intl \
    opcache

# Instala Redis extension
RUN pecl install redis && docker-php-ext-enable redis

# Configura OPcache para produção
RUN echo "opcache.enable=1" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.memory_consumption=256" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.interned_strings_buffer=16" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.max_accelerated_files=20000" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.validate_timestamps=0" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.save_comments=1" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.fast_shutdown=1" >> /usr/local/etc/php/conf.d/opcache.ini

# Configura php.ini para produção (segurança e performance)
RUN echo "expose_php = Off" >> /usr/local/etc/php/conf.d/security.ini \
    && echo "max_execution_time = 300" >> /usr/local/etc/php/conf.d/security.ini \
    && echo "memory_limit = 512M" >> /usr/local/etc/php/conf.d/security.ini \
    && echo "upload_max_filesize = 10M" >> /usr/local/etc/php/conf.d/security.ini \
    && echo "post_max_size = 10M" >> /usr/local/etc/php/conf.d/security.ini \
    && echo "disable_functions = exec,passthru,shell_exec,system,proc_open,popen" >> /usr/local/etc/php/conf.d/security.ini

# Instala Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Define diretório de trabalho
WORKDIR /var/www/html

# Copia arquivos de dependências primeiro (cache layer)
# O contexto é ./backend, então os arquivos estão na raiz
COPY composer.json composer.lock* ./

# Instala dependências do Composer (sem dev para produção)
RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader --no-scripts || \
    composer install --no-interaction --prefer-dist --optimize-autoloader --no-scripts

# Copia código da aplicação
# O contexto é ./backend, então copia tudo da raiz
COPY . .

# Executa scripts do Composer e otimiza Laravel
RUN composer dump-autoload --optimize \
    && php artisan config:cache || true \
    && php artisan route:cache || true \
    && php artisan view:cache || true

# ============================================
# Stage 2: Production - Imagem final slim
# ============================================
FROM php:8.2-fpm

# Instala apenas dependências runtime essenciais
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    default-mysql-client \
    libicu-dev \
    curl \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Instala extensões PHP (apenas runtime)
RUN docker-php-ext-install \
    pdo_mysql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    gd \
    zip \
    sockets \
    intl \
    opcache

# Instala Redis extension
RUN pecl install redis && docker-php-ext-enable redis

# Copia configurações do builder
COPY --from=builder /usr/local/etc/php/conf.d/opcache.ini /usr/local/etc/php/conf.d/
COPY --from=builder /usr/local/etc/php/conf.d/security.ini /usr/local/etc/php/conf.d/

# Define diretório de trabalho
WORKDIR /var/www/html

# Copia aplicação otimizada do builder
COPY --from=builder --chown=www-data:www-data /var/www/html /var/www/html

# Copia entrypoint script do backend/docker (que está no contexto)
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# Cria diretórios necessários com permissões corretas
RUN mkdir -p storage/framework/{sessions,views,cache} \
    && mkdir -p storage/logs \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# Define usuário não-root
USER www-data

# Expõe porta PHP-FPM
EXPOSE 9000

# Healthcheck simplificado - verifica se PHP-FPM está rodando e arquivos essenciais existem
HEALTHCHECK --interval=30s --timeout=10s --retries=3 --start-period=60s \
    CMD php -r "if (!file_exists('/var/www/html/vendor/autoload.php')) exit(1); if (!file_exists('/var/www/html/bootstrap/app.php')) exit(1); require '/var/www/html/vendor/autoload.php'; \$app = require '/var/www/html/bootstrap/app.php'; if (!\$app) exit(1); exit(0);" || exit 1

# Entrypoint e comando padrão
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["php-fpm"]
