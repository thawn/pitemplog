FROM pitemplog

ENV SENSOR_DIR='/tmp/devices/' RAND=1 LOCAL_SENSORS=yes
 
RUN cat "${INSTALL_DIR}"_bin/make_testsensors.sh "${INSTALL_DIR}"_bin/install.sh > /usr/local/bin/pitemplog_entrypoint_test \
  && chmod a+x /usr/local/bin/pitemplog_entrypoint_test

ENTRYPOINT ["pitemplog_entrypoint_test"]

CMD ["apache2-foreground"]
