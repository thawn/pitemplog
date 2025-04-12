#!/bin/bash
target_dir=${INSTALL_DIR:-/usr/local/share/templog/}
templog_user=${PT_USER:-pi}
echo "Installing into: $target_dir"
echo "Local sensors: $LOCAL_SENSORS"
mkdir -p "$target_dir"
chown ${templog_user}:${templog_user} "$target_dir"
chmod a+x "${target_dir}"_bin/*.{sh,py}
chmod u+x "${target_dir}"_sbin/*.sh
chmod o-w "${target_dir}"_sbin/*
chmod u+x "${target_dir}"_sbin/setup_timesyncd
chmod a+w "${target_dir}"
chmod a+w "${target_dir}"conf/config.json
chmod a+w "${target_dir}"_data/config.json
chmod a+x "${target_dir}"_data/*.py
cp "${target_dir}"_sbin/setup_timesyncd /etc/init.d/
wd=$( pwd )
cd /etc/rc3.d
ln -fs ../init.d/setup_templog_once S01setup_templog_once
cd $wd
cp "${target_dir}"_sbin/templog.conf /etc/apache2/sites-available/
chown root:root /etc/apache2/sites-available/templog.conf
rm -f /etc/apache2/sites-enabled/0000-templog.conf
ln -s /etc/apache2/sites-available/templog.conf /etc/apache2/sites-enabled/0000-templog.conf
if ! [ -e /usr/local/bin/jekyll ]; then
  ln -s /usr/bin/jekyll /usr/local/bin/jekyll
fi
ln -s "${target_dir}"_bin/pitemplog.py "$(python3 -m site | grep usr/local/lib | cut -d',' -f 1 | xargs)"
chown -R www-data:www-data /var/www/html
su - www-data -s /bin/bash -c "/usr/bin/python3 \"${target_dir}\"_data/create_pages.py"
su - www-data -s /bin/bash -c "/usr/local/bin/jekyll build --source \"${target_dir}\""
ln -s "${target_dir}"_bin/*.py /usr/local/bin/
ln -s "${target_dir}"_bin/pitemplog_backup.sh /usr/local/bin/
ln -s "${target_dir}"_bin/pitemplog_restore.sh /usr/local/bin/
ln -s "${target_dir}"_bin/pitemplog.conf /etc/
ln -s "${target_dir}"_sbin/pitemplog_partition_database.sh /usr/local/sbin/
echo "DB_HOST=${DB_HOST:-localhost}" > /tmp/crontab_env
echo "DB_DB=${DB_DB:-temperatures}" >> /tmp/crontab_env
echo "DB_USER=${DB_USER:-temp}" >> /tmp/crontab_env
echo "DB_PW=${DB_PW:-temp}" >> /tmp/crontab_env
cp /tmp/crontab_env /etc/systemd/system/partition_db.env
cp "${target_dir}"_sbin/*.timer /etc/systemd/system/
cp "${target_dir}"_sbin/*.service /etc/systemd/system/
systemctl enable partition_db.timer
systemctl start partition_db.timer
cgroup=$(grep cpuset /proc/1/cgroup | cut -d ':' -f 3)
if [ "${LOCAL_SENSORS:-yes}" == "no" ]; then
  cat /tmp/crontab_env "${target_dir}"_bin/crontab_nosensors | crontab -u ${templog_user} -
else
  cat /tmp/crontab_env "${target_dir}"_bin/crontab | crontab -u ${templog_user} -
fi
echo "Installation successful. (re)starting apache now."
if [ ! -f /.dockerenv ]; then
  #make sure necessary environment variables are set
  sed -e 's/^/export /' /tmp/crontab_env > /etc/profile.d/pitemplog.sh
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

