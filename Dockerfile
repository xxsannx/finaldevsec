FROM php:8.1-fpm-alpine as base

# Install common dependencies
RUN apk add --no-cache \
    git \
    curl \
    unzip \
    libxml2-dev \
    ... # pastikan semua dependensi PHP ada di sini

# Install PHP extensions
RUN docker-php-ext-install pdo pdo_mysql opcache

# Set working directory
WORKDIR /var/www/html

# --- Stage 1: Dependency Installation ---
FROM base as dependencies

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Install Node.js and NPM (via nvm or direct install for Alpine)
# Menggunakan cara sederhana untuk Alpine
RUN apk add --no-cache nodejs npm

# Copy composer files and install PHP dependencies
COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --ignore-platform-reqs

# Copy package files and install JS dependencies
COPY package.json package-lock.json ./
RUN npm install
RUN npm run build # Compile assets

# --- Stage 2: Final Production Image ---
FROM base as final

# Copy application code
COPY . .

# Copy installed dependencies from the 'dependencies' stage
COPY --from=dependencies /var/www/html/vendor /var/www/html/vendor
COPY --from=dependencies /var/www/html/node_modules /var/www/html/node_modules
COPY --from=dependencies /var/www/html/public /var/www/html/public 

# Set permissions
RUN chown -R www-data:www-data /var/www/html

# Expose port (asumsi FPM)
EXPOSE 9000

# Start PHP-FPM
CMD ["php-fpm"]