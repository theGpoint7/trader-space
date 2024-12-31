FROM php:8.3-apache

WORKDIR /var/www/html

RUN docker-php-ext-configure pcntl --enable-pcntl \
  && docker-php-ext-install \
    pcntl 
    # \
    # pdo_mysql

EXPOSE 8080

CMD [ "sail", "artisan", "reverb:start"]
