#!/bin/bash
target_dir=${INSTALL_DIR:-/usr/local/share/templog/}
echo "Installing into: $target_dir"
echo "Local sensors: $LOCAL_SENSORS"
mkdir -p "$target_dir"
chown pi:pi "$target_dir"
chown pi:pi /var/www
chmod a+rwx /var/www
chmod a+x "${target_dir}"_bin/*.{sh,py}
chmod u+x "${target_dir}"_sbin/*.sh
chmod a+w "${target_dir}"
chmod a+w "${target_dir}"conf/config.json
chmod a+w "${target_dir}"_data/config.json
chmod a+x "${target_dir}"_data/*.py
cp "${target_dir}"_sbin/templog.conf /etc/apache2/sites-available/
chown root:root /etc/apache2/sites-available/templog.conf
rm -f /etc/apache2/sites-enabled/0000-templog.conf
ln -s /etc/apache2/sites-available/templog.conf /etc/apache2/sites-enabled/0000-templog.conf
if ! [ -e /usr/local/bin/jekyll ]; then
  ln -s /usr/bin/jekyll /usr/local/bin/jekyll
fi
su - www-data -s /bin/bash -c "/usr/bin/python \"${target_dir}\"_data/create_pages.py"
su - www-data -s /bin/bash -c "/usr/local/bin/jekyll build --source \"${target_dir}\""
cp "${target_dir}"_bin/*.py /usr/local/bin/
cp "${target_dir}"_bin/pitemplog_backup.sh /usr/local/bin/
cp "${target_dir}"_bin/pitemplog_restore.sh /usr/local/bin/
cp "${target_dir}"_bin/pitemplog.conf /etc/
cp "${target_dir}"_sbin/pitemplog_partition_database.sh /usr/local/sbin/
echo "DB_HOST=${DB_HOST:-localhost}" > /tmp/crontab_env
echo "DB_DB=${DB_DB:-temperatures}" >> /tmp/crontab_env
echo "DB_USER=${DB_USER:-temp}" >> /tmp/crontab_env
echo "DB_PW=${DB_PW:-temp}" >> /tmp/crontab_env
cat /tmp/crontab_env "${target_dir}"_sbin/crontab_root | crontab -
cgroup=$(grep cpuset /proc/1/cgroup | cut -d ':' -f 3)
if [ "${LOCAL_SENSORS:-yes}" == "no" ]; then
  cat /tmp/crontab_env "${target_dir}"_bin/crontab_nosensors | crontab -u pi -
else
  cat /tmp/crontab_env "${target_dir}"_bin/crontab | crontab -u pi -
fi
echo "Installation successful. (re)starting apache now."
if [ "${cgroup#/docker}" == "$cgroup" ]; then
  #if we are not in a docker container, we restart the apache service
  service apache2 restart
else
  #In a docker container, apache runs as foreground process and is passed any arguments passed to the container
  service cron start
  if [ "${1#-}" != "$1" ]; then
	set -- apache2-foreground "$@"
  fi
  exec "$@"
fi

