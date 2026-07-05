# ==========================================================================
# Dockerfile - अस्माकं कक्षा (PHP 8 + Apache)
# Builds a production-ready container for Render.com (or any Docker host).
# No database required; all data is stored in JSON files under /data.
# ==========================================================================

FROM php:8.2-apache

# Required PHP extensions:
# - zip: used by api/backup.php and api/restore.php
# - fileinfo: used to validate uploaded image mime types
RUN apt-get update \
    && apt-get install -y --no-install-recommends libzip-dev unzip \
    && docker-php-ext-install zip fileinfo \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Enable Apache rewrite module (used for clean routing fallback).
RUN a2enmod rewrite

# Copy application source.
COPY . /var/www/html/

# Ensure data/uploads directories exist and are writable by the web server.
RUN mkdir -p /var/www/html/data /var/www/html/uploads \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 775 /var/www/html/data /var/www/html/uploads

# Apache must listen on the PORT env var that Render provides at runtime
# (defaults to 80 for local/plain Docker use).
ENV PORT=80
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

EXPOSE 80

ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["apache2-foreground"]
