FROM php:7.2-apache
ARG target_dir=/usr/local/share/templog/
WORKDIR /
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
  && useradd -mUs /bin/bash pi

COPY build $target_dir
RUN set -x \  
  && chown pi:pi "$target_dir" \
  && chown pi:pi /var/www \
  && chmod a+rwx /var/www \
  && chmod a+x "${target_dir}"_bin/*.{sh,py} \
  && chmod u+x "${target_dir}"_sbin/*.sh \
  && chmod a+w "${target_dir}" \
  && chmod a+w "${target_dir}"conf/config.json \
  && chmod a+w "${target_dir}"_data/config.json \
  && chmod a+x "${target_dir}"_data/*.py \
  && cp "${target_dir}"_sbin/templog.conf /etc/apache2/sites-available/ \
  && chown root:root /etc/apache2/sites-available/templog.conf \
  && rm -f /etc/apache2/sites-enabled/0000-templog.conf \
  && ln -s /etc/apache2/sites-available/templog.conf /etc/apache2/sites-enabled/0000-templog.conf \
  && ln -s /usr/bin/jekyll /usr/local/bin/jekyll\
  && su - www-data -s /bin/bash -c "/usr/bin/python \"${target_dir}\"_data/create_pages.py" \
  && su - www-data -s /bin/bash -c "/usr/local/bin/jekyll build --source \"${target_dir}\"" \
  && cp "${target_dir}"_bin/*.py /usr/local/bin/ \
  && cp "${target_dir}"_bin/pitemplog_backup.sh /usr/local/bin/ \
  && cp "${target_dir}"_bin/pitemplog_restore.sh /usr/local/bin/ \
  && cp "${target_dir}"_bin/pitemplog.conf /etc/ \
  && cp "${target_dir}"_sbin/pitemplog_partition_database.sh /usr/local/sbin/ \
  && crontab "${target_dir}"_sbin/crontab_root \
  && crontab -u pi "${target_dir}"_bin/crontab_docker \
  && service cron start
  