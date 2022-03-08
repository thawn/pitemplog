FROM php:7.4-apache
SHELL ["/bin/bash", "-c"]
RUN set -x \
  && apt-get update \
  && apt-get install -y python3-mysqldb python3-pip \
  && apt-get install -y --no-install-recommends jekyll cron \
  && docker-php-ext-install pdo_mysql \
  && pip install pyyaml \
  && apt-get remove -y --auto-remove python3-pip \
  && rm -rf /var/lib/apt/lists/* \
  && rm -f /etc/apache2/sites-enabled/*.conf \
  && useradd -mUs /bin/bash pi \
  && service cron start

ARG INSTALL_DIR_ARG=/usr/local/share/templog/
ENV LOCAL_SENSORS=no INSTALL_DIR=$INSTALL_DIR_ARG
ENV DB_HOST=mariadb DB_DB=temperatures DB_USER=temp DB_PW=temp
COPY build $INSTALL_DIR
RUN chmod a+x "${INSTALL_DIR_ARG}/_bin/install.sh" \
  && ln -s "${INSTALL_DIR_ARG}/_bin/install.sh" /usr/local/bin/pitemplog_entrypoint
VOLUME ["$INSTALL_DIR"]
WORKDIR /home/pi

ENTRYPOINT ["pitemplog_entrypoint"]

CMD ["apache2-foreground"]