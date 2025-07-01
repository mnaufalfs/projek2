# rental_kendaraan_new/Dockerfile
# Menggunakan base image PHP FPM dengan Alpine Linux versi 8.2
FROM php:8.2-fpm-alpine

# Menginstal dependensi sistem yang dibutuhkan untuk Nginx, Supervisor, Git, Unzip,
# serta semua library -dev (development) yang diperlukan untuk kompilasi ekstensi PHP.
# Perintah ini dipecah dengan '\' untuk keterbacaan, dan '&& \' untuk eksekusi berurutan.
RUN apk update && \
    apk add --no-cache \
    nginx \
    supervisor \
    openssl \
    git \
    unzip \
    # Dependensi untuk ekstensi PHP DOM, XML, SimpleXML
    libxml2-dev \
    # Dependensi untuk ekstensi PHP ZIP
    libzip-dev \
    # Dependensi untuk ekstensi PHP GD (Graphics Draw)
    libjpeg-turbo-dev \
    libpng-dev \
    libwebp-dev \
    freetype-dev \
    # Dependensi untuk ekstensi PHP CURL
    curl-dev \
    # Menghapus cache APK setelah instalasi untuk mengurangi ukuran image
    && rm -rf /var/cache/apk/*

# Menginstal ekstensi PHP menggunakan alat bawaan 'docker-php-ext-install' dan 'docker-php-ext-enable'.
# Alat ini secara otomatis mencari dan mengompilasi ekstensi dari sumber PHP.
# '-j$(nproc)' mempercepat kompilasi dengan menggunakan semua core CPU yang tersedia.
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

# Menentukan direktori kerja di dalam kontainer. Semua perintah COPY dan RUN berikutnya akan relatif terhadap ini.
WORKDIR /var/www/html

# Menyalin file konfigurasi Nginx kustom dari host ke lokasi konfigurasi Nginx di kontainer.
# Pastikan Anda memiliki folder 'nginx' di root proyek dengan file 'default.conf' di dalamnya.
COPY ./nginx/default.conf /etc/nginx/conf.d/default.conf

# Menyalin seluruh isi proyek Anda dari host ke direktori kerja di dalam kontainer.
COPY . .

# Mengatur kepemilikan dan izin untuk folder 'uploads' agar web server (www-data)
# dapat menulis ke dalamnya. Sesuaikan path jika ada folder lain yang perlu ditulis.
RUN chown -R www-data:www-data /var/www/html/uploads \
    && chmod -R 775 /var/www/html/uploads

# --- Bagian Opsional untuk Composer ---
# Jika proyek PHP Anda menggunakan Composer untuk mengelola dependensi:
# Hapus tanda komentar '#' pada baris di bawah ini.
# Ini akan mengunduh Composer dan menginstal semua dependensi proyek.
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
RUN composer install --no-dev --optimize-autoloader --working-dir=/var/www/html

# Mengumumkan bahwa kontainer akan mendengarkan di port 80.
EXPOSE 80

# Menyalin file konfigurasi Supervisor kustom dari host ke kontainer.
# Supervisor akan digunakan untuk menjalankan PHP-FPM dan Nginx secara bersamaan.
# Pastikan Anda memiliki folder 'supervisor' di root proyek dengan file 'supervisord.conf'.
COPY ./supervisor/supervisord.conf /etc/supervisord.conf

# Menentukan perintah yang akan dijalankan saat kontainer dimulai.
# Supervisor akan membaca konfigurasinya dan memulai PHP-FPM dan Nginx.
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisord.conf"]