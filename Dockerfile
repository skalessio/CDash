FROM php:7.0-apache

RUN curl -sL https://deb.nodesource.com/setup_4.x | bash
RUN apt-get install -y \
    cmake \
    dbus-x11 \
    default-jre \
    fonts-liberation \
    gconf-service \
    git \
    libappindicator1 \
    libbz2-dev \
    libfreetype6-dev \
    libgconf-2-4 \
    libgtk-3-0 \
    libjpeg62-turbo-dev \
    libmcrypt-dev \
    libpng12-dev \
    libpq-dev \
    libxslt-dev \
    libxss1 \
    nodejs \
    sudo \
    unzip \
    wget \
    xfonts-cyrillic \
    xdg-utils \
    xfonts-75dpi \
    xfonts-100dpi \
    xvfb \
    zip

RUN docker-php-ext-configure pgsql -with-pgsql=/usr/local/pgsql
RUN docker-php-ext-configure gd --with-freetype-dir=/usr/include/ --with-jpeg-dir=/usr/include/
RUN docker-php-ext-install -j$(nproc)  bcmath bz2 gd pdo_mysql pdo_pgsql xsl
RUN pecl install xdebug-2.5.5 && docker-php-ext-enable xdebug
RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
RUN php -r "if (hash_file('SHA384', 'composer-setup.php') === '669656bab3166a7aff8a7506b8cb2d1c292f042046c5a994c43155c0be6190fa0355160742ab2e1c88d40d5be660b410') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;"
RUN php composer-setup.php --install-dir=/usr/local/bin --filename=composer
RUN php -r "unlink('composer-setup.php');"
RUN curl -o google-chrome-stable_current_amd64.deb https://dl.google.com/linux/direct/google-chrome-stable_current_amd64.deb
RUN dpkg -i ./google-chrome-stable_current_amd64.deb && rm ./google-chrome-stable_current_amd64.deb
RUN sed -i.bkp -e \
      's/%sudo\s\+ALL=(ALL\(:ALL\)\?)\s\+ALL/%sudo ALL=NOPASSWD:ALL/g' \
      /etc/sudoers
RUN adduser --disabled-password --gecos '' kitware
RUN adduser kitware sudo
ADD . /home/kitware/cdash
COPY ./docker-entrypoint.sh /docker-entrypoint.sh
COPY ./docker-exectests.sh /docker-exectests.sh
RUN chmod +x /docker-entrypoint.sh
RUN chmod +x /docker-exectests.sh
WORKDIR /home/kitware/cdash
EXPOSE 80
# CMD ["sudo", "apache2-foreground"]
USER kitware
ENTRYPOINT ["/docker-entrypoint.sh"]
