# Use an official PHP image as a base
FROM php:8.1-apache

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libzip-dev \
    libpng-dev \
    libjpeg-dev \
    libonig-dev \
    libxml2-dev \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install pdo pdo_mysql mysqli gd zip mbstring exif

# Enable Apache rewrite module (if you use clean URLs)
RUN a2enmod rewrite

# Copy the application code into the container
COPY . /var/www/html/

# Set appropriate permissions (adjust as needed for your application)
RUN chown -R www-data:www-data /var/www/html

# Expose port 80 (Apache default)
EXPOSE 80

# Start Apache when the container launches
CMD ["apache2-foreground"]