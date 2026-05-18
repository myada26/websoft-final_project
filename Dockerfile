FROM richarvey/nginx-php-fpm:3.1.6

COPY . /var/www/html

# Image looks for run-scripts here
ENV WEBROOT=/var/www/html/public
ENV PHP_ERRORS_STDERR=1
ENV RUN_SCRIPTS=1
ENV REAL_IP_HEADER=1

# Laravel defaults (overridden by Render dashboard env vars)
ENV APP_ENV=production
ENV APP_DEBUG=false
ENV LOG_CHANNEL=stderr

# Composer install runs as root inside the container
ENV COMPOSER_ALLOW_SUPERUSER=1

CMD ["/start.sh"]
