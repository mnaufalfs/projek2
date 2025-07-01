# rental_kendaraan_new/Dockerfile
FROM php:8.2-fpm-alpine

# Update repositori Alpine dan instal dependensi sistem (non-PHP ekstensi)
RUN apk update && \
    apk add --no-cache \
    nginx \
    supervisor \
    openssl \
    git \
    unzip \
    # Tambahkan dependensi dev yang dibutuhkan untuk ekstensi PHP tertentu
    libxml2-dev \   # <-- BARIS INI DITAMBAHKAN
    libzip-dev \    # <-- (Opsional, tapi seringkali diperlukan untuk 'zip')
    libjpeg-turbo-dev \ # <-- (Opsional, tapi seringkali diperlukan untuk 'gd')
    libpng-dev \        # <-- (Opsional, tapi seringkali diperlukan untuk 'gd')
    libwebp-dev \       # <-- (Opsional, tapi seringkali diperlukan untuk 'gd')
    freetype-dev \      # <-- (Opsional, tapi seringkali diperlukan untuk 'gd')
    # Pastikan untuk membersihkan cache apk setelah instalasi berhasil
    && rm -rf /var/cache/apk/*

# --- BAGIAN INSTALASI EKSTENSI PHP ---
# Instal ekstensi PHP menggunakan docker-php-ext-install dan docker-php-ext-enable
# Pastikan nama ekstensi sesuai dengan yang diharapkan oleh PHP (tanpa 'php8-')
RUN docker-php-ext-install -j$(nproc) \
    mysqli \
    pdo_mysql \
    dom \
    xml \
    simplexml \
    json \
    mbstring \
    curl \
    gd \
    zip \
    && docker-php-ext-enable \
    mysqli \
    pdo_mysql \
    dom \
    xml \
    simplexml \
    json \
    mbstring \
    curl \
    gd \
    zip

# Konfigurasi Nginx: Salin file konfigurasi Nginx kustom Anda
COPY ./nginx/default.conf /etc/nginx/conf.d/default.conf

# Copy kode aplikasi Anda ke dalam container
WORKDIR /var/www/html
COPY . .

# Setel izin yang sesuai untuk folder yang memerlukan penulisan oleh web server
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