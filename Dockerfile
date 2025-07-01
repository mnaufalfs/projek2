# rental_kendaraan_new/Dockerfile
# Gunakan base image PHP FPM (tetap php:8.2-fpm-alpine)
FROM php:8.2-fpm-alpine

# Update repositori Alpine sebelum menginstal paket
# Ini memastikan daftar paket terbaru tersedia
RUN apk update && \
    # Instal dependensi sistem yang dibutuhkan untuk PHP, Nginx, Supervisor, dll.
    apk add --no-cache \
    nginx \
    php8-mysqli \
    php8-pdo_mysql \
    php8-dom \
    php8-xml \
    php8-simplexml \
    php8-json \
    php8-mbstring \
    php8-curl \
    php8-gd \
    php8-zip \
    supervisor \
    openssl \
    git \
    unzip \
    # Pastikan untuk membersihkan cache apk setelah instalasi berhasil
    && rm -rf /var/cache/apk/*

# Konfigurasi Nginx: Salin file konfigurasi Nginx kustom Anda
COPY ./nginx/default.conf /etc/nginx/conf.d/default.conf

# Copy kode aplikasi Anda ke dalam container
WORKDIR /var/www/html
COPY . .

# Setel izin yang sesuai untuk folder yang memerlukan penulisan oleh web server
# Sesuaikan ini berdasarkan kebutuhan aplikasi Anda. 'uploads' dan mungkin 'cache'/'storage'
# User 'www-data' adalah user default untuk Nginx/PHP-FPM di container berbasis Debian/Alpine
RUN chown -R www-data:www-data /var/www/html/uploads \
    && chmod -R 775 /var/www/html/uploads

# (Opsional) Jika Anda menggunakan Composer untuk dependensi PHP:
# COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
# RUN composer install --no-dev --optimize-autoloader --working-dir=/var/www/html

# Expose port yang digunakan oleh Nginx
EXPOSE 80

# Gunakan supervisord untuk menjalankan PHP-FPM dan Nginx secara bersamaan
COPY ./supervisor/supervisord.conf /etc/supervisord.conf
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisord.conf"]