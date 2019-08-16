ARG PHP_VERSION=7.3-fpm-stretch

FROM php:${PHP_VERSION}
LABEL maintainer="usamocom@ukr.net"
LABEL description="Project"

ENV INSTALL_DIR /var/www/project
ENV SSH_USER root
ENV SSH_PASSWORD root
#
#RUN requirements="libcurl3-dev libfreetype6 libjpeg62-turbo libjpeg62-turbo-dev libpng-dev libfreetype6-dev libicu-dev libxslt1-dev" \
#    && apt-get update \
#    && apt-get install -y $requirements \
#    && rm -rf /var/lib/apt/lists/* \
#	&& pecl install xdebug-2.6.1 \
#    && docker-php-ext-install pdo_mysql \
#    && docker-php-ext-configure gd --with-freetype-dir=/usr/include/ --with-jpeg-dir=/usr/include/ \
#    && docker-php-ext-install gd \
#    && docker-php-ext-install mbstring \
#    && docker-php-ext-install zip \
#    && docker-php-ext-install intl \
#    && docker-php-ext-install xsl \
#    && docker-php-ext-install soap \
#    && docker-php-ext-install bcmath \
#	&& docker-php-ext-enable xdebug \
#    && requirementsToRemove="libcurl3-dev libpng-dev libfreetype6-dev libjpeg62-turbo-dev" \
#    && apt-get purge --auto-remove -y $requirementsToRemove

RUN apt-get update \
	&& apt-get install -y openssh-server \
	sudo \
	openssh-server \
	curl \
	gnupg \
	&& curl -sL https://deb.nodesource.com/setup_11.x | bash - \
	&& apt-get install -y nodejs \
	&& npm install -g grunt-cli

#RUN apt-get install cron --no-install-recommends -y
RUN apt-get install mysql-client -y
RUN apt-get install vim -y

RUN echo "memory_limit=2048M" >> /usr/local/etc/php/conf.d/common.ini \
	&& echo "max_execution_time=1000" >> /usr/local/etc/php/conf.d/common.ini \
	&& echo "max_input_time=1000" >> /usr/local/etc/php/conf.d/common.ini

RUN echo "xdebug.remote_enable=1" >> /usr/local/etc/php/conf.d/xdebug.ini \
	&& echo "xdebug.idekey=PHPSTORM" >> /usr/local/etc/php/conf.d/xdebug.ini \
	&& echo "xdebug.remote_port=9000" >> /usr/local/etc/php/conf.d/xdebug.ini \
	&& echo "xdebug.remote_host=host.docker.internal" >> /usr/local/etc/php/conf.d/xdebug.ini \
	&& echo "xdebug.remote_cookie_expire_time=-9999" >> /usr/local/etc/php/conf.d/xdebug.ini

RUN mkdir /var/run/sshd
RUN echo "${SSH_USER}:${SSH_PASSWORD}" | chpasswd
RUN echo "PermitRootLogin yes" >> /etc/ssh/sshd_config
# SSH login fix. Otherwise user is kicked off after login
RUN sed 's@session\s*required\s*pam_loginuid.so@session optional pam_loginuid.so@g' -i /etc/pam.d/sshd
EXPOSE 22

RUN usermod -a -G root www-data && echo "www-data ALL=(ALL) NOPASSWD:ALL" >> /etc/sudoers

COPY build/php/entrypoint.sh /docker-entrypoint.sh
RUN chmod +x /docker-entrypoint.sh

RUN curl -sS https://getcomposer.org/installer | php && mv composer.phar /usr/local/bin/composer
RUN mkdir -p /var/www/.composer && chown www-data:www-data /var/www/.composer \
    && mkdir -p /var/www/.npm && chown www-data:www-data /var/www/.npm

RUN mkdir -p ${INSTALL_DIR} && chown www-data:www-data ${INSTALL_DIR}
COPY config/cron/crontab /etc/cron.d/crontab

WORKDIR ${INSTALL_DIR}

USER www-data

#COPY --chown=www-data:www-data ../app ${INSTALL_DIR}
