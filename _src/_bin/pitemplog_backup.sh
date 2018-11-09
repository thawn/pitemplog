#!/bin/bash
if [ -z "$1" ]; then
  echo "Usage: $0 [disk|mysql|config] to backup the entire disk/mysql database/config file, respectively."
  exit 1
fi
backup_host=$(awk -F ' *= *' '$1=="backup_host" {print $2}' /etc/pitemplog.conf)
username=$(awk -F ' *= *' '$1=="backup_user" {print $2}' /etc/pitemplog.conf)
case $1 in
disk)
  tar --one-file-system -czf - / | ssh ${username}@${backup_host} "cat >backup/keep_$(date +%Y-%m-%d)templog.root.tar.gz"
  tar -czf - /boot | ssh ${username}@${backup_host} "cat >backup/keep_$(date +%Y-%m-%d)templog.boot.tar.gz"
  tar -czf - /mnt/usb1 | ssh ${username}@${backup_host} "cat >backup/keep_$(date +%Y-%m-%d)templog.usb1.tar.gz"
  ;;
mysql)
  h=$HOSTNAME
  /usr/bin/mysqldump --add-drop-database --hex-blob -x -uroot -pmpi-cbg temperatures | /usr/bin/ssh -i /home/pi/.ssh/id_rsa ${username}@${backup_host} "gzip > backup/$(date +%Y-%m-%d_%H-%M)_${h}_temperatures.sql.gz"
  ;;
config)
  /usr/bin/scp /var/www/conf/config.json ${username}@${backup_host}:backup/$(date +%Y-%m-%d_%H-%M)_${HOSTNAME}_config.json
  ;;
esac
