FROM php:8.2-apache

# Install system dependencies and database drivers (MySQL and PostgreSQL)
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    zip \
    unzip \
    git \
    libpq-dev \
    libzip-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd pdo pdo_mysql pdo_pgsql zip

# Enable Apache rewrite module for Laravel routing
RUN a2enmod rewrite

# Reconfigure Apache document root to point to Laravel's public/ folder
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Set working directory inside container
WORKDIR /var/www/html

# Copy application files
COPY . .

# Set execute permissions on the entrypoint script
RUN chmod +x entrypoint.sh

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
RUN composer install --no-dev --optimize-autoloader

# Set proper write permissions for Laravel storage and cache directories
RUN chown -R www-data:www-data storage bootstrap/cache

# Expose web server port
EXPOSE 80

# Configure container entrypoint
ENTRYPOINT ["/var/www/html/entrypoint.sh"]
