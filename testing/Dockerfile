FROM pitemplog

ENV SENSOR_DIR='/tmp/devices/' RAND=1 LOCAL_SENSORS=yes
 
RUN set -x \ 
  && cat "${INSTALL_DIR}"_bin/make_testsensors.sh "${INSTALL_DIR}"_bin/install.sh > /usr/local/bin/pitemplog_entrypoint_test \
  && chmod a+x /usr/local/bin/pitemplog_entrypoint_test\
  && pecl install xdebug-3.4.2 \
  && docker-php-ext-enable xdebug \
  && apt-get update \
  && apt-get install -y mariadb-client python3-pip python3-unittest2 python3-mock

COPY src "${INSTALL_DIR}"../templog_tests

VOLUME ["/var/www/", "/usr/local/share/templog_tests"]

WORKDIR /usr/local/share/templog_tests

ENTRYPOINT ["pitemplog_entrypoint_test"]

CMD ["apache2-foreground"]
