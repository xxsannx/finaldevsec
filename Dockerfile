# --- Stage 1: Base Image (OS and PHP Extensions) ---
FROM php:8.1-fpm-alpine as base

# Install OS dependencies required by Laravel and PHP extensions
# Menggunakan -j$(nproc) untuk instalasi ekstensi paralel (lebih cepat)
RUN apk add --no-cache \
    git \
    curl \
    unzip \
    libxml2-dev \
    libzip-dev \
    icu-dev \
    postgresql-dev \
    mariadb-client \
    nginx # Nginx ditambahkan jika Anda akan menjalankannya di container yang sama (meski biasanya di container terpisah)

# Install PHP extensions required by Laravel (termasuk yang populer seperti zip, gd, bcmath)
RUN docker-php-ext-install -j$(nproc) \
    pdo pdo_mysql opcache bcmath exif pcntl zip gd intl

# Set working directory
WORKDIR /var/www/html

# --- Stage 2: Dependency Installation ---
FROM base as dependencies

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Install Node.js and NPM
RUN apk add --no-cache nodejs npm

# Copy composer files and install PHP dependencies
COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --ignore-platform-reqs

# Copy package files and install JS dependencies
COPY package.json package-lock.json ./
RUN npm install
RUN npm run dev # Menggunakan 'npm run dev' atau 'npm run build' sesuai konfigurasi Anda

# --- Stage 3: Final Production Image ---
FROM base as final

# Copy application code
COPY . .

# Copy installed dependencies and compiled assets from the 'dependencies' stage
COPY --from=dependencies /var/www/html/vendor /var/www/html/vendor
COPY --from=dependencies /var/www/html/node_modules /var/www/html/node_modules
COPY --from=dependencies /var/www/html/public/js /var/www/html/public/js
COPY --from=dependencies /var/www/html/public/css /var/www/html/public/css

# Set permissions
RUN chown -R www-data:www-data /var/www/html
RUN chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# User for running application
USER www-data

# Expose port (asumsi FPM)
EXPOSE 9000

# Start PHP-FPM
CMD ["php-fpm"]