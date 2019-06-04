#!/bin/bash
www_dir=/var/www/html
sudo crontab -r
sudo -u pi crontab -r
/usr/local/share/templog/_data/uninstall_pages.py
sudo rm -r /usr/local/share/templog
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

