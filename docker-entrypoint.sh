#!/bin/sh
# docker-entrypoint.sh
# Configures Apache to listen on the runtime $PORT (Render sets this
# dynamically), then hands off to the main container command.
set -e

PORT="${PORT:-80}"

sed -i "s/^Listen .*/Listen ${PORT}/" /etc/apache2/ports.conf
sed -i "s/<VirtualHost \*:80>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-enabled/000-default.conf

exec "$@"
