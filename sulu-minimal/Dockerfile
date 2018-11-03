FROM composer AS composer

WORKDIR /var/www/html

COPY composer.* /var/www/html/
RUN composer install --apcu-autoloader -o --no-dev --no-scripts

FROM kkarczmarczyk/node-yarn:latest AS yarn

WORKDIR /var/www/html

COPY package.json /var/www/html/

COPY --from=composer /var/www/html/vendor/sulu/sulu /var/www/html/vendor/sulu/sulu
COPY webpack.config.js /var/www/html/

RUN npm install
run npm run build

# Build actual image
FROM php:7.2-apache

WORKDIR /var/www/html

# install packages
RUN apt-get update
RUN apt-get install -y \
        openssl \
        git \
        unzip \
        libicu-dev \
        libmagickwand-dev

# install PHP extensions
RUN docker-php-ext-configure intl
RUN docker-php-ext-install -j$(nproc) \
        intl \
        pdo \
        pdo_mysql \
        opcache \
        zip

RUN pecl install \
    imagick

RUN docker-php-ext-enable \
        imagick

# apache config
RUN /usr/sbin/a2enmod rewrite
RUN /usr/sbin/a2enmod headers
RUN /usr/sbin/a2enmod expires
COPY ./config/container/apache.conf /etc/apache2/sites-available/000-default.conf

# copy needed files from build containers
COPY --from=yarn /var/www/html/public/admin/build/ /var/www/html/public/admin/build/
COPY --from=composer /var/www/html/vendor/ /var/www/html/vendor/

COPY . /var/www/html/

# Ensure that cache, log and session directories are writable
RUN chown -R www-data:www-data /var/www/html/var
