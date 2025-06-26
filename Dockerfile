FROM php:8.2-apache
SHELL ["/bin/bash", "-c"]
RUN set -x \
  && apt-get update \
  && apt-get -y dist-upgrade \
  && apt-get install -y systemd python3-mysqldb python3-yaml \
  && apt-get install -y --no-install-recommends jekyll cron \
  && apt-get clean \
  && docker-php-ext-install pdo_mysql \
  && rm -rf /var/lib/apt/lists/* \
  && rm -f /etc/apache2/sites-enabled/*.conf \
  && useradd -mUs /bin/bash pi \
  && service cron start

ARG PITEMPLOG_DIR_ARG=/usr/local/share/templog/
ENV LOCAL_SENSORS=no PITEMPLOG_DIR=$PITEMPLOG_DIR_ARG
ENV DB_HOST=mariadb DB_DB=temperatures DB_USER=temp DB_PW=temp
COPY build $PITEMPLOG_DIR
RUN chmod a+x "${PITEMPLOG_DIR_ARG}/_bin/install.sh" \
  && ln -s "${PITEMPLOG_DIR_ARG}/_bin/install.sh" /usr/local/bin/pitemplog_entrypoint
VOLUME ["$PITEMPLOG_DIR"]
WORKDIR /home/pi

ENTRYPOINT ["pitemplog_entrypoint"]

CMD ["apache2-foreground"]