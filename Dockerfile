# rental_kendaraan_new/Dockerfile
# Menggunakan image PHP FPM dengan Debian Bullseye (tag lebih umum)
FROM php:8.2-fpm

# Menginstal dependensi sistem yang dibutuhkan
# BARIS INI HARUS SATU BARIS FISIK PENUH DI FILE DOCKERFILE ANDA.
RUN apt update && apt install -y nginx supervisor openssl git unzip \
    libxml2-dev libzip-dev libjpeg-dev libpng-dev libwebp-dev libfreetype6-dev \
    libcurl4-openssl-dev libonig-dev zlib1g-dev libffi-dev build-essential \
    && rm -rf /var/lib/apt/lists/*

# --- BAGIAN INSTALASI EKSTENSI PHP ---
# Ekstensi dasar yang kurang rewel. JSON dan MBSTRING seharusnya sudah bawaan di PHP 8.2.
RUN docker-php-ext-install -j$(nproc) mysqli pdo_mysql dom xml simplexml && \
    docker-php-ext-enable mysqli pdo_mysql dom xml simplexml

# --- Instal ekstensi 'curl' ---
RUN docker-php-ext-install -j$(nproc) curl && docker-php-ext-enable curl

# --- Instal ekstensi 'gd' ---
# Konfigurasi eksplisit GD ini biasanya diperlukan
RUN docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp && \
    docker-php-ext-install -j$(nproc) gd && docker-php-ext-enable gd

# --- Instal ekstensi 'zip' (Kembali ke metode yang lebih sederhana) ---
# Opsi --with-libzip tidak dikenali, jadi kita hanya menggunakan install saja.
RUN docker-php-ext-install -j$(nproc) zip && docker-php-ext-enable zip

# Menentukan direktori kerja
WORKDIR /var/www/html

# Menyalin file konfigurasi Nginx kustom.
COPY ./nginx/default.conf /etc/nginx/conf.d/default.conf

# Menyalin seluruh isi proyek Anda.
COPY . .

# Mengatur kepemilikan dan izin untuk folder 'uploads'.
RUN chown -R www-data:www-data /var/www/html/uploads && chmod -R 775 /var/www/html/uploads

# --- Bagian Composer ---
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
RUN git config --global --add safe.directory /var/www/html && \
    composer install --no-dev --optimize-autoloader --working-dir=/var/www/html

# Mengumumkan bahwa kontainer akan mendengarkan di port 80.
EXPOSE 80

# Menyalin file konfigurasi Supervisor.
COPY ./supervisor/supervisord.conf /etc/supervisord.conf

# Menentukan perintah yang akan dijalankan saat kontainer dimulai.
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisord.conf"]