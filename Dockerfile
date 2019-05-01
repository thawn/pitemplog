FROM php:7.2-apache
SHELL ["/bin/bash", "-c"]
RUN set -x \
  && apt-get update \
  && apt-get install -y python-mysqldb python-pip \
  && apt-get install -y --no-install-recommends jekyll cron \
  && docker-php-ext-install pdo_mysql \
  && pip install peewee pyyaml \
  && apt-get remove -y --auto-remove python-pip \
  && rm -rf /var/lib/apt/lists/* \
  && rm -f /etc/apache2/sites-enabled/*.conf \
  && useradd -mUs /bin/bash pi \
  && service cron start

ARG INSTALL_DIR_ARG=/usr/local/share/templog/
ENV LOCAL_SENSORS=no INSTALL_DIR=$INSTALL_DIR_ARG
COPY build $INSTALL_DIR
RUN chmod a+x ${INSTALL_DIR_ARG}/_bin/install.sh \
  && ln -s ${INSTALL_DIR_ARG}/_bin/install.sh /usr/local/bin/pitemplog_entrypoint
VOLUME $INSTALL_DIR
WORKDIR /home/pi

ENTRYPOINT ["pitemplog_entrypoint"]

CMD ["apache2-foreground"]