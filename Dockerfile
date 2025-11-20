FROM php:8.2-apache

RUN apt-get update \
    && apt-get install -y \
       git \
       unzip \
       libpq-dev

RUN docker-php-ext-install pdo_pgsql

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY composer.json composer.lock ./

RUN composer install --no-interaction --optimize-autoloader --no-dev

COPY . .

RUN a2enmod rewrite \
    && echo "<VirtualHost *:80>\n\
        ServerName localhost\n\
        DocumentRoot /var/www/html/public\n\
        <Directory /var/www/html/public>\n\
            AllowOverride All\n\
            Require all granted\n\
        </Directory>\n\
        ErrorLog ${APACHE_LOG_DIR}/error.log\n\
        CustomLog ${APACHE_LOG_DIR}/access.log combined\n\
    </VirtualHost>" > /etc/apache2/sites-available/000-default.conf

EXPOSE 80
