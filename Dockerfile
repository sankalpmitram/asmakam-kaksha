# ==========================================================================
# Dockerfile - अस्माकं कक्षा (PHP 8 + Apache)
# Builds a production-ready container for Render.com (or any Docker host).
# All app data lives in Firestore, and student photos / school logo
# uploads live in Firebase Storage — both are real Google Cloud services,
# so this container itself needs no persistent local storage at all.
# ==========================================================================

FROM php:8.2-apache

# Required PHP extensions:
# - zip: used by api/backup.php and api/restore.php (ZIP backup/restore)
# - fileinfo: used to validate uploaded image mime types
# - curl: used by includes/firestore.php and includes/storage.php to call
#   the Firestore / Cloud Storage REST APIs (openssl is required too, for
#   signing the Firebase auth JWT — it ships enabled by default in the
#   official php:8.2-apache image)
RUN apt-get update \
    && apt-get install -y --no-install-recommends libzip-dev libcurl4-openssl-dev unzip \
    && docker-php-ext-install zip fileinfo curl \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Enable Apache rewrite module (used for clean routing fallback).
RUN a2enmod rewrite

# Copy application source.
COPY . /var/www/html/
RUN chown -R www-data:www-data /var/www/html

# Apache must listen on the PORT env var that Render provides at runtime
# (defaults to 80 for local/plain Docker use).
ENV PORT=80
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

EXPOSE 80

ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["apache2-foreground"]
