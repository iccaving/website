FROM debian:12

# Install dependencies
RUN apt update && \
    apt install -y rsync apt-transport-https lsb-release ca-certificates wget apache2 curl
RUN wget -O /etc/apt/trusted.gpg.d/php.gpg https://packages.sury.org/php/apt.gpg && \
    echo "deb https://packages.sury.org/php/ $(lsb_release -sc) main" | tee /etc/apt/sources.list.d/php.list 
RUN apt update && \
    apt install -y php7.2 php-pear php7.2-mysql php7.2-xml

# apache config
RUN a2enmod rewrite
# RUN sed -i "s,/var/www/html,/var/www/html/public,g" /etc/apache2/sites-enabled/000-default.conf
WORKDIR /var/www/html
RUN rm -rf index.html
COPY default.conf /etc/apache2/sites-enabled/000-default.conf

# Get bolt
# RUN curl -O https://bolt.cm/distribution/archive/3.7/bolt-3.7.2.tar.gz
# RUN tar -xzf bolt-3.7.2.tar.gz --strip-components=1
RUN curl -O https://bolt.cm/distribution/archive/3.7/bolt-3.7.2-flat-structure.tar.gz
RUN tar -xzf bolt-3.7.2-flat-structure.tar.gz --strip-components=1

# Get our files and config
COPY . ./website
RUN rm -rf ./website/vendor
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
debug_show_loggedoff: true   \n\
debuglog:                    \n\
  enabled: true              \n\
  filename: bolt-debug.log   \n\
  level: DEBUG' > app/config/config_local.yml

# Install bolt
RUN php app/nut init

# Set permissions
RUN chmod -R 777 app/cache/ app/config/ app/database/ extensions/ thumbs/ files/ theme/
# RUN chmod -R 777 app/cache/ app/config/ app/database/ extensions/
# RUN chmod -R 777 public/thumbs/ public/extensions/ public/files/ public/theme/

# Run apache
EXPOSE 80
CMD apachectl -D FOREGROUND