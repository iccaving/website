FROM php:5.6.40-apache

# Install dependencies
RUN apt update
RUN apt install -y rsync
RUN docker-php-ext-install pdo_mysql

# apache config
RUN a2enmod rewrite

# Get bolt
RUN curl -O https://bolt.cm/distribution/archive/3.6/bolt-v3.6.6-flat-structure.tar.gz
RUN tar -xzf bolt-v3.6.6-flat-structure.tar.gz --strip-components=1

# PHP config
RUN pecl install xdebug-2.5.5
RUN mv "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini"
RUN echo 'zend_extension=/usr/local/lib/php/extensions/no-debug-non-zts-20131226/xdebug.so' >> "$PHP_INI_DIR/php.ini"
RUN echo '[XDebug] \n\
    xdebug.remote_enable = 1 \n\
    xdebug.remote_autostart = 1 \n\
    xdebug.remote_connect_back = 1' >> "$PHP_INI_DIR/php.ini"
RUN echo '[Date] \n\
    date.timezone = GB' >> "$PHP_INI_DIR/php.ini"

# Get our files and config
COPY . ./website
RUN rsync -aPI ./website/ .

# Create a local config file
RUN echo 'database:          \n\
    driver: mysql            \n\
    databasename: rcc_caving \n\
    username: root           \n\
    password: root           \n\
    host: iccc-db            \n\
    port: 3306               \n\
    siteurl:                     \n\
    debug: true                  \n\
    debug_show_loggedoff: true' > app/config/config_local.yml

# Install bolt
RUN php app/nut init

# Set permissions
RUN chmod -R 777 app/cache/ app/config/ app/database/ extensions/ thumbs/ files/ theme/
