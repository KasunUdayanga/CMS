FROM php:8.2-apache
RUN apt-get update && apt-get install -y libsqlite3-dev && docker-php-ext-install pdo_sqlite
COPY . /var/www/html/
WORKDIR /var/www/html
EXPOSE 8080