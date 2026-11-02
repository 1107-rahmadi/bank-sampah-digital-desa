# Image web Bank Sampah Digital Desa: PHP 8.2 + Apache
FROM php:8.2-apache

RUN docker-php-ext-install pdo_mysql \
    && a2enmod rewrite headers remoteip

COPY docker/php.ini /usr/local/etc/php/conf.d/zz-banksampah.ini
COPY docker/apache-vhost.conf /etc/apache2/sites-available/000-default.conf

WORKDIR /var/www/html
COPY . /var/www/html

# DocumentRoot adalah public/, API berada di luar public/ sehingga
# ditautkan ke /api. Folder lain (config, database, app) tidak bisa dibuka dari web.
RUN ln -s /var/www/html/api /var/www/html/public/api \
    && chown -R root:www-data /var/www/html \
    && chmod -R g+rX,o-rwx /var/www/html

EXPOSE 80
