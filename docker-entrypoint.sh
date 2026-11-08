#!/bin/bash
set -e

# Dynamic Port binding for Render, Heroku, Railway & Cloud platforms
PORT="${PORT:-80}"
sed -i "s/Listen [0-9]*/Listen ${PORT}/g" /etc/apache2/ports.conf
sed -i "s/<VirtualHost \*:[0-9]*>/<VirtualHost \*:${PORT}>/g" /etc/apache2/sites-available/000-default.conf

exec "$@"
