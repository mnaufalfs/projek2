# rental_kendaraan_new/Dockerfile
FROM php:8.2-fpm-alpine

# Menginstal dependensi sistem yang dibutuhkan
RUN apk update && apk add --no-cache \
    nginx \
    supervisor \
    openssl \
    git \
    unzip \
    libxml2-dev \
    libzip-dev \
    libjpeg-turbo-dev \
    libpng-dev \
    libwebp-dev \
    freetype-dev \
    curl-dev \
    # Tambahkan zlib-dev dan libffi-dev yang kadang diperlukan untuk ekstensi seperti curl dan json/mbstring
    zlib-dev \      # <--- TAMBAH INI
    libffi-dev \    # <--- TAMBAH INI (jarang, tapi kadang membantu)
    && rm -rf /var/cache/apk/*

# --- BAGIAN INSTALASI EKSTENSI PHP (UTAMA) ---
# Instal ekstensi PHP umum yang lebih stabil dulu, KECUALI 'curl', 'gd' dan 'zip'
RUN docker-php-ext-install -j$(nproc) \
    mysqli \
    pdo_mysql \
    dom \
    xml \
    simplexml \
    json \
    mbstring \
    && docker-php-ext-enable \
    mysqli \
    pdo_mysql \
    dom \
    xml \
    simplexml \
    json \
    mbstring

# --- Instal ekstensi 'curl' secara terpisah ---
RUN docker-php-ext-install -j$(nproc) curl \
    && docker-php-ext-enable curl

# --- Instal ekstensi 'gd' secara terpisah ---
RUN docker-php-ext-install -j$(nproc) gd \
    && docker-php-ext-enable gd

# --- Instal ekstensi 'zip' secara terpisah ---
RUN docker-php-ext-install -j$(nproc) zip \
    && docker-php-ext-enable zip

# Konfigurasi Nginx: Salin file konfigurasi Nginx kustom Anda
COPY ./nginx/default.conf /etc/nginx/conf.d/default.conf

# Copy kode aplikasi Anda ke dalam container
WORKDIR /var/www/html
COPY . .

# Setel izin yang sesuai untuk folder yang memerlukan penulisan oleh web server
RUN chown -R www-data:www-data /var/www/html/uploads \
    && chmod -R 775 /var/www/html/uploads

# (Opsional) Jika Anda menggunakan Composer untuk dependensi PHP:
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
RUN composer install --no-dev --optimize-autoloader --working-dir=/var/www/html

# Expose port yang digunakan oleh Nginx
EXPOSE 80

# Gunakan supervisord untuk menjalankan PHP-FPM dan Nginx secara bersamaan
COPY ./supervisor/supervisord.conf /etc/supervisord.conf
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisord.conf"]