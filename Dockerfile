# =============================================================================
# EstateVista India - Production-Ready Dockerfile
# Base: PHP 8.2 with Apache, PDO MySQL, GD, Zip & OPcache
# =============================================================================

FROM php:8.2-apache

# Metadata
LABEL maintainer="EstateVista DevOps Team <devops@estatevistaindia.com>"
LABEL version="1.0"
LABEL description="EstateVista India - Ultra-Luxury Real Estate Advisory Portal"

# 1. Install system libraries and build packages
RUN apt-get update && apt-get install -y --no-install-recommends \
    libpng-dev \
    libjpeg-dev \
    libwebp-dev \
    libfreetype6-dev \
    libzip-dev \
    libonig-dev \
    default-mysql-client \
    zip \
    unzip \
    curl \
    && rm -rf /var/lib/apt/lists/*

# 2. Configure & install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j$(nproc) \
        pdo_mysql \
        mysqli \
        gd \
        mbstring \
        zip \
        opcache

# 3. Custom PHP Runtime Configuration (Optimized for Media & Indian Timezone)
RUN { \
        echo 'upload_max_filesize = 64M'; \
        echo 'post_max_size = 64M'; \
        echo 'memory_limit = 256M'; \
        echo 'max_execution_time = 120'; \
        echo 'date.timezone = Asia/Kolkata'; \
        echo 'display_errors = Off'; \
        echo 'log_errors = On'; \
        echo 'error_log = /var/log/apache2/php_errors.log'; \
    } > /usr/local/etc/php/conf.d/estatevista-php.ini

# 4. OPcache Optimization Directives
RUN { \
        echo 'opcache.enable = 1'; \
        echo 'opcache.memory_consumption = 128'; \
        echo 'opcache.interned_strings_buffer = 8'; \
        echo 'opcache.max_accelerated_files = 10000'; \
        echo 'opcache.revalidate_freq = 2'; \
        echo 'opcache.fast_shutdown = 1'; \
    } > /usr/local/etc/php/conf.d/opcache-recommended.ini

# 5. Enable Essential Apache Modules
RUN a2enmod rewrite headers expires deflate

# 6. Configure Apache DocumentRoot & AllowOverride for .htaccess directives
RUN { \
        echo '<Directory /var/www/html>'; \
        echo '    Options -Indexes +FollowSymLinks'; \
        echo '    AllowOverride All'; \
        echo '    Require all granted'; \
        echo '</Directory>'; \
    } > /etc/apache2/conf-available/estatevista-override.conf \
    && a2enconf estatevista-override

# 7. Set Container Working Directory
WORKDIR /var/www/html

# 8. Copy Application Code into Apache Webroot
COPY . /var/www/html/

# 9. Configure Directory Permissions for Apache (www-data) & Entrypoint
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && chmod -R 775 /var/www/html/uploads \
    && sed -i 's/\r$//' /var/www/html/docker-entrypoint.sh \
    && chmod +x /var/www/html/docker-entrypoint.sh

# 10. Expose Standard HTTP Port (Render binds dynamically via $PORT)
EXPOSE 80 10000

# 11. Run Apache via dynamic entrypoint
ENTRYPOINT ["/var/www/html/docker-entrypoint.sh"]
CMD ["apache2-foreground"]
