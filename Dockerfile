# --- Stage 1: Base Image (OS and PHP Extensions) ---
FROM php:8.1-fpm-alpine as base
# ... (Kode Stage 1 tetap sama: Instalasi OS dependencies dan PHP extensions) ...
RUN apk add --no-cache \
    git \
    curl \
    unzip \
    libxml2-dev \
    libzip-dev \
    icu-dev \
    postgresql-dev \
    mariadb-client \
    nginx \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    && rm -rf /var/cache/apk/*

RUN docker-php-ext-install -j$(nproc) \
    pdo pdo_mysql opcache bcmath exif pcntl zip intl

RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd

WORKDIR /var/www/html

# --- Stage 2: Dependency Installation (Copy Seluruh Proyek) ---
FROM base as dependencies

# Install Composer dan Node.js/NPM
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
RUN apk add --no-cache nodejs npm

# Menyalin SELURUH Proyek ke dalam container dependencies
COPY . . 

# Instalasi PHP dependencies
RUN composer install --no-dev --optimize-autoloader --ignore-platform-reqs

# Instalasi JS dependencies
RUN npm install
RUN npm run build # <--- PERBAIKAN: Mengganti 'npm run dev' menjadi 'npm run build'

# --- Stage 3: Final Production Image ---
FROM base as final

# Copy application code
COPY . .

# Copy installed dependencies and compiled assets from the 'dependencies' stage
COPY --from=dependencies /var/www/html/vendor /var/www/html/vendor
COPY --from=dependencies /var/www/html/node_modules /var/www/html/node_modules
COPY --from=dependencies /var/www/html/public/js /var/www/html/public/js
COPY --from=dependencies /var/www/html/public/css /var/www/html/public/css

# Set permissions dan User
RUN chown -R www-data:www-data /var/www/html
RUN chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

USER www-data

EXPOSE 9000

CMD ["php-fpm"]