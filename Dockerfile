# 1. Gunakan PHP dengan ekstensi yang dibutuhkan Laravel
FROM php:8.2-fpm-alpine

# 2. Install sistem dependencies dan ekstensi PHP
RUN apk add --no-cache \
    libpng-dev \
    libzip-dev \
    zip \
    unzip \
    git \
    curl \
    oniguruma-dev \
    nginx

RUN docker-php-ext-install pdo_mysql mbstring gd zip

# 3. Install Composer secara global
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# 4. Set working directory
WORKDIR /var/www

# 5. Salin semua file project
COPY . .

# 6. Install dependencies Laravel (tanpa dev tools untuk efisiensi)
RUN composer install --no-dev --optimize-autoloader

# 7. Atur izin folder storage dan bootstrap/cache (Wajib untuk Laravel)
RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache

# 8. Salin konfigurasi Nginx (kita buat di langkah berikutnya)
COPY ./docker/nginx.conf /etc/nginx/http.d/default.conf

# 9. Expose port 9004
EXPOSE 9004

# 10. Jalankan PHP-FPM dan Nginx secara bersamaan
CMD php-fpm -D && nginx -g "daemon off;"
