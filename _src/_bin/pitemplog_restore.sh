#!/bin/bash
if [ -z "$1" ]; then
  echo "Usage: $0 [mysql|config] <restore from date yyyy-mm-dd> to restore the mysql database/config file, respectively."
  exit 1
fi
backup_host=$(awk -F ' *= *' '$1=="backup_host" {print $2}' /etc/pitemplog.conf)
username=$(awk -F ' *= *' '$1=="backup_user" {print $2}' /etc/pitemplog.conf)
h=$HOSTNAME
if [ -z "$2" ]; then
  restoredate=$(date +%Y-%m-%d)
else
  restoredate=$2
fi
case $1 in
mysql)
  sudo service cron stop
  /usr/bin/ssh -i /home/pi/.ssh/id_rsa diezlab@${target} "zcat backup/${restoredate}*_${h}_temperatures.sql.gz" | mysql -uroot -pmpi-cbg temperatures
  sudo service cron start
  ;;
config)
  /usr/bin/scp ${username}@${backup_host}:backup/${restoredate}*_${h}_config.json  /var/www/conf/config.json
  ;;
esac
