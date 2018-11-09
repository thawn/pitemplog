#!/bin/bash
target_dir=/usr/local/share/templog/
sudo mkdir -p "$target_dir"
sudo chown pi:pi "$target_dir"
sudo chown pi:pi /var/www
sudo chmod a+rwx /var/www
chmod a+x "${target_dir}"_bin/*.{sh,py}
chmod u+x "${target_dir}"_sbin/*.sh
chmod a+w "${target_dir}"
chmod a+w "${target_dir}"conf/config.json
chmod a+w "${target_dir}"_data/config.json
chmod a+x "${target_dir}"_data/*.py
sudo cp "${target_dir}"_sbin/templog.conf /etc/apache2/sites-available/
sudo chown root:root /etc/apache2/sites-available/templog.conf
sudo rm -f /etc/apache2/sites-enabled/0000-templog.conf
sudo ln -s /etc/apache2/sites-available/templog.conf /etc/apache2/sites-enabled/0000-templog.conf
sudo service apache2 restart
if ! [ -e /usr/local/bin/jekyll ]; then
  sudo ln -s /usr/bin/jekyll /usr/local/bin/jekyll
fi
sudo -u www-data "${target_dir}"_data/create_pages.py
sudo -u www-data /usr/local/bin/jekyll build --source "${target_dir}"
sudo cp "${target_dir}"_bin/*.py /usr/local/bin/
sudo cp "${target_dir}"_bin/pitemplog_backup.sh /usr/local/bin/
sudo cp "${target_dir}"_bin/pitemplog_restore.sh /usr/local/bin/
sudo cp "${target_dir}"_bin/pitemplog.conf /etc/
sudo cp "${target_dir}"_sbin/pitemplog_partition_database.sh /usr/local/sbin/
sudo crontab "${target_dir}"_sbin/crontab_root
sudo -u pi crontab "${target_dir}"_bin/crontab

