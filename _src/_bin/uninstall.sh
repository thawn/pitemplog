#!/bin/bash
www_dir=/var/www/html
[ -f /etc/profile.d/pitemplog.sh ] && . /etc/profile.d/pitemplog.sh

if [ -z "$VAR" ] ;  then
  echo "VAR is not set, please set it in /etc/profile.d/pitemplog.sh" >&2
  exit 1
fi
sudo crontab -r
sudo -u pi crontab -r
"${PITEMPLOG_DIR}"_data/uninstall_pages.py
sudo rm -r "${PITEMPLOG_DIR}"
sudo rm -r "${www_dir}"/assets
sudo rm -r "${www_dir}"/conf
sudo rm -r "${www_dir}"/data.php
sudo rm -r "${www_dir}"/index.html
sudo rm /etc/apache2/sites-enabled/0000-templog.conf
sudo rm /etc/apache2/sites-available/templog.conf
sudo service apache2 restart
sudo rm /usr/local/bin/partition_database.py
sudo rm /usr/local/bin/reset_aggregates.py
sudo rm /usr/local/bin/tempaggregate.py
sudo rm /usr/local/bin/templog.py
sudo rm /usr/local/bin/pitemplog_backup.sh
sudo rm /usr/local/bin/pitemplog_restore.sh
sudo rm /etc/pitemplog.conf
sudo rm /usr/local/sbin/pitemplog_partition_database.sh

